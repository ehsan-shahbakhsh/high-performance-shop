<?php

namespace App\Services\Sales\Payment\Strategies;

use App\Contracts\Payment\PaymentStrategyInterface;
use App\Data\Sales\Payment\InitiatePaymentResultData;
use App\Data\Sales\Payment\RefundPaymentResultData;
use App\Data\Sales\Payment\VerifyPaymentResultData;
use App\Enums\Sales\PaymentMethodDriver;
use App\Enums\Sales\TransactionStatus;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Throwable;
use Exception;

class SamanStrategy implements PaymentStrategyInterface
{

    public function initiate(Transaction $transaction): InitiatePaymentResultData
    {
        try {
            $paymentMethod = $transaction->paymentMethod;
            $settings = $paymentMethod->settings ?? [];

            $terminalId = $settings['terminal_id'] ?? throw new Exception('Terminal ID سامان تنظیم نشده است.');
            $timeout = $settings['timeout'] ?? $this->timeout ?? 15;

            $redirectUrl = route('payment.callback', [
                'driver' => PaymentMethodDriver::Saman->value,
                'transaction' => $transaction->id,
            ]);

            $response = Http::timeout((is_string($timeout) && !is_numeric($timeout)) ? 15 : intval($timeout))
                ->acceptJson()
                ->post('https://sep.shaparak.ir/OnlinePG/OnlinePG', [
                    "Action" => "Token",
                    "TerminalId" => $terminalId,
                    "Amount" => $transaction->amount * 10, // تبدیل تومان به ریال
                    "ResNum" => (string)$transaction->id,
                    "RedirectURL" => $redirectUrl,
                    "CellNumber" => $transaction->user->mobile ?? '',
                ]);

            $result = $response->json();

            if ($response->failed() || data_get($result, 'status') != 1) {
                $errorMessage = data_get($result, 'errorDesc', 'خطا در دریافت اطلاعات از درگاه سامان');
                $errorCode = data_get($result, 'errorCode', 'نامشخص');

                return new InitiatePaymentResultData(
                    isSuccessful: false,
                    message: "{$errorMessage} (کد خطا: {$errorCode})"
                );
            }

            $token = data_get($result, 'token', data_get($result, 'data'));

            if (!$token) {
                return new InitiatePaymentResultData(
                    isSuccessful: false,
                    message: 'توکن پرداخت از سمت بانک دریافت نشد.'
                );
            }

            $transaction->update([
                'status' => TransactionStatus::Pending,
                'reference_id' => $token,
            ]);

            return new InitiatePaymentResultData(
                isSuccessful: true,
                redirectUrl: sprintf('https://sep.shaparak.ir/OnlinePG/SendToken?token=%s', $token),
                message: 'در حال انتقال به درگاه سامان...',
                transactionId: $token,
            );

        } catch (Throwable $e) {
            report($e);

            return new InitiatePaymentResultData(
                isSuccessful: false,
                message: 'خطای سیستمی غیرمنتظره در ارتباط با درگاه سامان.',
            );
        }
    }

    public function verify(Transaction $transaction, array $payload = []): VerifyPaymentResultData
    {
        try {
            $state = strtolower($payload['State'] ?? '');
            $status = $payload['Status'] ?? '';

            if ($state !== 'ok' || $status != '2') {
                return new VerifyPaymentResultData(
                    isSuccessful: false,
                    transactionStatus: TransactionStatus::Failed,
                    message: 'پرداخت توسط کاربر لغو شد یا درگاه وضعیت نامعتبر برگرداند.',
                );
            }

            $refNum = $payload['RefNum'] ?? null;
            if (!$refNum) {
                return new VerifyPaymentResultData(
                    isSuccessful: false,
                    transactionStatus: TransactionStatus::Failed,
                    message: 'شماره مرجع (RefNum) از سمت بانک ارسال نشده است.',
                );
            }

            $paymentMethod = $transaction->paymentMethod;
            $settings = $paymentMethod->settings ?? [];

            $terminalId = $settings['terminal_id'] ?? throw new Exception('Terminal ID سامان تنظیم نشده است.');
            $timeout = $settings['timeout'] ?? $this->timeout ?? 15;

            $response = Http::timeout((is_string($timeout) && !is_numeric($timeout)) ? 15 : intval($timeout))
                ->post('https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTransaction', [
                    'RefNum'         => $refNum,
                    'TerminalNumber' => $terminalId,
                ]);

            $result = $response->json();

            if ($response->failed() || !data_get($result, 'Success') || data_get($result, 'ResultCode') != 0) {
                $errorDesc = data_get($result, 'ResultDescription', 'خطا در تایید نهایی تراکنش از سمت سامان.');

                return new VerifyPaymentResultData(
                    isSuccessful: false,
                    transactionStatus: TransactionStatus::Failed,
                    message: $errorDesc,
                    driverPayload: $result,
                );
            }

            $rrn = data_get($result, 'TransactionDetail.RRN', $refNum);

            return new VerifyPaymentResultData(
                isSuccessful: true,
                transactionStatus: TransactionStatus::Successful,
                referenceId: $rrn,
                message: 'پرداخت با موفقیت در درگاه سامان تایید شد.',
                driverPayload: $result,
            );

        } catch (Throwable $e) {
            report($e);

            return new VerifyPaymentResultData(
                isSuccessful: false,
                transactionStatus: TransactionStatus::Failed,
                message: 'خطای سیستمی غیرمنتظره در تایید تراکنش درگاه سامان.',
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