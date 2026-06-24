<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PostController;
use Illuminate\Support\Facades\Route;

Route::get('/auth/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::get('/posts', [PostController::class, 'index']);
Route::get('/posts/my', [PostController::class, 'mine'])->middleware('auth:sanctum');
Route::get('/admin/posts', [PostController::class, 'adminIndex'])->middleware('auth:sanctum');
Route::post('/posts', [PostController::class, 'store'])->middleware('auth:sanctum');
Route::put('/posts/{post}', [PostController::class, 'update'])->middleware('auth:sanctum');
Route::delete('/posts/{post}', [PostController::class, 'destroy'])->middleware('auth:sanctum');
