<?php

namespace App\Services;

use App\Models\DatabaseHost;
use App\Models\ServerDatabase;
use Illuminate\Support\Facades\Cache;

/**
 * Single sign-on ke phpMyAdmin. Panel bikin token acak sekali pakai (berlaku
 * 60 detik) dan mengarahkan user ke skrip signon di phpMyAdmin. Skrip itu
 * menukar token ke Panel lewat /api/pma/redeem (server ke server, dijaga
 * secret bersama), jadi password nggak pernah lewat URL atau browser.
 */
class PhpMyAdminSignon
{
    public const TTL = 60;

    public function enabled(): bool
    {
        return filled(config('app.phpmyadmin_url')) && filled(config('app.phpmyadmin_signon_secret'));
    }

    public function urlForDatabase(ServerDatabase $database): string
    {
        return $this->issue(['type' => 'database', 'id' => $database->id]);
    }

    public function urlForHost(DatabaseHost $host): string
    {
        return $this->issue(['type' => 'host', 'id' => $host->id]);
    }

    private function issue(array $payload): string
    {
        $token = bin2hex(random_bytes(32));
        Cache::put("pma-signon:{$token}", $payload, self::TTL);

        return rtrim((string) config('app.phpmyadmin_url'), '/').'/dockpanel-signon.php?token='.$token;
    }

    /**
     * Tukar token jadi kredensial. Token langsung dibuang (sekali pakai).
     *
     * @return array{user:string,password:string,host:string,port:int,db:?string}|null
     */
    public function redeem(string $token): ?array
    {
        if (! preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }

        $payload = Cache::pull("pma-signon:{$token}");
        if (! is_array($payload)) {
            return null;
        }

        if (($payload['type'] ?? '') === 'database') {
            $db = ServerDatabase::with('databaseHost')->find($payload['id'] ?? 0);
            $host = $db?->databaseHost;
            if (! $db || ! $host) {
                return null;
            }

            return [
                'user' => $db->username,
                'password' => (string) $db->password,
                'host' => $host->host,
                'port' => (int) $host->port,
                'db' => $db->database,
            ];
        }

        if (($payload['type'] ?? '') === 'host') {
            $host = DatabaseHost::find($payload['id'] ?? 0);
            if (! $host) {
                return null;
            }

            return [
                'user' => $host->username,
                'password' => (string) $host->password,
                'host' => $host->host,
                'port' => (int) $host->port,
                'db' => null,
            ];
        }

        return null;
    }
}
