<?php

declare(strict_types=1);

namespace Rupcom\LotteryToolkit\Bridge\Symfony;

use Rupcom\LotteryToolkit\Exception\GameMismatchException;
use Rupcom\LotteryToolkit\Exception\ValidationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/** Opt-in mapping. Import/provider failures stay server errors; no internal message is exposed. */
final class DomainExceptionSubscriber implements EventSubscriberInterface
{
    /** @return array<string, string> */
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onException'];
    }

    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if ($exception instanceof ValidationException || $exception instanceof GameMismatchException) {
            $event->setResponse(new JsonResponse(['error' => 'invalid_lottery_data'], 422));
        }
    }
}
