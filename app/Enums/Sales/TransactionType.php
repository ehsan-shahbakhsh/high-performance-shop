<?php

namespace App\Enums\Sales;

enum TransactionType: string
{
    case Payment = 'payment';
    case Refund = 'refund';
}
