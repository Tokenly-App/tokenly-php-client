<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Tokenly\Data\GiftTokensResponse;
use Tokenly\Data\TokenBalanceResponse;
use Tokenly\Exceptions\AuthenticationException;
use Tokenly\Exceptions\ForbiddenException;
use Tokenly\Exceptions\TokenlyException;
use Tokenly\Exceptions\ValidationException;
use Tokenly\TokenlyClient;

function makeClient(MockHandler $mockHandler): TokenlyClient
{
    $handlerStack = HandlerStack::create($mockHandler);
    $httpClient = new Client(['handler' => $handlerStack]);

    return new TokenlyClient('test-key', 'test-secret', $httpClient);
}

function makeJsonResponse(int $status, mixed $data): Response
{
    return new Response(
        status: $status,
        headers: ['Content-Type' => 'application/json'],
        body: json_encode($data, JSON_THROW_ON_ERROR),
    );
}

// ─── giftTokens ──────────────────────────────────────────────────────────────

describe('giftTokens', function (): void {
    it('returns a GiftTokensResponse on success', function (): void {
        $payload = [
            'success' => true,
            'message' => 'Tokens gifted successfully',
            'transaction' => [
                'id' => 123,
                'sender_app_id' => 1,
                'target_app_code' => 'MYRS8WGC',
                'created_at' => '2026-02-11T10:30:00.000000Z',
            ],
        ];

        $client = makeClient(new MockHandler([makeJsonResponse(200, $payload)]));

        $response = $client->giftTokens('MYRS8WGC');

        expect($response)->toBeInstanceOf(GiftTokensResponse::class)
            ->and($response->success)->toBeTrue()
            ->and($response->message)->toBe('Tokens gifted successfully')
            ->and($response->transaction->id)->toBe(123)
            ->and($response->transaction->senderAppId)->toBe(1)
            ->and($response->transaction->targetAppCode)->toBe('MYRS8WGC')
            ->and($response->transaction->createdAt)->toBe('2026-02-11T10:30:00.000000Z');
    });

    it('throws AuthenticationException on 401', function (): void {
        $mock = new MockHandler([
            new ClientException(
                'Unauthorized',
                new Request('POST', 'tokens/gift'),
                makeJsonResponse(401, ['message' => 'Unauthenticated.']),
            ),
        ]);

        $client = makeClient($mock);

        expect(fn () => $client->giftTokens('MYRS8WGC'))
            ->toThrow(AuthenticationException::class, 'Unauthenticated.');
    });

    it('throws ForbiddenException on 403', function (): void {
        $mock = new MockHandler([
            new ClientException(
                'Forbidden',
                new Request('POST', 'tokens/gift'),
                makeJsonResponse(403, ['message' => 'Your app is inactive.']),
            ),
        ]);

        $client = makeClient($mock);

        expect(fn () => $client->giftTokens('MYRS8WGC'))
            ->toThrow(ForbiddenException::class, 'Your app is inactive.');
    });

    it('throws ValidationException on 400', function (): void {
        $mock = new MockHandler([
            new ClientException(
                'Bad Request',
                new Request('POST', 'tokens/gift'),
                makeJsonResponse(400, ['message' => 'You cannot gift tokens to yourself.']),
            ),
        ]);

        $client = makeClient($mock);

        expect(fn () => $client->giftTokens('MYRS8WGC'))
            ->toThrow(ValidationException::class, 'You cannot gift tokens to yourself.');
    });

    it('throws TokenlyException with fallback message when response has no message field', function (): void {
        $mock = new MockHandler([
            new ClientException(
                'Server Error',
                new Request('POST', 'tokens/gift'),
                makeJsonResponse(422, []),
            ),
        ]);

        $client = makeClient($mock);

        expect(fn () => $client->giftTokens('MYRS8WGC'))
            ->toThrow(TokenlyException::class, 'Tokenly API error');
    });

    it('throws TokenlyException for unexpected 5xx-like client errors', function (): void {
        $mock = new MockHandler([
            new ClientException(
                'Conflict',
                new Request('POST', 'tokens/gift'),
                makeJsonResponse(409, ['message' => 'Conflict occurred.']),
            ),
        ]);

        $client = makeClient($mock);

        expect(fn () => $client->giftTokens('MYRS8WGC'))
            ->toThrow(TokenlyException::class, 'Conflict occurred.');
    });
});

// ─── getTokenBalance ──────────────────────────────────────────────────────────

describe('getTokenBalance', function (): void {
    it('returns a TokenBalanceResponse on success', function (): void {
        $payload = [
            'success' => true,
            'app_id' => 1,
            'tokens_sent' => 50,
            'tokens_received' => 100,
            'balance' => 50,
        ];

        $client = makeClient(new MockHandler([makeJsonResponse(200, $payload)]));

        $response = $client->getTokenBalance();

        expect($response)->toBeInstanceOf(TokenBalanceResponse::class)
            ->and($response->success)->toBeTrue()
            ->and($response->appId)->toBe(1)
            ->and($response->tokensSent)->toBe(50)
            ->and($response->tokensReceived)->toBe(100)
            ->and($response->balance)->toBe(50);
    });

    it('throws AuthenticationException on 401', function (): void {
        $mock = new MockHandler([
            new ClientException(
                'Unauthorized',
                new Request('GET', 'tokens/balance'),
                makeJsonResponse(401, ['message' => 'Unauthenticated.']),
            ),
        ]);

        $client = makeClient($mock);

        expect(fn () => $client->getTokenBalance())
            ->toThrow(AuthenticationException::class, 'Unauthenticated.');
    });

    it('throws ForbiddenException on 403', function (): void {
        $mock = new MockHandler([
            new ClientException(
                'Forbidden',
                new Request('GET', 'tokens/balance'),
                makeJsonResponse(403, ['message' => 'App is not active.']),
            ),
        ]);

        $client = makeClient($mock);

        expect(fn () => $client->getTokenBalance())
            ->toThrow(ForbiddenException::class, 'App is not active.');
    });

    it('throws TokenlyException with fallback message when response has no message field', function (): void {
        $mock = new MockHandler([
            new ClientException(
                'Error',
                new Request('GET', 'tokens/balance'),
                makeJsonResponse(500, []),
            ),
        ]);

        $client = makeClient($mock);

        expect(fn () => $client->getTokenBalance())
            ->toThrow(TokenlyException::class, 'Tokenly API error');
    });
});

// ─── constructor ─────────────────────────────────────────────────────────────

describe('constructor', function (): void {
    it('uses the injected HTTP client', function (): void {
        $payload = [
            'success' => true,
            'app_id' => 99,
            'tokens_sent' => 10,
            'tokens_received' => 20,
            'balance' => 10,
        ];

        $client = makeClient(new MockHandler([makeJsonResponse(200, $payload)]));

        $response = $client->getTokenBalance();

        expect($response->appId)->toBe(99);
    });
});
