<?php

namespace App\Support;

final class PassportKeyPermissions
{
    public static function fix(): void
    {
        $privateKey = storage_path('oauth-private.key');
        $publicKey = storage_path('oauth-public.key');

        if (is_file($privateKey)) {
            chmod($privateKey, 0600);
        }

        if (is_file($publicKey)) {
            chmod($publicKey, 0640);
        }
    }
}
