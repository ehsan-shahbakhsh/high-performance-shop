<?php

namespace App\Services\Sales\Payment\Strategies;

use App\Contracts\Payment\PaymentStrategyInterface;
use App\Enums\Sales\TransactionStatus;
use Illuminate\Support\Facades\DB;
use App\Data\Sales\Payment\{InitiatePaymentResultData, VerifyPaymentResultData, RefundPaymentResultData};
use App\Models\Transaction;
use Throwable;

class WalletStrategy implements PaymentStrategyInterface
{
    public function initiate(Transaction $transaction): InitiatePaymentResultData
    {
        $user = $transaction->user;
        $wallet = $user->wallet;

        if ($wallet->balance < $transaction->amount) {
            return new InitiatePaymentResultData(
                isSuccessful: false,
                message: 'موجودی کیف پول کافی نیست.',
            );
        }

        $wallet->decrement('balance', $transaction->amount);
        $transaction->update(['status' => TransactionStatus::Successful]);

        return new InitiatePaymentResultData(
            isSuccessful: true,
            message: 'مبلغ از کیف پول شما کسر شد.',
        );
    }

    public function verify(Transaction $transaction, array $payload = []): VerifyPaymentResultData
    {
        return new VerifyPaymentResultData(
            isSuccessful: true,
            transactionStatus: TransactionStatus::Successful,
            referenceId: 'W-' . uniqid(),
        );
    }

    public function refund(Transaction $transaction, Transaction $refundTransaction): RefundPaymentResultData
    {
        try {
            DB::transaction(static function () use ($transaction, $refundTransaction) {
                $user = $transaction->user;
                $wallet = $user->wallet;

                $wallet->increment('balance', $refundTransaction->amount);

                $refundTransaction->update([
                    'status' => TransactionStatus::Successful,
                    'reference_id' => 'REF-W-' . uniqid(),
                ]);

                $transaction->update(['status' => TransactionStatus::Refunded]);
            });

            return new RefundPaymentResultData(
                isSuccessful: true,
                transactionStatus: TransactionStatus::Successful,
                message: 'مبلغ با موفقیت به کیف پول کاربر بازگشت داده شد.',
                referenceId: $refundTransaction->reference_id,
            );

        } catch (Throwable $e) {
            report($e);

            return new RefundPaymentResultData(
                isSuccessful: false,
                transactionStatus: TransactionStatus::Failed,
                message: 'خطا در بازگشت وجه به کیف پول. لطفاً با پشتیبانی تماس بگیرید.',
            );
        }
    }
}