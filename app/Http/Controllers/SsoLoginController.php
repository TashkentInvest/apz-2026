<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SsoLoginController extends Controller
{
    public function __invoke(Request $request)
    {
        $payload = $this->verifiedPayload($request);
        if (! $payload) {
            abort(Response::HTTP_UNAUTHORIZED, 'Invalid SSO token.');
        }

        $userData = $payload['user'] ?? [];
        $email = $userData['email'] ?? ('sso-'.$userData['id'].'@apps.local');
        $pinfl = $userData['pinfl'] ?? null;

        $query = User::query()->where('email', $email);
        if ($pinfl && Schema::hasColumn('users', 'pinfl')) {
            $query->orWhere('pinfl', $pinfl);
        }

        $user = $query->first() ?: new User();
        $user->forceFill($this->userAttributes($userData, $email))->save();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(env('MODULE_SSO_REDIRECT_PATH', '/home'));
    }

    private function verifiedPayload(Request $request): ?array
    {
        $encoded = (string) $request->query('sso_payload', '');
        $signature = (string) $request->query('sso_signature', '');
        $secret = (string) env('MODULE_SSO_SECRET', '');

        if ($encoded === '' || $signature === '' || $secret === '') {
            return null;
        }

        $expected = rtrim(strtr(base64_encode(hash_hmac('sha256', $encoded, $secret, true)), '+/', '-_'), '=');
        if (! hash_equals($expected, $signature)) {
            return null;
        }

        $payload = json_decode(base64_decode(strtr($encoded, '-_', '+/')), true);
        if (! is_array($payload)) {
            return null;
        }

        if (($payload['iss'] ?? null) !== env('MODULE_SSO_ISSUER', 'apps.toshkentinvest.uz')) {
            return null;
        }

        if (($payload['aud'] ?? null) !== env('MODULE_SSO_AUDIENCE', $request->getHost())) {
            return null;
        }

        if ((int) ($payload['exp'] ?? 0) < time()) {
            return null;
        }

        return $payload;
    }

    private function userAttributes(array $userData, string $email): array
    {
        $attrs = [
            'name' => $userData['name'] ?? $email,
            'email' => $email,
            'password' => Hash::make(Str::random(40)),
        ];

        foreach (['pinfl', 'inn', 'organization', 'position'] as $field) {
            if (Schema::hasColumn('users', $field) && ! empty($userData[$field])) {
                $attrs[$field] = $userData[$field];
            }
        }

        if (Schema::hasColumn('users', 'pinfl') && empty($attrs['pinfl'])) {
            $attrs['pinfl'] = str_pad((string) ($userData['id'] ?? random_int(1, 99999999999999)), 14, '0', STR_PAD_LEFT);
        }

        if (Schema::hasColumn('users', 'role')) {
            $role = $userData['role'] ?? null;
            $attrs['role'] = in_array($role, ['admin', 'consumer', 'user'], true)
                ? $role
                : env('MODULE_SSO_DEFAULT_ROLE', 'user');
        }

        if (Schema::hasColumn('users', 'status')) {
            $attrs['status'] = env('MODULE_SSO_DEFAULT_STATUS', 'approved');
        }

        if (Schema::hasColumn('users', 'email_verified_at')) {
            $attrs['email_verified_at'] = now();
        }

        return $attrs;
    }
}
