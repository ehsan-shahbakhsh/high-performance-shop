<?php

namespace App\Services\Sales\Payment\Strategies;

use App\Contracts\Payment\PaymentStrategyInterface;
use App\Data\Sales\Payment\InitiatePaymentResultData;
use App\Data\Sales\Payment\RefundPaymentResultData;
use App\Data\Sales\Payment\VerifyPaymentResultData;
use App\Enums\Sales\TransactionStatus;
use App\Models\Transaction;

class CashOnDeliveryStrategy implements PaymentStrategyInterface
{
    public function initiate(Transaction $transaction): InitiatePaymentResultData
    {
        $deliveryCode = random_int(10000, 99999);

        $transaction->update([
            'status' => TransactionStatus::PendingDelivery,
            'reference_id' => $deliveryCode,
        ]);

        // TODO
        // DeliveryCodeGenerated::dispatch($transaction);

        return new InitiatePaymentResultData(
            isSuccessful: true,
            message: 'سفارش ثبت شد. لطفاً کد تحویل پیامک شده را به مامور ارائه دهید.',
        );
    }

    public function verify(Transaction $transaction, array $payload = []): VerifyPaymentResultData
    {
        $providedCode = $payload['delivery_code'] ?? null;

        if ($transaction->reference_id && $transaction->reference_id != $providedCode) {
            return new VerifyPaymentResultData(
                isSuccessful: false,
                transactionStatus: TransactionStatus::PendingDelivery,
                message: 'کد تحویل نامعتبر است. لطفاً مجدداً بررسی کنید.',
            );
        }

        $transaction->update(['status' => TransactionStatus::Successful]);

        return new VerifyPaymentResultData(
            isSuccessful: true,
            transactionStatus: TransactionStatus::Successful,
            referenceId: $transaction->reference_id,
            message: 'تحویل کالا و دریافت وجه با موفقیت تایید شد.',
        );
    }

    public function refund(Transaction $transaction, Transaction $refundTransaction): RefundPaymentResultData
    {
        $transaction->update(['status' => TransactionStatus::Canceled]);
        $refundTransaction->update(['status' => TransactionStatus::Successful]);

        return new RefundPaymentResultData(
            isSuccessful: true,
            transactionStatus: TransactionStatus::Successful,
            message: 'تراکنش پرداخت در محل باطل شد.',
            referenceId: $refundTransaction->reference_id,
        );
    }
}
