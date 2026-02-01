<?php

namespace App\Enums;

enum WalletTransactionType: string
{
    case Deposit = 'deposit';
    case Withdraw = 'withdraw';
    case Purchase = 'purchase';
}
