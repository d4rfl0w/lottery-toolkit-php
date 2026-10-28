<?php

declare(strict_types=1);

namespace LotteryToolkitExample;

use Rupcom\LotteryToolkit\Exception\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

final readonly class LotteryController
{
    public function __construct(private LotteryService $lottery)
    {
    }

    #[Route('/statistics', methods: ['GET'])]
    public function statistics(): JsonResponse
    {
        return new JsonResponse($this->lottery->statistics());
    }

    #[Route('/tickets/check', methods: ['POST'])]
    public function check(Request $request): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new BadRequestHttpException('Malformed JSON.', $exception);
        }
        if (!is_array($payload) || !is_array($payload['bets'] ?? null)) {
            throw new ValidationException('The request must contain a bets array.');
        }

        return new JsonResponse(['matches' => $this->lottery->check($payload['bets'])]);
    }
}
