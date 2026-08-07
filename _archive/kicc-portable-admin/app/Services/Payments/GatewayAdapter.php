<?php

namespace App\Services\Payments;

interface GatewayAdapter
{
    public function charge(array $params): array;
    public function refund(string $intentId, float $amount, string $reason = ''): array;
    public function status(string $intentId): array;
}
