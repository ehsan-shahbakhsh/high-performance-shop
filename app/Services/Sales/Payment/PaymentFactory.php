<?php

namespace App\Services\Sales\Payment;

use App\Contracts\Payment\PaymentStrategyInterface;
use App\Enums\Sales\PaymentMethodDriver;
use App\Services\Sales\Payment\Strategies\{
    CardToCardStrategy,
    CashOnDeliveryStrategy,
    SamanStrategy,
    WalletStrategy,
    ZarinpalStrategy,
};

class PaymentFactory
{
    public static function make(PaymentMethodDriver $driver): PaymentStrategyInterface
    {
        return match ($driver) {
            PaymentMethodDriver::Zarinpal => resolve(ZarinpalStrategy::class),
            PaymentMethodDriver::Saman => resolve(SamanStrategy::class),
            PaymentMethodDriver::Wallet => resolve(WalletStrategy::class),
            PaymentMethodDriver::Cod => resolve(CashOnDeliveryStrategy::class),
            PaymentMethodDriver::CardToCard => resolve(CardToCardStrategy::class),
        };
    }
}
