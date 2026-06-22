<?php

namespace App\Services\Sales\Payment\Strategies;

use App\Contracts\Payment\PaymentStrategyInterface;
use App\Enums\Sales\PaymentMethodDriver;
use App\Enums\Sales\TransactionStatus;
use Illuminate\Support\Facades\Http;
use App\Data\Sales\Payment\{InitiatePaymentResultData, VerifyPaymentResultData, RefundPaymentResultData};
use App\Models\Transaction;
use Exception;
use Throwable;

class ZarinpalStrategy implements PaymentStrategyInterface
{
    private int $timeout = 15;

    public function initiate(Transaction $transaction): InitiatePaymentResultData
    {
        try {
            $paymentMethod = $transaction->paymentMethod;
            $settings = $paymentMethod->settings ?? [];

            $isTest = boolval($settings['test'] ?? '0');
            $merchantId = $settings['merchant_id'] ?? throw new Exception('Merchant ID زرین‌پال تنظیم نشده است.');
            $timeout = $settings['timeout'] ?? $this->timeout;

            $redirectUrl = route('payment.callback', ['driver' => PaymentMethodDriver::Zarinpal->value, 'transaction' => $transaction->id]);

            $response = Http::timeout((is_string($timeout) && !is_numeric($timeout)) ? $this->timeout : intval($timeout))
                ->acceptJson()
                ->withBody(json_encode([
                    'merchant_id' => $merchantId,
                    'amount' => $transaction->amount,
                    'currency' => 'IRT',
                    'callback_url' => $redirectUrl,
                    'description' => "Transaction Id: {$transaction->id}",
                    'metadata' => [
                        'transaction_id' => (string)$transaction->id,
                        'mobile' => $transaction->user->mobile ?? '',
                        'email' => $transaction->user->email ?? '',
                    ],
                ]))
                ->post(sprintf('https://%s.zarinpal.com/pg/v4/payment/request.json', $isTest ? 'sandbox' : 'payment'));

            $result = $response->json();

            if ($response->failed() || data_get($result, 'data.code') != 100) {
                $errorMessage = data_get($result, 'errors.message', 'خطا در دریافت اطلاعات از درگاه بانکی');

                return new InitiatePaymentResultData(
                    isSuccessful: false,
                    message: $errorMessage
                );
            }

            $authority = data_get($result, 'data.authority');

            $transaction->update([
                'status' => TransactionStatus::Pending,
                'reference_id' => $authority,
            ]);

            return new InitiatePaymentResultData(
                isSuccessful: true,
                redirectUrl: "https://payment.zarinpal.com/pg/StartPay/{$authority}",
                message: 'در حال انتقال به درگاه پرداخت...',
                transactionId: $authority,
            );
        } catch (Throwable $e) {
            report($e);

            return new InitiatePaymentResultData(
                isSuccessful: false,
                message: 'خطای سیستمی غیرمنتظره در ارتباط با درگاه.'
            );
        }
    }

    public function verify(Transaction $transaction, array $payload = []): VerifyPaymentResultData
    {
        try {
            if (strtoupper($payload['Status'] ?? '') !== 'OK') {
                return new VerifyPaymentResultData(
                    isSuccessful: false,
                    transactionStatus: TransactionStatus::Failed,
                    message: 'پرداخت توسط کاربر لغو شد یا با خطا مواجه گردید.',
                );
            }

            $authority = $payload['Authority'] ?? null;

            if (!$authority || $transaction->reference_id !== $authority) {
                return new VerifyPaymentResultData(
                    isSuccessful: false,
                    transactionStatus: TransactionStatus::Failed,
                    message: 'توکن پرداخت نامعتبر است یا دستکاری شده است.',
                );
            }

            $paymentMethod = $transaction->paymentMethod;
            $settings = $paymentMethod->settings ?? [];

            $isTest = boolval($settings['test'] ?? '0');
            $merchantId = $settings['merchant_id'] ?? throw new Exception('Merchant ID زرین‌پال تنظیم نشده است.');
            $timeout = $settings['timeout'] ?? $this->timeout;

            $response = Http::timeout((is_string($timeout) && !is_numeric($timeout)) ? $this->timeout : intval($timeout))
                ->withBody(json_encode([
                    'merchant_id' => $merchantId,
                    'amount' => $transaction->amount,
                    'authority' => $authority,
                ]))
                ->post(sprintf('https://%s.zarinpal.com/pg/v4/payment/verify.json', $isTest ? 'sandbox' : 'payment'));

            $result = $response->json();
            $code = data_get($result, 'data.code');

            if ($response->failed() || !in_array($code, [100, 101])) {
                $errorMessage = data_get($result, 'errors.message', 'خطا در تایید پرداخت از سمت بانک.');

                return new VerifyPaymentResultData(
                    isSuccessful: false,
                    transactionStatus: TransactionStatus::Failed,
                    message: $errorMessage,
                    driverPayload: $result,
                );
            }

            return new VerifyPaymentResultData(
                isSuccessful: true,
                transactionStatus: TransactionStatus::Successful,
                referenceId: data_get($result, 'data.ref_id'),
                message: 'پرداخت با موفقیت انجام شد.',
                driverPayload: $result,
            );
        } catch (Throwable $e) {
            report($e);

            return new VerifyPaymentResultData(
                isSuccessful: false,
                transactionStatus: TransactionStatus::Failed,
                message: 'خطای سیستمی در تایید تراکنش.',
            );
        }
    }

    public function refund(Transaction $transaction, Transaction $refundTransaction): RefundPaymentResultData
    {
        // TODO: پیاده‌سازی وب‌سرویس ریفاند در آینده

        $refundTransaction->update(['status' => TransactionStatus::Failed]);

        return new RefundPaymentResultData(
            isSuccessful: false,
            transactionStatus: TransactionStatus::Failed,
            message: 'استرداد وجه خودکار موقتاً غیرفعال است. لطفاً استرداد را به صورت دستی (پایا/کارت‌به‌کارت) انجام دهید.'
        );
    }
}