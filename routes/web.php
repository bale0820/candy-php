<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\SocialLoginController;
use App\Http\Controllers\BoardController;
use App\Models\Post;

Route::get('/auth/{provider}/redirect', [SocialLoginController::class, 'redirect'])
    ->whereIn('provider', ['kakao', 'naver']);
Route::get('/auth/{provider}/callback', [SocialLoginController::class, 'callback'])
    ->whereIn('provider', ['kakao', 'naver']);

Route::get('/', [BoardController::class, 'index']);
Route::get('/board/create', [BoardController::class, 'create']);
Route::get('/board', [BoardController::class, 'index']);
Route::get('/board/{id}/edit', [BoardController::class, 'edit']);
Route::post('/board/{id}/update', [BoardController::class, 'update']);
Route::post('/board/{id}/delete', [BoardController::class, 'destroy']);
Route::get('/board/{id}', [BoardController::class, 'show']);
Route::post('/store', [BoardController::class, 'store']);
Route::get('/insert', function () {

    Post::create([
        'title' => '첫 번째 게시글',
        'content' => '라라벨 공부중입니다.'
    ]);

    return '등록 완료';
});
