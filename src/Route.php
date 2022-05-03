<?php

namespace Limpich\Router;

use Closure;

class Route
{
  public function __construct(
    private string $method,
    private string $path,
    private Closure $callable
  ) {
    
  }

  /**
   * @return string
   */
  public function getMethod(): string
  {
    return $this->method;
  }

  /**
   * @return string
   */
  public function getPath(): string
  {
    return $this->path;
  }

  /**
   * @return Closure
   */
  public function getCallable(): Closure
  {
    return $this->callable;
  }
}