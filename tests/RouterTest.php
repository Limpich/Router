<?php

namespace Limpich\Tests\Router;

use DI\Container;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use JetBrains\PhpStorm\NoReturn;
use Limpich\Router\Attributes\Method;
use Limpich\Router\Exceptions\CannotResolveMethodArgumentsException;
use Limpich\Router\Exceptions\NoMethodForPathException;
use Limpich\Router\RouterBuilder;
use Limpich\Router\RouterOld;
use Limpich\Tests\Router\Controllers\TestController;
use Limpich\Tests\Router\Middlewares\TestMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class RouterTest extends TestCase
{
  private function getBuilder(): RouterBuilder
  {
    return new RouterBuilder(
      new RouterHandler(),
      new Container(),
    );
  }
  
  
  public function testRouterDefault(): void
  {
    $router = $this
      ->getBuilder()
      ->withRoute(Method::GET, '/get', function() {
        return new Response(200);
      })
      ->build();
    
    $response = $router->run(new ServerRequest('GET', 'https://example.com/'));
    
    $this->assertEquals(404, $response->getStatusCode());
  }

  public function testOptions(): void
  {
    $router = $this
      ->getBuilder()
      ->withRoute(Method::GET, '/get', function() {
        return new Response(200);
      })
      ->build();

    $response = $router->run(new ServerRequest(Method::OPTIONS, 'https://example.com/get'));

    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals('https://example.com', $response->getHeaderLine('Origin'));
  }
  
  public function testGetWithoutParams(): void
  {
    $router = $this
      ->getBuilder()
      ->withRoute(Method::GET, '/get', function() {
        return new Response(204);
      })
      ->build();

    $response = $router->run(new ServerRequest(Method::GET, 'https://example.com/get'));

    $this->assertEquals(204, $response->getStatusCode());
  }

  public function testGetWithoutParamsController(): void
  {
    $router = $this
      ->getBuilder()
      ->withController(TestController::class)
      ->build();

    $response = $router->run(new ServerRequest(Method::GET, 'https://example.com/get'));

    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals('valid', $response->getBody()->getContents());
  }

  public function testGetCannotResolveParams(): void
  {
    $router = $this
      ->getBuilder()
      ->withController(TestController::class)
      ->build();

    $response = $router->run(new ServerRequest(Method::GET, 'https://example.com/get2'));
    
    $array = json_decode($response->getBody()->getContents());
    
    $this->assertEquals(400, $response->getStatusCode());
    $this->assertEquals('/get2',            $array->path);
    $this->assertEquals('a can\'t be null', $array->error);
  }
}