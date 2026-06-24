<?php

namespace App\Support;

use App\Models\Post;
use Illuminate\Http\Request;

class PostPolicy
{
    public function canManage(Request $request, Post $post): bool
    {
        $user = $request->user();

        if (! $user) {
            return false;
        }

        return $this->isAdmin($request) || (int) $post->user_id === (int) $user->id;
    }

    public function isAdmin(Request $request): bool
    {
        return (int) $request->user()?->id === 1;
    }
}
