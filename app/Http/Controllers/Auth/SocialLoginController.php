<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SocialLoginController extends Controller
{
    public function redirect(string $provider): RedirectResponse
    {
        $this->ensureSupportedProvider($provider);

        $state = Str::random(40);
        session(["oauth_state_{$provider}" => $state]);

        return redirect()->away($this->authorizationUrl($provider, $state));
    }

    public function callback(Request $request, string $provider): RedirectResponse
    {
        $this->ensureSupportedProvider($provider);

        if ($request->string('state')->toString() !== session()->pull("oauth_state_{$provider}")) {
            throw ValidationException::withMessages([
                'state' => ['소셜 로그인 요청 상태가 올바르지 않습니다.'],
            ]);
        }

        try {
            $profile = $this->fetchProfile($provider, $request->string('code')->toString());
        } catch (RequestException) {
            return redirect($this->frontendUrl('login=provider_error'));
        }

        $user = User::updateOrCreate(
            [
                'provider' => $provider,
                'provider_id' => $profile['id'],
            ],
            [
                'name' => $profile['name'],
                'email' => $profile['email'],
                'password' => Hash::make(Str::password(32)),
                'avatar' => $profile['avatar'],
            ],
        );

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect($this->frontendUrl('login=success'));
    }

    private function authorizationUrl(string $provider, string $state): string
    {
        $config = config("services.{$provider}");

        if ($provider === 'kakao') {
            return 'https://kauth.kakao.com/oauth/authorize?'.http_build_query([
                'response_type' => 'code',
                'client_id' => $config['client_id'],
                'redirect_uri' => $config['redirect'],
                'state' => $state,
            ]);
        }

        return 'https://nid.naver.com/oauth2.0/authorize?'.http_build_query([
            'response_type' => 'code',
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect'],
            'state' => $state,
        ]);
    }

    /**
     * @return array{id: string, name: string, email: string, avatar: ?string}
     */
    private function fetchProfile(string $provider, string $code): array
    {
        $token = $this->fetchAccessToken($provider, $code);

        return $provider === 'kakao'
            ? $this->fetchKakaoProfile($token)
            : $this->fetchNaverProfile($token);
    }

    private function fetchAccessToken(string $provider, string $code): string
    {
        $config = config("services.{$provider}");

        $url = $provider === 'kakao'
            ? 'https://kauth.kakao.com/oauth/token'
            : 'https://nid.naver.com/oauth2.0/token';

        $payload = [
            'grant_type' => 'authorization_code',
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri' => $config['redirect'],
            'code' => $code,
        ];

        if ($provider === 'kakao' && blank($payload['client_secret'])) {
            unset($payload['client_secret']);
        }

        $response = Http::asForm()->post($url, $payload)->throw()->json();

        return $response['access_token'];
    }

    /**
     * @return array{id: string, name: string, email: string, avatar: ?string}
     */
    private function fetchKakaoProfile(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get('https://kapi.kakao.com/v2/user/me')
            ->throw()
            ->json();

        $account = $response['kakao_account'] ?? [];
        $profile = $account['profile'] ?? [];
        $id = (string) $response['id'];

        return [
            'id' => $id,
            'name' => $profile['nickname'] ?? 'kakao-user',
            'email' => $account['email'] ?? "kakao-{$id}@oauth.local",
            'avatar' => $profile['profile_image_url'] ?? null,
        ];
    }

    /**
     * @return array{id: string, name: string, email: string, avatar: ?string}
     */
    private function fetchNaverProfile(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get('https://openapi.naver.com/v1/nid/me')
            ->throw()
            ->json('response');

        $id = (string) $response['id'];

        return [
            'id' => $id,
            'name' => $response['name'] ?? $response['nickname'] ?? 'naver-user',
            'email' => $response['email'] ?? "naver-{$id}@oauth.local",
            'avatar' => $response['profile_image'] ?? null,
        ];
    }

    private function frontendUrl(string $query): string
    {
        return rtrim((string) config('app.frontend_url'), '/')."/?{$query}";
    }

    private function ensureSupportedProvider(string $provider): void
    {
        abort_unless(in_array($provider, ['kakao', 'naver'], true), 404);
    }
}
