<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Channel extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'bot_token',
        'bot_id',
        'baseurl',
        'poll_buf',
        'webhook_token',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'bot_token' => 'encrypted',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Channel $channel) {
            $channel->webhook_token ??= Str::random(48);
        });

        static::updating(function (Channel $channel) {
            // Only the immutable identity fields are protected; operational fields (poll_buf, is_active, etc.) may change
            if ($channel->isDirty(['user_id', 'bot_token', 'bot_id'])) {
                throw new \Illuminate\Auth\Access\AuthorizationException('Channels cannot be modified once created.');
            }
        });
    }

    /**
     * Get the user that owns the channel.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
