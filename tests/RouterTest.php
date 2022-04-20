<?php

namespace Limpich\Tests\Router;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use JetBrains\PhpStorm\NoReturn;
use Limpich\Router\Exceptions\CannotResolveMethodArgumentsException;
use Limpich\Router\Exceptions\NoMethodForPathException;
use Limpich\Router\Router;
use Limpich\Tests\Router\Controllers\TestController;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Container;
use Throwable;

class RouterTest extends TestCase
{
  #[NoReturn] public function testGet1()
  {
    $container = new Container();
    $container->set(TestController::class, new TestController());

    $response = (new Router($container))
      ->registerController(TestController::class)
      ->run(new ServerRequest('GET', 'https://example.com/get1'));

    $this->assertEquals('valid', $response);
  }

  /**
   * @dataProvider get2Provider
   */
  #[NoReturn] public function testGet2(int $a, int $b, int $expected)
  {
    $container = new Container();
    $container->set(TestController::class, new TestController());

    $response = (new Router($container))
      ->registerController(TestController::class)
      ->run(
        (new ServerRequest('GET', "https://example.com/get2?a=$a&b=$b"))
          ->withQueryParams(['a' => $a, 'b' => $b,])
      );

    $this->assertEquals($expected, $response);
  }

  public function get2Provider(): array
  {
    return [
      [123,  234,   357],
      [-1,   1,     0  ],
      [111, -1000, -889],
    ];
  }

  #[NoReturn] public function testGet3()
  {
    $container = new Container();
    $container->set(TestController::class, new TestController());

    $this->expectException(CannotResolveMethodArgumentsException::class);

    (new Router($container))
      ->registerController(TestController::class)
      ->setThrowableHandler(function (Throwable $e, ServerRequestInterface $serverRequest) {
        return new Response(400);
      })
      ->run(
        (new ServerRequest('GET', "https://example.com/get2?b=1"))
          ->withQueryParams(['b' => 1,])
      );
  }

  #[NoReturn] public function testGet4()
  {
    $container = new Container();
    $container->set(TestController::class, new TestController());

    $response = (new Router($container))
      ->registerController(TestController::class)
      ->setCannotResolveArgumentsHandler(function (Throwable $e, ServerRequestInterface $serverRequest) {
        return 'valid';
      })
      ->run(
        (new ServerRequest('GET', "https://example.com/get2?b=1"))
          ->withQueryParams(['b' => 1,])
      );

    $this->assertEquals('valid', $response);
  }

  #[NoReturn] public function testGet5()
  {
    $container = new Container();
    $container->set(TestController::class, new TestController());

    $this->expectException(NoMethodForPathException::class);

    (new Router($container))
      ->registerController(TestController::class)
      ->setThrowableHandler(function (Throwable $e, ServerRequestInterface $serverRequest) {
        return new Response(500);
      })
      ->run(
        new ServerRequest('GET', "https://example.com/notExistingPath")
      );
  }

  #[NoReturn] public function testGet6()
  {
    $container = new Container();
    $container->set(TestController::class, new TestController());

    $response = (new Router($container))
      ->registerController(TestController::class)
      ->setDefaultHandler(function (ServerRequestInterface $serverRequest) {
        return 'valid';
      })
      ->run(
        new ServerRequest('GET', "https://example.com/notExistingPath")
      );

    $this->assertEquals('valid', $response);
  }
}