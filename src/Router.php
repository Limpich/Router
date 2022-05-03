<?php

namespace Limpich\Router;

use Closure;
use Limpich\Router\Attributes\Method;
use Limpich\Router\Exceptions\CannotResolveMethodArgumentsException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionFunction;

class Router
{
  public function __construct(
    private readonly RouteCollection        $routeCollection,
    private readonly RouterHandlerInterface $routerHandler,
    private readonly ContainerInterface     $container
  ) {
    
  }
  
  public function run(ServerRequestInterface $request): ResponseInterface
  {
    $requestMethod = strtolower($request->getMethod());
    
    if ($requestMethod === strtolower(Method::OPTIONS)) {
      return $this->routerHandler->handleOptionsRequest($request);
    }
    
    $routes = $this->routeCollection->getRoutes();
    $routes = array_filter($routes, function (Route $route) use ($requestMethod) {
      return $requestMethod === strtolower($route->getMethod());
    });

    /**
     * @var Route $route
     */
    foreach ($routes as $route) {
      $resolvedVars = [];
      if ($this->isPatternMatch($request, $route->getPath(), $resolvedVars)) {
        try {
          return $this->runWithParamsInject($route->getCallable(), $resolvedVars);  
        } catch (CannotResolveMethodArgumentsException $exception) {
          return $this->routerHandler->handleCannotResolveArguments($exception, $request);
        }
        
      }
    }
    
    return $this->routerHandler->handleDefault($request);
  }

  private function isPatternMatch(ServerRequestInterface $request, string $pattern, array &$resolvedVars): bool
  {
    return preg_match_all(
      $pattern,
      $request->getUri()->getPath(),
      $resolvedVars
    );
  }

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
}