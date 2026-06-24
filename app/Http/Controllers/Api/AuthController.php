<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->toUserPayload($request->user()),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'data' => $this->toUserPayload($user),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, true)) {
            throw ValidationException::withMessages([
                'email' => ['이메일 또는 비밀번호가 올바르지 않습니다.'],
            ]);
        }

        $request->session()->regenerate();

        return response()->json([
            'data' => $this->toUserPayload($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        Auth::forgetGuards();

        return response()->json([
            'message' => 'Logged out.',
        ]);
    }

    private function toUserPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'isAdmin' => (int) $user->id === 1,
        ];
    }
}
