<?php

namespace App\Services;

use App\Jobs\ProcessOrder;
use Illuminate\Support\Str;

class OrderService
{
    public function submit(int $userId, array $items): string
    {
        $orderRef = Str::uuid()->toString();

        ProcessOrder::dispatch($orderRef, $userId, $items);

        return $orderRef;
    }
}
