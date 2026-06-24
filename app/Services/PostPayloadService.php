<?php

namespace App\Services;

use App\Models\Post;
use App\Support\PostPolicy;
use Illuminate\Http\Request;

class PostPayloadService
{
    public function __construct(
        private readonly PostAttachmentService $attachments,
        private readonly PostPolicy $policy,
    ) {
    }

    public function toCommunityPost(Post $post, ?Request $request = null): array
    {
        $attachments = collect($post->attachments ?? [])
            ->filter(fn (array $attachment) => isset($attachment['path']) && is_string($attachment['path']) && $attachment['path'] !== '')
            ->map(fn (array $attachment) => $this->withUrl($attachment))
            ->values();

        if ($post->image_path && $attachments->doesntContain('path', $post->image_path)) {
            $attachments->prepend($this->withUrl([
                'path' => $post->image_path,
                'name' => basename($post->image_path),
                'mime' => null,
                'size' => null,
                'isImage' => true,
            ]));
        }

        $imageUrls = $attachments
            ->filter(fn (array $attachment) => $attachment['isImage'])
            ->pluck('url')
            ->values()
            ->all();

        return [
            'id' => $post->id,
            'userId' => $post->user_id,
            'title' => $post->title,
            'excerpt' => $post->content,
            'author' => $post->author ?? 'anonymous',
            'category' => $post->category ?? 'Laravel',
            'imageUrl' => $imageUrls[0] ?? null,
            'imageUrls' => $imageUrls,
            'attachments' => $attachments->all(),
            'tags' => $post->tags ?? [],
            'replies' => $post->replies ?? 0,
            'views' => $post->views ?? 0,
            'createdAt' => $post->created_at?->toIso8601String(),
            'canEdit' => $request ? $this->policy->canManage($request, $post) : false,
            'canDelete' => $request ? $this->policy->canManage($request, $post) : false,
        ];
    }

    private function withUrl(array $attachment): array
    {
        return [
            ...$attachment,
            'url' => $this->attachments->url($attachment['path']),
        ];
    }
}
