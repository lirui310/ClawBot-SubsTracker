<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GatewayService
{
    private const DEFAULT_BASE_URL = 'https://ilinkai.weixin.qq.com';

    private const CHANNEL_VERSION = '1.0.2';

    /** Build the auth headers required by every iLink API call. */
    private function headers(string $botToken): array
    {
        return [
            'Content-Type' => 'application/json',
            'Authorization' => "Bearer {$botToken}",
            'AuthorizationType' => 'ilink_bot_token',
            // Random uint32 encoded as base64 — prevents replay attacks
            'X-WECHAT-UIN' => base64_encode((string) random_int(0, 4294967295)),
        ];
    }

    /**
     * Get a login QR code from iLink.
     *
     * Returns the raw iLink response. The caller is responsible for converting
     * `qrcode_img_content` (a WeChat scan URL) into a displayable QR image.
     *
     * @return array{qrcode: string, qrcode_img_content: string, baseurl?: string}|null
     */
    public function getQrCode(): ?array
    {
        try {
            $response = Http::timeout(40)->get(self::DEFAULT_BASE_URL.'/ilink/bot/get_bot_qrcode', [
                'bot_type' => 3,
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('iLink QR code request failed', ['status' => $response->status()]);
        } catch (\Exception $e) {
            Log::error('iLink connection failed: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Poll the scan status of a QR code.
     *
     * @return array{status: string, bot_token?: string, baseurl?: string}|null
     */
    public function checkQrStatus(string $qrcode): ?array
    {
        try {
            $response = Http::timeout(10)->get(self::DEFAULT_BASE_URL.'/ilink/bot/get_qrcode_status', [
                'qrcode' => $qrcode,
            ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('iLink status check failed: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Long-poll for inbound messages.
     *
     * @param  string  $pollBuf  Pagination cursor from the previous call ('' on first call)
     * @return array{msgs: array<mixed>, get_updates_buf: string}|null
     */
    public function getUpdates(string $botToken, string $pollBuf, string $baseUrl = self::DEFAULT_BASE_URL): ?array
    {
        try {
            $response = Http::timeout(40)
                ->withHeaders($this->headers($botToken))
                ->post($baseUrl.'/ilink/bot/getupdates', [
                    'get_updates_buf' => $pollBuf,
                    'base_info' => ['channel_version' => self::CHANNEL_VERSION],
                ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('iLink getupdates failed', ['status' => $response->status()]);
        } catch (\Exception $e) {
            Log::error('iLink getupdates error: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Send a text message through a bot.
     *
     * `contextToken` comes from the inbound message that started the conversation.
     * WeChat requires it — sending without it will silently fail.
     */
    public function sendMessage(
        string $botToken,
        string $toUserId,
        string $content,
        string $contextToken,
        string $baseUrl = self::DEFAULT_BASE_URL
    ): bool {
        try {
            $response = Http::timeout(10)
                ->withHeaders($this->headers($botToken))
                ->post($baseUrl.'/ilink/bot/sendmessage', [
                    'msg' => [
                        'from_user_id' => '',
                        'to_user_id' => $toUserId,
                        'client_id' => 'clawbot-'.Str::uuid(),
                        'message_type' => 2,
                        'message_state' => 2,
                        'context_token' => $contextToken,
                        'item_list' => [
                            ['type' => 1, 'text_item' => ['text' => $content]],
                        ],
                    ],
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('iLink sendmessage failed: '.$e->getMessage());
        }

        return false;
    }
}
