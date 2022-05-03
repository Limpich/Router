<?php

namespace Limpich\Router;

use Closure;
use Limpich\Router\Attributes\Controller;
use Limpich\Router\Attributes\Method;
use Limpich\Router\Attributes\Middleware;
use Limpich\Router\Exceptions\ClassNotControllerException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionMethod;
use ReflectionObject;

class RouterBuilder
{
  private RouteCollection $routeCollection;
  
  public function __construct(
    private RouterHandlerInterface $routerHandler,
    private ContainerInterface     $container,
  ) {
    $this->routeCollection = new RouteCollection();  
  }
  
  public function withRoute(string $method, string $path, Closure $closure): self
  {
    $path = "~^$path\$~i";
    $this->routeCollection->add($method, $path, $closure);
    
    return $this;
  }
  
  public function withController(string $controllerClass): self
  {
    try {
      $controller = $this->container->get($controllerClass);
      $controllerReflectionObject = new ReflectionObject($controller);
    } catch (NotFoundExceptionInterface | ContainerExceptionInterface) {
      throw new ClassNotControllerException("Class $controllerClass not found");
    }

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
      $methodPattern = "~^$methodPattern\$~i";
      $middleware = null;
      if ($middlewareAttribute = $reflectionMethod->getAttributes(Middleware::class)[0] ?? null) {
        // TODO: 
        $middleware = $middlewareAttribute->newInstance()->getCode();
      }

      $this->routeCollection->add($methodAttribute->getMethod(), $methodPattern, $methodClosure);
    }
    
    return $this;
  }
  
  public function build(): Router
  {
    return (new Router(
      $this->routeCollection,
      $this->routerHandler,
      $this->container
    ));
  }
}