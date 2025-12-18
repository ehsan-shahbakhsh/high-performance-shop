<?php

namespace App\Enums;

enum WalletTransactionTypeEnum: string
{
    case Deposit = 'deposit';
    case Withdraw = 'withdraw';
    case Purchase = 'purchase';
}
