<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Services\GatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function send(Request $request, string $token, GatewayService $gateway): JsonResponse
    {
        $channel = Channel::where('webhook_token', $token)->where('is_active', true)->first();

        if (! $channel) {
            return response()->json(['ok' => false, 'error' => 'Invalid token or channel inactive.'], 404);
        }

        $content = trim((string) ($request->input('content') ?? $request->input('msg') ?? ''));

        if ($content === '') {
            return response()->json(['ok' => false, 'error' => 'content (or msg) parameter is required.'], 422);
        }

        if (mb_strlen($content) > 2000) {
            return response()->json(['ok' => false, 'error' => 'content must not exceed 2000 characters.'], 422);
        }

        $lastInbound = $channel->messages()->where('direction', 'inbound')->latest()->first();

        if (! $lastInbound) {
            return response()->json([
                'ok' => false,
                'error' => 'No inbound message found. The WeChat user must send a message first.',
            ], 422);
        }

        $toUser = $lastInbound->metadata['from_user'] ?? '';
        $contextToken = $lastInbound->metadata['context_token'] ?? '';

        $success = $gateway->sendMessage(
            $channel->bot_token,
            $toUser,
            $content,
            $contextToken,
            $channel->baseurl
        );

        if (! $success) {
            return response()->json(['ok' => false, 'error' => 'Failed to send message via iLink.'], 502);
        }

        $channel->messages()->create([
            'content' => $content,
            'direction' => 'outbound',
            'metadata' => ['to_user' => $toUser, 'via' => 'webhook'],
        ]);

        return response()->json(['ok' => true]);
    }
}
