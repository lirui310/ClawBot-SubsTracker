<?php

use App\Models\Channel;
use App\Models\User;
use Livewire\Volt\Volt;

test('authenticated user can view channels index', function () {
    $user = User::factory()->create();
    $channel = Channel::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('channels.index'))
        ->assertOk()
        ->assertSee($channel->name);
});

test('user cannot view another users channel', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $channel = Channel::factory()->create(['user_id' => $user1->id]);

    $this->actingAs($user2)
        ->get(route('channels.show', $channel))
        ->assertForbidden();
});

test('user cannot have more than 3 channels', function () {
    $user = User::factory()->create();
    Channel::factory()->count(3)->create(['user_id' => $user->id]);

    $this->actingAs($user);

    $this->assertTrue($user->channels()->count() === 3);

    // Verify UI prevents creation
    Volt::test('channels.index')
        ->assertSee('已达到最大通道数');
});

test('channel identity fields are immutable', function () {
    $user = User::factory()->create();
    $channel = Channel::factory()->create(['user_id' => $user->id]);

    $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
    $this->expectExceptionMessage('Channels cannot be modified once created.');

    // bot_token is a core identity field that must never change after creation
    $channel->update(['bot_token' => 'new-token']);
});

test('deleting a channel removes all associated messages', function () {
    $user = User::factory()->create();
    $channel = Channel::factory()->create(['user_id' => $user->id]);
    $messages = \App\Models\Message::factory()->count(5)->create(['channel_id' => $channel->id]);

    $this->assertDatabaseCount('messages', 5);

    $this->actingAs($user);

    Volt::test('channels.index')
        ->call('delete', $channel);

    $this->assertDatabaseMissing('channels', ['id' => $channel->id]);
    $this->assertDatabaseCount('messages', 0);
});
