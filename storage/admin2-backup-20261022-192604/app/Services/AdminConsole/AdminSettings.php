<?php

namespace App\Services\AdminConsole;

use App\Services\CountryConsole\CountryScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/** Freshful admin console (admin update 1, 22 Oct 2026): the logged-in admin's own profile and password (table admins). */
class AdminSettings
{
    public static function profile(int $id): array
    {
        $a = DB::table('admins')->where('id', $id)->first();
        if (!$a) return ['ok' => false, 'message' => 'Your admin account was not found.'];
        return ['ok' => true, 'profile' => ['id' => (int) $a->id, 'name' => (string) ($a->name ?? ''), 'email' => (string) $a->email, 'address' => (string) ($a->address ?? ''),
            'image' => (string) ($a->image ?? ''), 'image_url' => AdminUploads::url('users', $a->image ?? ''), 'secure' => AdminStaff::isHashed($a->password ?? null)],
            'push' => AdminPush::configured(), 'uploads' => AdminUploads::root() !== null];
    }

    public static function save(int $id, array $in, array $actor): array
    {
        $old = DB::table('admins')->where('id', $id)->first();
        if (!$old) return ['ok' => false, 'message' => 'Your admin account was not found.'];
        $name = trim((string) ($in['name'] ?? '')); $email = strtolower(trim((string) ($in['email'] ?? '')));
        if ($name === '' || mb_strlen($name) > 100) return ['ok' => false, 'message' => 'Enter your name.'];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return ['ok' => false, 'message' => 'Enter a valid email (it is your login).'];
        if (DB::table('admins')->whereRaw('LOWER(email) = ?', [$email])->where('id', '!=', $id)->exists()) return ['ok' => false, 'message' => 'Another admin uses that email.'];
        $v = ['name' => $name, 'email' => $email];
        if (CountryScope::has('admins', 'address')) $v['address'] = mb_substr(trim((string) ($in['address'] ?? '')), 0, 255);
        $img = trim((string) ($in['image'] ?? ''));
        if ($img !== '' && !preg_match('/^[A-Za-z0-9_\-\.]{1,120}$/', $img)) return ['ok' => false, 'message' => 'Upload the photo again.'];
        if (CountryScope::has('admins', 'image')) $v['image'] = $img;
        if (CountryScope::has('admins', 'updated_at')) $v['updated_at'] = date('Y-m-d H:i:s');
        DB::table('admins')->where('id', $id)->update($v);
        AdminAudit::log($actor, 'edit', 'Settings', 'admins', $id, 'Updated own admin profile', $old, DB::table('admins')->where('id', $id)->first());
        return ['ok' => true, 'message' => 'Profile saved.' . ($email !== strtolower((string) $old->email) ? ' Use ' . $email . ' to log in from now on.' : '')];
    }

    public static function password(int $id, string $current, string $new, string $confirm, array $actor): array
    {
        $a = DB::table('admins')->where('id', $id)->first();
        if (!$a) return ['ok' => false, 'message' => 'Your admin account was not found.'];
        $stored = (string) $a->password;
        $okCur = AdminStaff::isHashed($stored) ? Hash::check($current, $stored) : hash_equals($stored, $current);
        if (!$okCur) return ['ok' => false, 'message' => 'Your current password is not correct.'];
        if (mb_strlen($new) < 10) return ['ok' => false, 'message' => 'The new password must be at least 10 characters.'];
        if (!preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) return ['ok' => false, 'message' => 'Use letters and numbers in the new password.'];
        if ($new !== $confirm) return ['ok' => false, 'message' => 'The two new passwords are not the same.'];
        if ($new === $current) return ['ok' => false, 'message' => 'The new password is the same as the current one.'];
        $mode = AdminStaff::passwordMode('admins');
        DB::table('admins')->where('id', $id)->update(['password' => $mode === 'hash' || AdminStaff::isHashed($stored) ? Hash::make($new) : $new]);
        AdminAudit::log($actor, 'password', 'Settings', 'admins', $id, 'Changed own admin password');
        return ['ok' => true, 'message' => 'Password changed. Use the new password next time you log in.'];
    }
}
