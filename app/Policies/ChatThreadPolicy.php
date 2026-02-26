<?php

namespace App\Policies;

use App\Models\ChatThread;
use App\Models\Inquiry;
use App\Models\User;

class ChatThreadPolicy
{
    public function view(User $user, ChatThread $thread): bool
    {
        return (int) $thread->poster_user_id === (int) $user->id
            || (int) $thread->responder_user_id === (int) $user->id;
    }

    public function send(User $user, ChatThread $thread): bool
    {
        if (!$this->view($user, $thread)) {
            return false;
        }

        if ((bool) $thread->is_read_only) {
            return false;
        }

        return (bool) $thread->is_active;
    }

    public function listThreads(User $user, Inquiry $inquiry): bool
    {
        $inquiry->loadMissing('poster');
        $poster = $inquiry->poster;

        if (!$poster || !isset($poster->user_id)) {
            return false;
        }

        return (int) $poster->user_id === (int) $user->id;
    }
}
