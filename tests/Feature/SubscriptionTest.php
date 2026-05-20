<?php

use App\Models\Channel;
use App\Models\Subscription;
use App\Models\User;

// ── isInNotifyWindow ──────────────────────────────────────────────────────────

test('subscription is in notify window when today is within notify_days of deadline', function () {
    $sub = Subscription::factory()->make([
        'deadline_at' => now()->addDays(20),
        'notify_days' => 30,
    ]);

    expect($sub->isInNotifyWindow())->toBeTrue();
});

test('subscription is not in notify window before notify_days threshold', function () {
    $sub = Subscription::factory()->make([
        'deadline_at' => now()->addDays(60),
        'notify_days' => 30,
    ]);

    expect($sub->isInNotifyWindow())->toBeFalse();
});

test('subscription is not in notify window after deadline', function () {
    $sub = Subscription::factory()->expired()->make();

    expect($sub->isInNotifyWindow())->toBeFalse();
});

test('subscription is in notify window on deadline day', function () {
    $sub = Subscription::factory()->make([
        'deadline_at' => today(),
        'notify_days' => 1,
    ]);

    expect($sub->isInNotifyWindow())->toBeTrue();
});

// ── daysUntilDeadline ─────────────────────────────────────────────────────────

test('daysUntilDeadline returns correct positive value', function () {
    $sub = Subscription::factory()->make(['deadline_at' => now()->addDays(10)]);

    expect($sub->daysUntilDeadline())->toBe(10);
});

test('daysUntilDeadline returns 0 on deadline day', function () {
    $sub = Subscription::factory()->make(['deadline_at' => today()]);

    expect($sub->daysUntilDeadline())->toBe(0);
});

test('daysUntilDeadline returns negative value after deadline', function () {
    $sub = Subscription::factory()->make(['deadline_at' => now()->subDays(3)]);

    expect($sub->daysUntilDeadline())->toBe(-3);
});

// ── Channel deletion protection ───────────────────────────────────────────────

test('cannot delete channel that has subscriptions', function () {
    $user = User::factory()->create();
    $channel = Channel::factory()->create(['user_id' => $user->id]);
    Subscription::factory()->create(['user_id' => $user->id, 'channel_id' => $channel->id]);

    $this->actingAs($user);

    $response = \Illuminate\Support\Facades\Gate::inspect('delete', $channel);
    expect($response->denied())->toBeTrue();
    expect($response->message())->toContain('消息订阅');
});

test('can delete channel with no subscriptions', function () {
    $user = User::factory()->create();
    $channel = Channel::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user);

    $response = \Illuminate\Support\Facades\Gate::inspect('delete', $channel);
    expect($response->allowed())->toBeTrue();
});

// ── Subscription ownership ────────────────────────────────────────────────────

test('user cannot access another users subscription', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $channel = Channel::factory()->create(['user_id' => $owner->id]);
    $subscription = Subscription::factory()->create(['user_id' => $owner->id, 'channel_id' => $channel->id]);

    $this->actingAs($other)
        ->get(route('subscriptions.edit', $subscription))
        ->assertForbidden();
});
