<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommunityPostApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'filesystems.default' => 's3',
            'filesystems.disks.s3.bucket' => 'php-community',
            'filesystems.disks.s3.region' => 'ap-northeast-2',
            'filesystems.disks.s3.url' => null,
            'filesystems.disks.s3.throw' => true,
        ]);
    }

    public function test_posts_can_be_listed_for_the_vue_frontend(): void
    {
        Post::create([
            'title' => 'Vue와 Laravel 연결 질문',
            'content' => 'Vite proxy로 Laravel API를 연결하고 싶습니다.',
            'category' => 'Vue',
            'tags' => ['vue', 'laravel'],
            'author' => 'tester',
            'replies' => 2,
            'views' => 10,
        ]);

        $response = $this->getJson('/api/posts');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Vue와 Laravel 연결 질문')
            ->assertJsonPath('data.0.excerpt', 'Vite proxy로 Laravel API를 연결하고 싶습니다.')
            ->assertJsonPath('data.0.category', 'Vue')
            ->assertJsonPath('data.0.tags.0', 'vue')
            ->assertJsonPath('data.0.imageUrl', null)
            ->assertJsonPath('data.0.attachments', [])
            ->assertJsonPath('data.0.canEdit', false)
            ->assertJsonPath('data.0.canDelete', false);
    }

    public function test_guest_cannot_create_posts(): void
    {
        $this->postJson('/api/posts', [
            'title' => 'PostgreSQL 인덱스 질문',
            'excerpt' => 'EXPLAIN 결과를 어떻게 읽으면 좋을까요?',
            'category' => 'Database',
            'tags' => ['postgresql', 'index'],
        ])->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_posts(): void
    {
        $user = User::factory()->create([
            'name' => 'session-dev',
        ]);

        $response = $this->actingAs($user)->postJson('/api/posts', [
            'title' => 'PostgreSQL 인덱스 질문',
            'excerpt' => 'EXPLAIN 결과를 어떻게 읽으면 좋을까요?',
            'category' => 'Database',
            'tags' => ['postgresql', 'index'],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.title', 'PostgreSQL 인덱스 질문')
            ->assertJsonPath('data.category', 'Database')
            ->assertJsonPath('data.author', 'session-dev')
            ->assertJsonPath('data.userId', $user->id)
            ->assertJsonPath('data.canEdit', true);

        $this->assertDatabaseHas('posts', [
            'title' => 'PostgreSQL 인덱스 질문',
            'category' => 'Database',
            'author' => 'session-dev',
            'user_id' => $user->id,
        ]);
    }

    public function test_authenticated_user_can_upload_multiple_attachments_to_s3(): void
    {
        Storage::fake('s3');

        $user = User::factory()->create([
            'name' => 'file-dev',
        ]);

        $response = $this->actingAs($user)->post('/api/posts', [
            'title' => '파일 업로드 테스트',
            'excerpt' => '이미지와 일반 파일을 함께 업로드합니다.',
            'category' => 'Vue',
            'tags' => ['upload', 's3'],
            'attachments' => [
                $this->smallPngUpload(),
                UploadedFile::fake()->create('notes.pdf', 128, 'application/pdf'),
            ],
        ], [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.title', '파일 업로드 테스트')
            ->assertJsonPath('data.author', 'file-dev')
            ->assertJsonPath('data.attachments.0.isImage', true)
            ->assertJsonPath('data.attachments.1.isImage', false)
            ->assertJson(fn ($json) => $json
                ->whereType('data.imageUrl', 'string')
                ->whereType('data.imageUrls.0', 'string')
                ->whereType('data.attachments.0.url', 'string')
                ->whereType('data.attachments.1.url', 'string')
                ->etc());

        $post = Post::firstOrFail();

        $this->assertCount(2, $post->attachments);
        Storage::disk('s3')->assertExists($post->attachments[0]['path']);
        Storage::disk('s3')->assertExists($post->attachments[1]['path']);
    }

    public function test_user_can_list_only_their_posts(): void
    {
        $owner = User::factory()->create(['name' => 'owner']);
        $other = User::factory()->create(['name' => 'other']);

        Post::create($this->postPayload($owner, '내 글'));
        Post::create($this->postPayload($other, '남의 글'));

        $this->actingAs($owner)
            ->getJson('/api/posts/my')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', '내 글')
            ->assertJsonPath('data.0.canEdit', true);
    }

    public function test_owner_can_update_and_delete_their_post(): void
    {
        Storage::fake('s3');

        $owner = User::factory()->create(['name' => 'owner']);
        $post = Post::create($this->postPayload($owner, '수정 전'));

        $this->actingAs($owner)
            ->putJson("/api/posts/{$post->id}", [
                'title' => '수정 후',
                'excerpt' => '수정한 내용입니다.',
                'category' => 'Laravel',
                'tags' => ['edit'],
            ])
            ->assertOk()
            ->assertJsonPath('data.title', '수정 후');

        $this->actingAs($owner)
            ->deleteJson("/api/posts/{$post->id}")
            ->assertOk();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_owner_can_keep_remove_and_add_attachments_when_updating_post(): void
    {
        Storage::fake('s3');

        $owner = User::factory()->create(['name' => 'owner']);
        Storage::disk('s3')->put('posts/old-image.png', 'old image');
        Storage::disk('s3')->put('posts/old-file.pdf', 'old file');

        $post = Post::create([
            ...$this->postPayload($owner, '첨부 수정 전'),
            'image_path' => 'posts/old-image.png',
            'attachments' => [
                [
                    'path' => 'posts/old-image.png',
                    'name' => 'old-image.png',
                    'mime' => 'image/png',
                    'size' => 9,
                    'isImage' => true,
                ],
                [
                    'path' => 'posts/old-file.pdf',
                    'name' => 'old-file.pdf',
                    'mime' => 'application/pdf',
                    'size' => 8,
                    'isImage' => false,
                ],
            ],
        ]);

        $response = $this->actingAs($owner)->post("/api/posts/{$post->id}", [
            '_method' => 'PUT',
            'title' => '첨부 수정 후',
            'excerpt' => '기존 파일을 유지하고 새 파일을 추가합니다.',
            'category' => 'Vue',
            'tags' => ['keep', 'add'],
            'keepAttachmentPaths' => ['posts/old-image.png'],
            'attachments' => [
                $this->smallPngUpload(),
                UploadedFile::fake()->create('new-notes.pdf', 128, 'application/pdf'),
            ],
        ], [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.title', '첨부 수정 후')
            ->assertJsonPath('data.attachments.0.isImage', true)
            ->assertJsonPath('data.attachments.1.isImage', true)
            ->assertJsonPath('data.attachments.2.isImage', false);

        $post->refresh();

        Storage::disk('s3')->assertExists('posts/old-image.png');
        Storage::disk('s3')->assertMissing('posts/old-file.pdf');
        Storage::disk('s3')->assertExists($post->attachments[1]['path']);
        Storage::disk('s3')->assertExists($post->attachments[2]['path']);
        $this->assertCount(3, $post->attachments);
        $this->assertSame($post->attachments[0]['path'], $post->image_path);
    }

    public function test_deleting_post_removes_s3_attachments(): void
    {
        Storage::fake('s3');

        $owner = User::factory()->create(['name' => 'owner']);
        Storage::disk('s3')->put('posts/delete-image.png', 'image');
        Storage::disk('s3')->put('posts/delete-file.pdf', 'file');

        $post = Post::create([
            ...$this->postPayload($owner, '첨부 삭제 대상'),
            'image_path' => 'posts/delete-image.png',
            'attachments' => [
                [
                    'path' => 'posts/delete-image.png',
                    'name' => 'delete-image.png',
                    'mime' => 'image/png',
                    'size' => 5,
                    'isImage' => true,
                ],
                [
                    'path' => 'posts/delete-file.pdf',
                    'name' => 'delete-file.pdf',
                    'mime' => 'application/pdf',
                    'size' => 4,
                    'isImage' => false,
                ],
            ],
        ]);

        $this->actingAs($owner)
            ->deleteJson("/api/posts/{$post->id}")
            ->assertOk();

        Storage::disk('s3')->assertMissing('posts/delete-image.png');
        Storage::disk('s3')->assertMissing('posts/delete-file.pdf');
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_other_user_cannot_update_or_delete_post(): void
    {
        $owner = User::factory()->create(['name' => 'owner']);
        $other = User::factory()->create(['name' => 'other']);
        $post = Post::create($this->postPayload($owner, '남의 글'));

        $this->actingAs($other)
            ->putJson("/api/posts/{$post->id}", [
                'title' => '해킹',
                'excerpt' => '권한 없음',
                'category' => 'Vue',
                'tags' => [],
            ])
            ->assertForbidden();

        $this->actingAs($other)
            ->deleteJson("/api/posts/{$post->id}")
            ->assertForbidden();
    }

    public function test_admin_user_id_one_can_list_and_delete_any_post(): void
    {
        Storage::fake('s3');

        $admin = User::factory()->create(['id' => 1, 'name' => 'admin']);
        $owner = User::factory()->create(['name' => 'owner']);
        $post = Post::create($this->postPayload($owner, '삭제 대상'));

        $this->actingAs($admin)
            ->getJson('/api/admin/posts')
            ->assertOk()
            ->assertJsonPath('data.0.title', '삭제 대상')
            ->assertJsonPath('data.0.canDelete', true);

        $this->actingAs($admin)
            ->deleteJson("/api/posts/{$post->id}")
            ->assertOk();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    private function postPayload(User $user, string $title): array
    {
        return [
            'user_id' => $user->id,
            'title' => $title,
            'content' => '본문입니다.',
            'category' => 'Laravel',
            'tags' => ['laravel'],
            'author' => $user->name,
            'replies' => 0,
            'views' => 1,
        ];
    }

    private function smallPngUpload(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'post-image-');

        file_put_contents($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='
        ));

        return new UploadedFile($path, 'post.png', 'image/png', null, true);
    }
}
