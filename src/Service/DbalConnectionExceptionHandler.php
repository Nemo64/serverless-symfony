<?php

namespace App\Service;


use Doctrine\DBAL\Exception\ConnectionException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;

class DbalConnectionExceptionHandler implements EventSubscriberInterface
{
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ExceptionEvent::class => 'handleKernelException',
            AuthenticationFailureEvent::class => 'handleAuthenticationFailure',
        ];
    }

    /**
     * @param ExceptionEvent $event
     *
     * @see \Symfony\Component\HttpKernel\HttpKernel::handleThrowable
     */
    public function handleKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if ($exception instanceof ConnectionException) {
            $event->setThrowable($this->createServiceUnavailableException($exception));
        }
    }

    /**
     * @param AuthenticationFailureEvent $event
     *
     * @see \Symfony\Component\Security\Core\Authentication\AuthenticationProviderManager::authenticate
     */
    public function handleAuthenticationFailure(AuthenticationFailureEvent $event): void
    {
        $exception = $event->getAuthenticationException()->getPrevious();
        if ($exception instanceof ConnectionException) {
            throw $this->createServiceUnavailableException($exception);
        }
    }

    private function createServiceUnavailableException(ConnectionException $exception): ServiceUnavailableHttpException
    {
        $previous = $exception->getPrevious() ?? $exception;
        $retryAfter = 60 * 60; // retry in 1 hour

        $auroraPaused = $previous->getErrorCode() === '6000';
        if ($auroraPaused) {
            $retryAfter = 60; // retry after 60 seconds
        }

        return new ServiceUnavailableHttpException($retryAfter, $previous->getMessage(), $exception);
    }
}
