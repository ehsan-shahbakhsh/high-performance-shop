<?php

namespace App\Services\Sales\Payment\Strategies;

use App\Contracts\Payment\PaymentStrategyInterface;
use App\Data\Sales\Payment\InitiatePaymentResultData;
use App\Data\Sales\Payment\RefundPaymentResultData;
use App\Data\Sales\Payment\VerifyPaymentResultData;
use App\Enums\Sales\TransactionStatus;
use App\Models\Transaction;
use Throwable;

class CardToCardStrategy implements PaymentStrategyInterface
{
    public function initiate(Transaction $transaction): InitiatePaymentResultData
    {
        try {
            $paymentMethod = $transaction->paymentMethod;
            $settings = $paymentMethod->settings ?? [];

            $cardNumber = $settings['card_number'] ?? 'نامشخص';
            $accountName = $settings['account_name'] ?? 'فروشگاه';
            $shabaNumber = $settings['shaba_number'] ?? null;

            $transaction->update(['status' => TransactionStatus::Pending]);

            $shabaText = $shabaNumber ? " یا شماره شبای {$shabaNumber}" : "";

            return new InitiatePaymentResultData(
                isSuccessful: true,
                message: "لطفاً مبلغ فاکتور را به شماره کارت {$cardNumber}{$shabaText} به نام {$accountName} واریز نموده و اطلاعات فیش را ثبت کنید.",
            // redirectUrl: route('frontend.receipt.upload', ['transaction' => $transaction->id])
            );

        } catch (Throwable $e) {
            report($e);

            return new InitiatePaymentResultData(
                isSuccessful: false,
                message: 'خطای سیستمی در فراخوانی اطلاعات حساب بانکی.',
            );
        }
    }

    public function verify(Transaction $transaction, array $payload = []): VerifyPaymentResultData
    {
        try {
            $receiptNumber = $payload['receipt_number'] ?? null;
            $receiptDate = $payload['receipt_date'] ?? null;

            if (!$receiptNumber) {
                return new VerifyPaymentResultData(
                    isSuccessful: false,
                    transactionStatus: TransactionStatus::Pending,
                    message: 'وارد کردن شماره پیگیری فیش واریزی الزامی است.',
                );
            }

            $transaction->update([
                'status' => TransactionStatus::Pending,
                'reference_id' => $receiptNumber,
                'paid_at' => now(),
            ]);

            return new VerifyPaymentResultData(
                isSuccessful: true,
                transactionStatus: TransactionStatus::Pending,
                referenceId: $receiptNumber,
                message: 'فیش واریزی شما با موفقیت ثبت شد و پس از تایید واحد مالی، سفارش شما قطعی خواهد شد.',
            );

        } catch (Throwable $e) {
            report($e);

            return new VerifyPaymentResultData(
                isSuccessful: false,
                transactionStatus: TransactionStatus::Failed,
                message: 'خطای سیستمی در ثبت اطلاعات فیش بانکی.',
            );
        }
    }

    public function refund(Transaction $transaction, Transaction $refundTransaction): RefundPaymentResultData
    {
        $refundTransaction->update(['status' => TransactionStatus::Failed]);

        return new RefundPaymentResultData(
            isSuccessful: false,
            transactionStatus: TransactionStatus::Failed,
            message: 'استرداد وجه برای روش کارت‌به‌کارت باید به صورت دستی (حواله پایا/ساتنا) توسط واحد مالی انجام شود.',
        );
    }
}