<?php

namespace Limpich\Tests\Router;

use GuzzleHttp\Psr7\Response;
use Limpich\Router\Exceptions\CannotResolveMethodArgumentsException;
use Limpich\Router\RouterHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class RouterHandler implements RouterHandlerInterface
{
  /**
   * @inheritDoc
   */
  public function handleOptionsRequest(ServerRequestInterface $request): ResponseInterface
  {
    return (new Response(200))
      ->withHeader('Origin', 'https://example.com');
  }

  /**
   * @inheritDoc
   */
  public function handleThrowable(Throwable $e, ServerRequestInterface $request): ResponseInterface
  {
    return new Response(510);
  }

  /**
   * @inheritDoc
   */
  public function handleDefault(ServerRequestInterface $request): ResponseInterface
  {
    return new Response(404);
  }

  public function handleCannotResolveArguments(
    CannotResolveMethodArgumentsException $exception, 
    ServerRequestInterface $request): ResponseInterface
  {
    $response = [
      'path'  => $request->getUri()->getPath(),
      'error' => $exception->getMessage(),
    ];
    
    return new Response(400, [], json_encode($response));
  }
}