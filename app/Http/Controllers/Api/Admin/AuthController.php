<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ApiException;
use App\Http\Controllers\Api\ApiController;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends ApiController
{
    private const TOKEN_TTL_DAYS = 7;

    /** POST /api/admin/login — email, password; returns a Sanctum token for the admin.* routes */
    public function login(Request $request): JsonResponse
    {
        $email = $this->requireFilled($request, 'email');
        $password = (string) $this->requireFilled($request, 'password');

        $admin = Admin::where('email', $email)->first();
        if (! $admin || ! $this->passwordMatches($password, (string) $admin->password)) {
            throw new ApiException('Invalid email or password');
        }

        $token = $admin->createToken('admin-api', ['admin'], now()->addDays(self::TOKEN_TTL_DAYS));

        return $this->respond([
            'result'     => true,
            'message'    => 'Login successful',
            'token'      => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at?->toDateTimeString(),
            'admin'      => $admin->only(['id', 'name', 'email']),
        ]);
    }

    /** POST /api/admin/logout — revokes the token used for this request */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->respond(['result' => true, 'message' => 'Logged out']);
    }

    /** Same rule as the web AuthController: bcrypt hashes, or legacy plaintext rows. */
    private function passwordMatches(string $given, string $stored): bool
    {
        return preg_match('/^\$2[aby]\$/', $stored)
            ? Hash::check($given, $stored)
            : hash_equals($stored, $given);
    }
}
