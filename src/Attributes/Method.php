<?php

namespace Limpich\Router\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Method
{
  public const GET    = 'GET';
  public const POST   = 'POST';
  public const PUT    = 'PUT';
  public const PATCH  = 'PATCH';
  public const DELETE = 'DELETE';

  /**
   * @param string $pattern
   * @param string $method
   */
  public function __construct(
    private string $pattern,
    private string $method,
  ) {

  }

  /**
   * @return string
   */
  public function getPattern(): string
  {
    return $this->pattern;
  }

  /**
   * @return string
   */
  public function getMethod(): string
  {
    return $this->method;
  }
}