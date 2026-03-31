<?php

declare(strict_types=1);

namespace Tokenly\Data;

/**
 * Represents the response from the Tokenly gift tokens endpoint.
 *
 * @phpstan-type GiftTokensResponseArray array{
 *     success: bool,
 *     message: string,
 *     transaction: array{
 *         id: int,
 *         sender_app_id: int,
 *         target_app_code: string,
 *         created_at: string
 *     }
 * }
 */
final readonly class GiftTokensResponse
{
    public function __construct(
        public bool $success,
        public string $message,
        public Transaction $transaction,
    ) {
    }

    /**
     * @param GiftTokensResponseArray $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            success: $data['success'],
            message: $data['message'],
            transaction: Transaction::fromArray($data['transaction']),
        );
    }
}
