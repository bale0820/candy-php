<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PostStoreRequest;
use App\Http\Requests\PostUpdateRequest;
use App\Models\Post;
use App\Services\PostAttachmentService;
use App\Services\PostPayloadService;
use App\Support\PostPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class PostController extends Controller
{
    public function __construct(
        private readonly PostAttachmentService $attachments,
        private readonly PostPayloadService $payloads,
        private readonly PostPolicy $policy,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Post::latest()
                ->limit(50)
                ->get()
                ->map(fn (Post $post) => $this->payloads->toCommunityPost($post)),
        ]);
    }

    public function mine(Request $request): JsonResponse
    {
        return response()->json([
            'data' => Post::where('user_id', $request->user()->id)
                ->latest()
                ->limit(50)
                ->get()
                ->map(fn (Post $post) => $this->payloads->toCommunityPost($post, $request)),
        ]);
    }

    public function adminIndex(Request $request): JsonResponse
    {
        if (! $this->policy->isAdmin($request)) {
            return response()->json([
                'message' => '관리자만 접근할 수 있습니다.',
            ], 403);
        }

        return response()->json([
            'data' => Post::latest()
                ->limit(100)
                ->get()
                ->map(fn (Post $post) => $this->payloads->toCommunityPost($post, $request)),
        ]);
    }

    public function store(PostStoreRequest $request): JsonResponse
    {
        try {
            $attachments = $this->attachments->storeMany($request->file('attachments', []));
        } catch (Throwable $exception) {
            report($exception);

            return $this->uploadFailedResponse();
        }

        $firstImage = collect($attachments)->firstWhere('isImage', true);

        $post = Post::create([
            'user_id' => $request->user()->id,
            'title' => $request->validated('title'),
            'content' => $request->validated('excerpt'),
            'image_path' => $firstImage['path'] ?? null,
            'attachments' => $attachments,
            'category' => $request->validated('category'),
            'tags' => $request->validated('tags', []),
            'author' => $request->user()->name,
            'replies' => 0,
            'views' => 1,
        ]);

        return response()->json([
            'data' => $this->payloads->toCommunityPost($post, $request),
        ], 201);
    }

    public function update(PostUpdateRequest $request, Post $post): JsonResponse
    {
        if (! $this->policy->canManage($request, $post)) {
            return response()->json([
                'message' => '본인 게시글만 수정할 수 있습니다.',
            ], 403);
        }

        $attachments = $post->attachments ?? [];
        $imagePath = $post->image_path;
        $removedPaths = [];

        if ($request->has('keepAttachmentPaths') || $request->hasFile('attachments')) {
            $keepPaths = $request->validated('keepAttachmentPaths', []);
            $attachments = $this->attachments->keepExisting($post, $keepPaths);
            $removedPaths = $this->attachments->removedPaths($post, $keepPaths);
        }

        if ($request->hasFile('attachments')) {
            try {
                $attachments = [
                    ...$attachments,
                    ...$this->attachments->storeMany($request->file('attachments', [])),
                ];
            } catch (Throwable $exception) {
                report($exception);

                return $this->uploadFailedResponse();
            }
        }

        $this->attachments->deletePaths($removedPaths);
        $firstImage = collect($attachments)->firstWhere('isImage', true);
        $imagePath = $firstImage['path'] ?? null;

        $post->update([
            'title' => $request->validated('title'),
            'content' => $request->validated('excerpt'),
            'image_path' => $imagePath,
            'attachments' => $attachments,
            'category' => $request->validated('category'),
            'tags' => $request->validated('tags', []),
        ]);

        return response()->json([
            'data' => $this->payloads->toCommunityPost($post->refresh(), $request),
        ]);
    }

    public function destroy(Request $request, Post $post): JsonResponse
    {
        if (! $this->policy->canManage($request, $post)) {
            return response()->json([
                'message' => '본인 게시글만 삭제할 수 있습니다.',
            ], 403);
        }

        $this->attachments->deletePostFiles($post);
        $post->delete();

        return response()->json([
            'message' => '게시글을 삭제했습니다.',
        ]);
    }

    private function uploadFailedResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'S3 파일 업로드에 실패했습니다. AWS 자격 증명, 리전, 버킷 권한을 확인해주세요.',
        ], 500);
    }
}
