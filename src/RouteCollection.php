<?php

namespace Limpich\Router;

use Closure;

class RouteCollection
{
  /**
   * @var array 
   */
  private array $routes = [];

  /**
   * @param string $method
   * @param string $path
   * @param Closure $closure
   * @return $this
   */
  public function add(string $method, string $path, Closure $closure): RouteCollection
  {
    $this->routes[] = new Route($method, $path, $closure);
    
    return $this;
  }

  /**
   * @return array
   */
  public function getRoutes(): array
  {
    return $this->routes;
  }
}