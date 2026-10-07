<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * One-time migration of driver_registration.password from plaintext to bcrypt.
 *
 * Run only after the patched legacy logins (api/driver/auth/login.php and
 * appservices/function.php driver_login) are deployed, since the old code
 * compared plaintext and would reject hashed rows. Safe to re-run: rows that
 * are already bcrypt (or empty) are skipped.
 */
class HashDriverPasswords extends Command
{
    protected $signature = 'drivers:hash-passwords {--dry-run : Only report how many rows would change}';

    protected $description = 'Hash remaining plaintext driver passwords with bcrypt';

    public function handle(): int
    {
        $plain = DB::table('driver_registration')
            ->whereNotNull('password')
            ->where('password', '!=', '')
            ->where('password', 'not like', '$2y$%')
            ->where('password', 'not like', '$2a$%')
            ->where('password', 'not like', '$2b$%');

        $count = $plain->clone()->count();

        if ($this->option('dry-run')) {
            $this->info("$count driver password(s) would be hashed.");

            return self::SUCCESS;
        }

        $done = 0;
        $plain->clone()->orderBy('id')->select('id', 'password')->chunkById(100, function ($drivers) use (&$done) {
            foreach ($drivers as $driver) {
                // Guarded by the old value so a password changed mid-run isn't overwritten.
                $done += DB::table('driver_registration')
                    ->where('id', $driver->id)
                    ->where('password', $driver->password)
                    ->update(['password' => Hash::make($driver->password)]);
            }
        });

        $this->info("Hashed $done of $count driver password(s).");

        return self::SUCCESS;
    }
}
