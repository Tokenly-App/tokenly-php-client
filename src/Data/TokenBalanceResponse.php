<?php

declare(strict_types=1);

namespace Tokenly\Data;

/**
 * Represents the response from the Tokenly get balance endpoint.
 *
 * @phpstan-type TokenBalanceResponseArray array{
 *     success: bool,
 *     app_id: int,
 *     tokens_sent: int,
 *     tokens_received: int,
 *     balance: int
 * }
 */
final readonly class TokenBalanceResponse
{
    public function __construct(
        public bool $success,
        public int $appId,
        public int $tokensSent,
        public int $tokensReceived,
        public int $balance,
    ) {
    }

    /**
     * @param TokenBalanceResponseArray $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            success: $data['success'],
            appId: $data['app_id'],
            tokensSent: $data['tokens_sent'],
            tokensReceived: $data['tokens_received'],
            balance: $data['balance'],
        );
    }
}
