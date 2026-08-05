<?php

namespace App\Data\Sales;

use Spatie\LaravelData\Data;

class TransactionGatewayPayloadData extends Data
{
    public function __construct(
        public ?array $request = null,
        public ?array $callback = null,
        public ?array $verify = null,
    )
    {
    }
}
