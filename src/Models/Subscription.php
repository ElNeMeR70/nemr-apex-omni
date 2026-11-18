<?php
declare(strict_types=1);

namespace Nemr\Apex\Models;

/**
 * Modern PHP 8.3 Immutable Entity Model
 */
readonly class Subscription
{
    public function __construct(
        public readonly int $id,
        public readonly int $user_id,
        public readonly string $plan,
        public readonly float $price,
        public readonly bool $is_active,
        public readonly string $createdAt,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (int)($data['id'] ?? 0),
            user_id: $data['user_id'],
            plan: $data['plan'],
            price: $data['price'],
            is_active: $data['is_active'],,
            createdAt: $data['created_at'] ?? date('Y-m-d H:i:s')
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'plan' => $this->plan,
            'price' => $this->price,
            'is_active' => $this->is_active,,
            'created_at' => $this->createdAt,
        ];
    }
}
