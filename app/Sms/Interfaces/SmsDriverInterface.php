<?php

namespace App\Sms\Interfaces;

use App\Sms\Exceptions\SmsSendingFailedException;

interface SmsDriverInterface
{
    /**
     * Send a plain text SMS to a single recipient.
     * Suitable for general notifications (e.g., order status).
     *
     * @param string $mobile The recipient's mobile number.
     * @param string $message The content of the message.
     * @return string The message ID (for tracking delivery status).
     * @throws SmsSendingFailedException
     */
    public function send(string $mobile, string $message): string;

    /**
     * Send an SMS using a pre-defined pattern (Lookup/Service).
     * Highly recommended for OTP and high-priority alerts.
     *
     * @param string $mobile The recipient's mobile number.
     * @param string $patternCode The specific pattern code defined in the SMS provider panel.
     * @param array $params An associative array of variables to replace in the pattern (e.g., ['code' => '1234']).
     * @return string The message ID.
     * @throws SmsSendingFailedException
     */
    public function sendPattern(string $mobile, string $patternCode, array $params): string;

    /**
     * Send a single message to multiple recipients (Bulk).
     *
     * @param array $mobiles An array of mobile numbers.
     * @param string $message The content of the message.
     * @return array An array of message IDs.
     * @throws SmsSendingFailedException
     */
    public function sendBulk(array $mobiles, string $message): array;
}