<?php
declare(strict_types=1);

namespace Nemr\Apex\Controllers;

use Nemr\Apex\Database\QueryBuilder;
use Nemr\Apex\Models\Subscription;
use PDO;

class SubscriptionController
{
    public function __construct(protected PDO $pdo) {}

    public function index(array $request): array
    {
        $rows = QueryBuilder::table('subscriptions', $this->pdo)
            ->orderBy('id', 'DESC')
            ->limit(50)
            ->get();

        return [
            'status' => 'success',
            'data' => array_map(fn($r) => Subscription::fromArray($r)->toArray(), $rows)
        ];
    }

    public function show(array $request, array $params): array
    {
        $id = (int)($params['id'] ?? 0);
        $row = QueryBuilder::table('subscriptions', $this->pdo)
            ->where('id', '=', $id)
            ->first();

        if (!$row) {
            http_response_code(404);
            return ['status' => 'error', 'message' => 'Subscription not found'];
        }

        return ['status' => 'success', 'data' => Subscription::fromArray($row)->toArray()];
    }

    public function store(array $request): array
    {
        $body = $request['body'] ?? [];
        $data = [
            'user_id' => $body['user_id'] ?? '',
            'plan' => $body['plan'] ?? '',
            'price' => $body['price'] ?? '',
            'is_active' => $body['is_active'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ];

        $newId = QueryBuilder::table('subscriptions', $this->pdo)->insert($data);

        http_response_code(201);
        return [
            'status' => 'success',
            'message' => 'Subscription created successfully',
            'id' => $newId
        ];
    }
}
