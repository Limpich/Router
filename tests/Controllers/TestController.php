<?php

namespace Limpich\Tests\Router\Controllers;

use Limpich\Router\Attributes\Controller;
use Limpich\Router\Attributes\Method;

#[Controller]
class TestController
{
  #[Method('/get1', Method::GET)]
  public function get1(): string
  {
    return 'valid';
  }

  #[Method('/get2', Method::GET)]
  public function get2(int $a, ?int $b = null): int
  {
    return $a + $b;
  }
}