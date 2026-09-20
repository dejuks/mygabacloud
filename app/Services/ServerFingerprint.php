<?php

namespace App\Services;

/**
 * Builds a fingerprint from characteristics of the server this code is
 * running on. This is NOT a security boundary against a determined attacker
 * with server access — anyone who can read this file can read the
 * fingerprint logic and forge a match. It's the same "soft" deterrent every
 * self-hosted PHP script uses: it catches casual copying (someone dragging
 * the folder onto three other domains without thinking about it), not a
 * targeted attempt to bypass it.
 */
class ServerFingerprint
{
    public function components(): array
    {
        return [
            'hostname' => gethostname() ?: 'unknown-host',
            'install_path' => base_path(),
            'os' => PHP_OS,
        ];
    }

    public function hash(): string
    {
        $components = $this->components();
        ksort($components);

        return hash('sha256', implode('|', $components));
    }
}
