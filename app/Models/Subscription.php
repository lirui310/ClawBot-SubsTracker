<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    /** @use HasFactory<\Database\Factories\SubscriptionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'channel_id',
        'title',
        'content',
        'deadline_at',
        'notify_days',
        'is_active',
        'last_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'deadline_at' => 'date',
            'is_active' => 'boolean',
            'last_sent_at' => 'datetime',
            'notify_days' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /** Whether today falls within the notification window. */
    public function isInNotifyWindow(): bool
    {
        $today = now()->startOfDay();
        $deadline = $this->deadline_at->copy()->startOfDay();

        if ($today->gt($deadline)) {
            return false;
        }

        return $today->gte($deadline->copy()->subDays($this->notify_days));
    }

    /** Calendar days remaining until deadline (0 on deadline day, negative after). */
    public function daysUntilDeadline(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->deadline_at->copy()->startOfDay(), false);
    }
}
