<?php

namespace App\Services;

use App\Models\DatabaseHost;
use App\Models\ServerDatabase;
use PDO;
use PDOException;
use RuntimeException;

/**
 * Bikin dan hapus database + user MySQL beneran di Database Host.
 *
 * User di Database Host cukup punya hak: CREATE USER di *.*, dan ALL PRIVILEGES
 * ... WITH GRANT OPTION di database berawalan "s" + 8 karakter + "_" (namespace
 * Panel; lihat skrip setup-dbadmin). Nggak perlu root.
 */
class DatabaseProvisioner
{
    public function enabled(): bool
    {
        return (bool) config('app.provision_databases', true);
    }

    /** Bikin database, user, dan grant-nya. Aman dipanggil berulang (idempoten). */
    public function create(ServerDatabase $db): void
    {
        if (! $this->enabled()) {
            return;
        }

        $this->run($db->databaseHost, function (PDO $pdo) use ($db) {
            foreach (self::createStatements(
                fn (string $v) => $pdo->quote($v),
                $db->database,
                $db->username,
                $this->remoteOf($db),
                (string) $db->password,
            ) as $sql) {
                $pdo->exec($sql);
            }
        });
    }

    /** Hapus database; user ikut dihapus kalau nggak dipakai database lain. */
    public function drop(ServerDatabase $db): void
    {
        if (! $this->enabled()) {
            return;
        }

        $userStillUsed = ServerDatabase::where('database_host_id', $db->database_host_id)
            ->where('username', $db->username)
            ->where('id', '!=', $db->id)
            ->exists();

        $this->run($db->databaseHost, function (PDO $pdo) use ($db, $userStillUsed) {
            foreach (self::dropStatements(
                fn (string $v) => $pdo->quote($v),
                $db->database,
                $db->username,
                $this->remoteOf($db),
                $userStillUsed,
            ) as $sql) {
                $pdo->exec($sql);
            }
        });
    }

    /**
     * SQL buat bikin database + user. Murni string (nggak konek ke mana-mana),
     * jadi gampang dites. $quote = fungsi escape string (PDO::quote).
     *
     * @return list<string>
     */
    public static function createStatements(callable $quote, string $db, string $user, string $remote, string $password): array
    {
        self::assertSafe($db, $user, $remote);

        $u = $quote($user).'@'.$quote($remote);
        $p = $quote($password);
        $grantDb = str_replace('_', '\\_', $db); // "_" di nama database GRANT itu wildcard

        return [
            "CREATE DATABASE IF NOT EXISTS `{$db}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
            "CREATE USER IF NOT EXISTS {$u} IDENTIFIED BY {$p}",
            "ALTER USER {$u} IDENTIFIED BY {$p}",
            "GRANT ALL PRIVILEGES ON `{$grantDb}`.* TO {$u}",
        ];
    }

    /** @return list<string> */
    public static function dropStatements(callable $quote, string $db, string $user, string $remote, bool $userStillUsed): array
    {
        self::assertSafe($db, $user, $remote);

        $u = $quote($user).'@'.$quote($remote);
        $grantDb = str_replace('_', '\\_', $db);

        $sql = ["DROP DATABASE IF EXISTS `{$db}`"];
        $sql[] = $userStillUsed
            ? "REVOKE ALL PRIVILEGES ON `{$grantDb}`.* FROM {$u}"
            : "DROP USER IF EXISTS {$u}";

        return $sql;
    }

    private static function assertSafe(string $db, string $user, string $remote): void
    {
        if (! preg_match('/^[A-Za-z0-9_]{1,64}$/', $db)) {
            throw new RuntimeException('Nama database nggak valid.');
        }
        if (! preg_match('/^[A-Za-z0-9_]{1,32}$/', $user)) {
            throw new RuntimeException('Username database nggak valid.');
        }
        if (! preg_match('/^[A-Za-z0-9._%:-]{1,60}$/', $remote)) {
            throw new RuntimeException('Nilai "Connections from" nggak valid.');
        }
    }

    private function remoteOf(ServerDatabase $db): string
    {
        return $db->remote ?: '%';
    }

    private function run(?DatabaseHost $host, callable $work): void
    {
        if (! $host) {
            throw new RuntimeException('Database Host nggak ketemu.');
        }

        try {
            $pdo = new PDO(
                "mysql:host={$host->host};port={$host->port};charset=utf8mb4",
                $host->username,
                (string) $host->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 5,
                ],
            );
            $work($pdo);
        } catch (PDOException $e) {
            throw new RuntimeException(self::explain($e), 0, $e);
        }
    }

    /** Terjemahin error MySQL jadi pesan yang bisa ditindaklanjuti. */
    public static function explain(PDOException $e): string
    {
        $code = (int) ($e->errorInfo[1] ?? $e->getCode());

        return match (true) {
            in_array($code, [1045, 1698], true) => 'Login ke Database Host ditolak. Cek username dan password di halaman Databases.',
            in_array($code, [2002, 2003, 2006], true) => 'Database Host nggak bisa dihubungi. Cek host dan port-nya.',
            in_array($code, [1044, 1142, 1227, 1370, 1410], true) => 'User Database Host nggak punya hak cukup (butuh CREATE USER dan GRANT OPTION). Jalankan skrip setup-dbadmin.',
            default => 'Operasi database gagal: '.mb_strimwidth($e->getMessage(), 0, 200, '...'),
        };
    }
}
