<?php

namespace App\Sms\Drivers;

use App\Sms\Interfaces\SmsDriverInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class LogDriver implements SmsDriverInterface
{
    /**
     * @inheritDoc
     */
    public function send(string $mobile, string $message): string
    {
        $id = (string) Str::uuid();
        Log::info("SMS [Simple] -> ID: $id | To: $mobile | Msg: $message");
        return $id;
    }

    /**
     * @inheritDoc
     */
    public function sendPattern(string $mobile, string $patternCode, array $params): string
    {
        $id = (string) Str::uuid();
        Log::info("SMS [Pattern] -> ID: $id | To: $mobile | Pattern: $patternCode | Data: " . json_encode($params));
        return $id;
    }

    /**
     * @inheritDoc
     */
    public function sendBulk(array $mobiles, string $message): array
    {
        $ids = [];
        foreach ($mobiles as $mobile) {
            $ids[$mobile] = $this->send($mobile, $message);
        }
        return $ids;
    }
}