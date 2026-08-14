<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Generates the one-time passwords handed to users who have never set their own.
 *
 * Every account gets its OWN value. A single shared default — as the Moodle
 * importer originally used — means one leaked string opens every account that
 * has not logged in yet, and there is no way to revoke it per user.
 *
 * The alphabet deliberately drops characters that are misread when a password is
 * dictated over the phone or copied off a printout (0/O, 1/l/I, 5/S, 2/Z), since
 * these are read aloud by admins far more often than they are pasted.
 */
final class OneTimePassword
{
    private const ALPHABET = 'ABCDEFGHJKMNPQRTUVWXYabcdefghjkmnpqrtuvwxy346789';

    /**
     * A random one-time password. Length 12 keeps ~68 bits of entropy even with
     * the reduced alphabet, which is far beyond what a login form can be brute
     * forced for, while staying short enough to dictate.
     */
    public static function generate(int $length = 12): string
    {
        $alphabet = self::ALPHABET;
        $max = strlen($alphabet) - 1;

        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, $max)];
        }

        // Guarantee the mix Laravel's default password rules expect, rather than
        // trusting randomness to have produced one.
        if (! preg_match('/[a-z]/', $password) || ! preg_match('/[A-Z]/', $password) || ! preg_match('/\d/', $password)) {
            return self::generate($length);
        }

        return $password;
    }

    /**
     * The shared default the Moodle importer used before per-user passwords.
     * Kept only so the rotation command can recognise accounts still on it.
     */
    public static function legacySharedDefault(): string
    {
        return 'ChangeMe@2026';
    }
}
