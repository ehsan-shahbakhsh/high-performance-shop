<?php

namespace App\Enums\Sales;

enum TransactionStatus: string
{
    case Pending = 'pending';
    case Successful = 'successful';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case Canceled = 'canceled';
    case PendingDelivery = 'pending_delivery';
}
