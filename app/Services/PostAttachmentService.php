<?php

namespace App\Services;

use App\Models\Post;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class PostAttachmentService
{
    public function storeMany(array $files): array
    {
        return collect($files)
            ->filter(fn ($file) => $file instanceof UploadedFile)
            ->map(fn (UploadedFile $file) => $this->store($file))
            ->values()
            ->all();
    }

    public function store(UploadedFile $file): array
    {
        $path = $file->storeAs(
            'posts/'.now()->format('Y/m'),
            Str::uuid().'.'.$file->getClientOriginalExtension(),
            ['disk' => 's3'],
        );

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('S3 upload failed.');
        }

        return [
            'path' => $path,
            'name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'isImage' => str_starts_with((string) $file->getMimeType(), 'image/'),
        ];
    }

    public function deletePostFiles(Post $post): void
    {
        $this->pathsFor($post)
            ->unique()
            ->each(fn (string $path) => Storage::disk('s3')->delete($path));
    }

    public function deletePaths(iterable $paths): void
    {
        collect($paths)
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->unique()
            ->each(fn (string $path) => Storage::disk('s3')->delete($path));
    }

    public function keepExisting(Post $post, array $keepPaths): array
    {
        $keep = collect($keepPaths)->flip();

        return collect($post->attachments ?? [])
            ->filter(fn (array $attachment) => isset($attachment['path']) && $keep->has($attachment['path']))
            ->values()
            ->all();
    }

    public function removedPaths(Post $post, array $keepPaths): array
    {
        $keep = collect($keepPaths)->flip();

        return collect($post->attachments ?? [])
            ->pluck('path')
            ->filter(fn ($path) => is_string($path) && $path !== '' && ! $keep->has($path))
            ->values()
            ->all();
    }

    public function url(string $path): string
    {
        $diskConfig = config('filesystems.disks.s3', []);
        $bucket = $diskConfig['bucket'] ?? 'php-community';
        $region = $diskConfig['region'] ?? 'us-east-1';

        return "https://{$bucket}.s3.{$region}.amazonaws.com/".ltrim($path, '/');
    }

    private function pathsFor(Post $post): Collection
    {
        $paths = collect($post->attachments ?? [])
            ->pluck('path')
            ->filter(fn ($path) => is_string($path) && $path !== '')
            ->values();

        if ($post->image_path) {
            $paths->push($post->image_path);
        }

        return $paths;
    }
}
