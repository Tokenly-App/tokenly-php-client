<?php

declare(strict_types=1);

namespace Tokenly\Data;

/**
 * Represents a transaction returned by the Tokenly gift endpoint.
 *
 * @phpstan-type TransactionArray array{
 *     id: int,
 *     sender_app_id: int,
 *     target_app_code: string,
 *     created_at: string
 * }
 */
final readonly class Transaction
{
    public function __construct(
        public int $id,
        public int $senderAppId,
        public string $targetAppCode,
        public string $createdAt,
    ) {
    }

    /**
     * @param TransactionArray $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            senderAppId: $data['sender_app_id'],
            targetAppCode: $data['target_app_code'],
            createdAt: $data['created_at'],
        );
    }
}
