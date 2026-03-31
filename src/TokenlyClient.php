<?php

declare(strict_types=1);

namespace Tokenly;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Tokenly\Data\GiftTokensResponse;
use Tokenly\Data\TokenBalanceResponse;
use Tokenly\Exceptions\AuthenticationException;
use Tokenly\Exceptions\ForbiddenException;
use Tokenly\Exceptions\TokenlyException;
use Tokenly\Exceptions\ValidationException;

class TokenlyClient
{
    private const BASE_URI = 'https://tokenlyapp.com/api/v1/';

    private readonly ClientInterface $httpClient;

    public function __construct(
        private readonly string $appKey,
        private readonly string $appSecret,
        ?ClientInterface $httpClient = null,
    ) {
        $this->httpClient = $httpClient ?? new Client([
            'base_uri' => self::BASE_URI,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-App-Key' => $this->appKey,
                'X-App-Secret' => $this->appSecret,
            ],
        ]);
    }

    /**
     * Gift tokens to another registered app.
     *
     * @throws AuthenticationException When credentials are invalid or missing.
     * @throws ForbiddenException      When the app is inactive or lacks permission.
     * @throws ValidationException     When the request parameters are invalid.
     * @throws TokenlyException        On any other API error.
     */
    public function giftTokens(string $targetAppCode): GiftTokensResponse
    {
        try {
            $response = $this->httpClient->request('POST', 'tokens/gift', [
                'json' => ['target_app_code' => $targetAppCode],
            ]);

            /** @var array{success: bool, message: string, transaction: array{id: int, sender_app_id: int, target_app_code: string, created_at: string}} $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            return GiftTokensResponse::fromArray($data);
        } catch (ClientException $exception) {
            throw $this->buildException($exception);
        }
    }

    /**
     * Retrieve the current token balance for this app.
     *
     * @throws AuthenticationException When credentials are invalid or missing.
     * @throws ForbiddenException      When the app is inactive or lacks permission.
     * @throws TokenlyException        On any other API error.
     */
    public function getTokenBalance(): TokenBalanceResponse
    {
        try {
            $response = $this->httpClient->request('GET', 'tokens/balance');

            /** @var array{success: bool, app_id: int, tokens_sent: int, tokens_received: int, balance: int} $data */
            $data = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            return TokenBalanceResponse::fromArray($data);
        } catch (ClientException $exception) {
            throw $this->buildException($exception);
        }
    }

    private function buildException(ClientException $exception): TokenlyException
    {
        $statusCode = $exception->getResponse()->getStatusCode();
        $message = $this->extractErrorMessage($exception);

        return match ($statusCode) {
            401 => new AuthenticationException($message, $statusCode, $exception),
            403 => new ForbiddenException($message, $statusCode, $exception),
            400 => new ValidationException($message, $statusCode, $exception),
            default => new TokenlyException($message, $statusCode, $exception),
        };
    }

    private function extractErrorMessage(ClientException $exception): string
    {
        $body = (string) $exception->getResponse()->getBody();

        /** @var array{message?: string}|null $data */
        $data = json_decode($body, true);

        return $data['message'] ?? 'Tokenly API error';
    }
}
