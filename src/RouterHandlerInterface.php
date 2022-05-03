<?php

namespace Limpich\Router;

use Limpich\Router\Exceptions\CannotResolveMethodArgumentsException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

interface RouterHandlerInterface
{
  /**
   * Called if the route is not found
   * @param ServerRequestInterface $request
   * @return ResponseInterface
   */
  public function handleDefault(ServerRequestInterface $request): ResponseInterface;

  /**
   * Called when cannot inject closure arguments
   * @param CannotResolveMethodArgumentsException $exception
   * @param ServerRequestInterface $request
   * @return ResponseInterface
   */
  public function handleCannotResolveArguments(
    CannotResolveMethodArgumentsException $exception, 
    ServerRequestInterface $request): ResponseInterface;

  /**
   * Called when an error occurs in the controller call
   * @param Throwable $e
   * @param ServerRequestInterface $request
   * @return ResponseInterface
   */
  public function handleThrowable(Throwable $e, ServerRequestInterface $request): ResponseInterface;

  /**
   * Called on HTTP Options
   * @param ServerRequestInterface $request
   * @return ResponseInterface
   */
  public function handleOptionsRequest(ServerRequestInterface $request): ResponseInterface;
}