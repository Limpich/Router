<?php

namespace Limpich\Tests\Router\Controllers;

use GuzzleHttp\Psr7\Response;
use Limpich\Router\Attributes\Controller;
use Limpich\Router\Attributes\Method;
use Limpich\Router\Attributes\Middleware;
use Psr\Http\Message\ResponseInterface;

#[Controller]
class TestController
{
  #[Method('/get', Method::GET)]
  public function get(): ResponseInterface
  {
    return new Response(200, [], 'valid');
  }

  #[Method('/get2', Method::GET)]
  public function get2(int $a, ?int $b = null): int
  {
    return $a + $b;
  }

  #[Method('/get3', Method::GET), Middleware(code: 'test')]
  public function get3(): ResponseInterface
  {
    return new Response(200, [], 'valid');
  }
}