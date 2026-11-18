<?php

namespace App\Http\Controllers\Api\Driver;

use App\Exceptions\ApiException;
use App\Support\DriverJwt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends DriverController
{
    /** POST /api/driver/auth/login — was api/driver/auth/login.php */
    public function login(Request $request): JsonResponse
    {
        $email = $this->requireFilled($request, 'email');
        $password = (string) $this->requireFilled($request, 'password');

        $driver = DB::table('driver_registration')->where('email', $email)->first();
        if (! $driver || ! $this->passwordMatches($password, (string) $driver->password)) {
            throw new ApiException('Invalid email or password');
        }

        // Migrate-on-login: upgrade a remaining plaintext row to bcrypt
        // (the legacy driver logins were patched to verify bcrypt too).
        if (! $this->isBcrypt((string) $driver->password)) {
            DB::table('driver_registration')->where('id', $driver->id)->update(['password' => Hash::make($password)]);
        }

        return $this->respond([
            'result'  => true,
            'message' => 'Login successful',
            'token'   => DriverJwt::encode(['id' => $driver->id, 'phone' => $driver->phone, 'role' => 'driver']),
            'user'    => $this->driverWithRelations($driver),
        ]);
    }

    /** POST /api/driver/auth/register — was api/driver/auth/register.php */
    
public function register(Request $request): JsonResponse
{
    // Validate request data
    $validated = $request->validate([
        'email' => 'required|string|email:rfc|max:255',
        'password' => 'required|string|min:6',
        'phone' => 'nullable|string|max:20',
    ]);

    $email = trim($validated['email']);
    $password = $validated['password'];
    $phone = trim($validated['phone'] ?? '');

    // Check duplicate email
    if (DB::table('driver_registration')->where('email', $email)->exists()) {
        throw new ApiException('Email already registered');
    }

    // Register driver inside database transaction
    $regId = DB::transaction(function () use ($email, $password, $phone) {

        $nextEmp = (int) DB::table('driver_registration')
            ->lockForUpdate()
            ->max('emp_no') + 1;

        $regId = DB::table('driver_registration')->insertGetId([
            'email'               => $email,
            'password'            => Hash::make($password),
            'phone'               => $phone,
            'emp_no'              => $nextEmp,
            'status'              => 'active',
            'verification_status' => 'incomplete',
        ]);

        // Create delivery boy record
        DB::table('delivery_boy')->insert([
            'regid'       => $regId,
            'phone'       => $phone,
            'employee_id' => 'EMP' . str_pad(
                (string) $nextEmp,
                4,
                '0',
                STR_PAD_LEFT
            ),
            'status' => 'active',
        ]);

        return $regId;
    });

    // Fetch registered user
    $user = (array) DB::table('driver_registration')
        ->where('id', $regId)
        ->first();

    // Remove password from response
    unset($user['password']);

    // Generate JWT token and return response
    return $this->respond([
        'result'  => true,
        'message' => 'Registration successful',
        'token'   => DriverJwt::encode([
            'id'    => $regId,
            'phone' => $phone,
            'role'  => 'driver',
        ]),
        'user' => $user,
    ]);
}


    /** Accepts legacy plaintext rows and bcrypt hashes (same rule as the web AuthController). */
    private function passwordMatches(string $given, string $stored): bool
    {
        return $this->isBcrypt($stored)
            ? Hash::check($given, $stored)
            : $stored !== '' && hash_equals($stored, $given);
    }

    private function isBcrypt(string $stored): bool
    {
        return (bool) preg_match('/^\$2[aby]\$/', $stored);
    }
}
