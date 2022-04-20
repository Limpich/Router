<?php

namespace Limpich\Router\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Controller
{
  /**
   * @param string|null $path
   */
  public function __construct(
    private ?string $path = null
  ) {

  }

  /**
   * @return string|null
   */
  public function getPath(): ?string
  {
    return $this->path;
  }
}