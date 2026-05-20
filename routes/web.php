<?php

use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Volt::route('channels', 'channels.index')->name('channels.index');
    Volt::route('channels/create', 'channels.create')->name('channels.create');
    Volt::route('channels/{channel}', 'channels.show')->name('channels.show');

    Volt::route('subscriptions', 'subscriptions.index')->name('subscriptions.index');
    Volt::route('subscriptions/create', 'subscriptions.create')->name('subscriptions.create');
    Volt::route('subscriptions/{subscription}/edit', 'subscriptions.edit')->name('subscriptions.edit');
});

// Public webhook — URL token IS the auth, no session required; rate-limited to 30/min per token
Route::any('/hook/{token}', [WebhookController::class, 'send'])
    ->middleware('throttle:30,1')
    ->name('channel.webhook');

require __DIR__.'/auth.php';
