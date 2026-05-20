<?php

namespace App\Policies;

use App\Models\Channel;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ChannelPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Channel $channel): bool
    {
        return $user->id === $channel->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->channels()->count() < 3;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Channel $channel): bool
    {
        return false; // Channels are immutable
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Channel $channel): Response
    {
        if ($user->id !== $channel->user_id) {
            return Response::deny('无权删除此通道。');
        }

        if ($channel->subscriptions()->exists()) {
            return Response::deny('请先删除该通道下的所有消息订阅，再删除通道。');
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Channel $channel): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Channel $channel): bool
    {
        return false;
    }
}
