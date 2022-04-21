<?php

namespace Limpich\Router;

use Closure;
use Limpich\Router\Attributes\Controller;
use Limpich\Router\Attributes\Method;
use Limpich\Router\Exceptions\CannotResolveMethodArgumentsException;
use Limpich\Router\Exceptions\ClassNotControllerException;
use Limpich\Router\Exceptions\NoMethodForPathException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionObject;
use Throwable;

class Router
{
  /**
   * @var array
   */
  public array $resolvedRoutes = [
    Method::GET    => [],
    Method::POST   => [],
    Method::PUT    => [],
    Method::PATCH  => [],
    Method::DELETE => [],
  ];

  private ?Closure $defaultHandler = null;
  private ?Closure $throwableHandler = null;
  private ?Closure $cannotResolveArgumentsHandler = null;

  /**
   * @param ContainerInterface $container
   */
  public function __construct(
    private ContainerInterface $container
  )
  { }

  /**
   * @param Closure $handler
   *  Callable with 2 arguments - \Throwable and \Psr\Http\Message\ServerRequestInterface
   * @return $this
   */
  public function setThrowableHandler(Closure $handler): Router
  {
    $this->throwableHandler = $handler;

    return $this;
  }

  /**
   * @param Closure $handler
   *  Callable with 1 argument - \Psr\Http\Message\ServerRequestInterface
   * @return Router
   */
  public function setDefaultHandler(Closure $handler): Router
  {
    $this->defaultHandler = $handler;

    return $this;
  }

  /**
   * @param Closure|null $cannotResolveArgumentsHandler
   *  Callable with 2 arguments - \Throwable and \Psr\Http\Message\ServerRequestInterface
   */
  public function setCannotResolveArgumentsHandler(?Closure $cannotResolveArgumentsHandler): self
  {
    $this->cannotResolveArgumentsHandler = $cannotResolveArgumentsHandler;

    return $this;
  }

  public function registerController(string $controllerClass): Router
  {
    try {
      $controller = $this->container->get($controllerClass);
    } catch (NotFoundExceptionInterface | ContainerExceptionInterface) {
      throw new ClassNotControllerException("Class $controllerClass not found");
    }
    $controllerReflectionObject = new ReflectionObject($controller);

    $controllerAttributeReflection = $controllerReflectionObject
        ->getAttributes(Controller::class)[0] ?? null;
    if (is_null($controllerAttributeReflection)) {
      throw new ClassNotControllerException("Class $controllerClass hasn't ControllerAttribute");
    }

    /** @var Controller $controllerAttribute */
    $controllerAttribute = $controllerAttributeReflection->newInstance();
    foreach ($controllerReflectionObject->getMethods(ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
      $methodAttributeReflection = $reflectionMethod->getAttributes(Method::class)[0] ?? null;
      if (is_null($methodAttributeReflection)) {
        continue;
      }

      /** @var Method $methodAttribute */
      $methodAttribute = $methodAttributeReflection->newInstance();
      $methodClosure = $reflectionMethod->getClosure($controller);
      $methodPattern = $controllerAttribute->getPath() . $methodAttribute->getPattern();
      $methodPattern = "~^$methodPattern$~i";

      $this->resolvedRoutes[$methodAttribute->getMethod()][$methodPattern] = $methodClosure;
    }

    return $this;
  }

  public function registerControllers(array $controllerClasses): self
  {
    foreach ($controllerClasses as $controllerClass) {
      $this->registerController($controllerClass);
    }

    return $this;
  }

  public function run(ServerRequestInterface $serverRequest): mixed
  {
    /**
     * @var string $pattern
     * @var Closure $pattern
     */
    foreach ($this->resolvedRoutes[$serverRequest->getMethod()] as $pattern => $closure) {
      $resolvedVars = [];
      if ($this->isPatternMatch($serverRequest, $pattern, $resolvedVars)) {
        $extractedVars = array_merge(
          $this->extractVarsFromServerRequest($serverRequest),
          $resolvedVars
        );

        try {
          return $this->runWithParamsInject($closure, $extractedVars);
        } catch (CannotResolveMethodArgumentsException $exception) {
          return !is_null($this->cannotResolveArgumentsHandler)
            ? call_user_func_array($this->cannotResolveArgumentsHandler, [$exception, $serverRequest])
            : throw $exception;
        } catch (Throwable $exception) {
          return !is_null($this->throwableHandler)
            ? call_user_func_array($this->throwableHandler, [$exception, $serverRequest])
            /** Throw exception if $this->throwableHandler isn`t set */
            : throw $exception;
        }
      }
    }

    return !is_null($this->defaultHandler)
      ? call_user_func_array($this->defaultHandler, [$serverRequest])
      : throw new NoMethodForPathException("No method for path {$serverRequest->getUri()->getPath()} was found.");
  }

  private function isPatternMatch(ServerRequestInterface $serverRequest, string $pattern, array &$resolvedVars): bool
  {
    return preg_match_all(
      $pattern,
      $serverRequest->getUri()->getPath(),
      $resolvedVars
    );
  }

  /**
   * @param Closure $closure
   * @param array $extractedVars
   * @return mixed
   * @throws CannotResolveMethodArgumentsException
   */
  private function runWithParamsInject(Closure $closure, array $extractedVars): mixed
  {
    $varsToInject = [];
    $reflectionFunction = new ReflectionFunction($closure);
    foreach ($reflectionFunction->getParameters() as $parameter) {
      $argument = $extractedVars[$parameter->getName()]
        ?? ($parameter->isDefaultValueAvailable()
          ? $parameter->getDefaultValue()
          : null);

      if (!$parameter->allowsNull() && is_null($argument)) {
        throw new CannotResolveMethodArgumentsException("{$parameter->getName()} can't be null");
      }

      $varsToInject[$parameter->getName()] = $argument;
    }

    return $reflectionFunction->invoke(...$varsToInject);
  }

  private function extractVarsFromServerRequest(ServerRequestInterface $serverRequest): array
  {
    $getParams = $serverRequest->getQueryParams();
    $postParams = (array)$serverRequest->getParsedBody();

    return array_merge(
      $getParams,
      $postParams
    );
  }
}