<?php

namespace App\Contracts\Payment;

use App\Models\Transaction;
use App\Data\Sales\Payment\{InitiatePaymentResultData, VerifyPaymentResultData, RefundPaymentResultData};

interface PaymentStrategyInterface
{
    /**
     * @param Transaction $transaction
     * @return InitiatePaymentResultData
     */
    public function initiate(Transaction $transaction): InitiatePaymentResultData;

    /**
     * @param Transaction $transaction
     * @param array $payload
     * @return VerifyPaymentResultData
     */
    public function verify(Transaction $transaction, array $payload = []): VerifyPaymentResultData;

    /**
     * @param Transaction $transaction
     * @param Transaction $refundTransaction
     * @return RefundPaymentResultData
     */
    public function refund(Transaction $transaction, Transaction $refundTransaction): RefundPaymentResultData;
}