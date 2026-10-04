<?php

namespace App\Services\Concerns;

/**
 * Operasi backup ke Wings (/api/servers/{uuid}/backups/*). Dipakai lewat
 * `use WingsBackups;` di WingsService, jadi $this->client() dan $this->server
 * tersedia. Butuh DockWings v0.4.1+.
 *
 * Semua method balikin [status HTTP, data] biar controller yang mutusin
 * pesan error ke user.
 */
trait WingsBackups
{
    protected function backupsPath(string $suffix = ''): string
    {
        return "/api/servers/{$this->server->uuid}/backups".$suffix;
    }

    public function createBackup(string $backupUuid): array
    {
        $r = $this->client()->timeout(15)->post($this->backupsPath(), ['uuid' => $backupUuid]);

        return [$r->status(), $r->json() ?? []];
    }

    public function backupStatus(string $backupUuid): array
    {
        $r = $this->client()->timeout(10)->get($this->backupsPath("/{$backupUuid}"));

        return [$r->status(), $r->json() ?? []];
    }

    public function deleteBackup(string $backupUuid): array
    {
        $r = $this->client()->timeout(30)->delete($this->backupsPath("/{$backupUuid}"));

        return [$r->status(), $r->json() ?? []];
    }

    /** Mulai restore backup (butuh DockWings v0.4.2+). Server harus mati. */
    public function restoreBackup(string $backupUuid): array
    {
        $r = $this->client()->timeout(15)->post($this->backupsPath("/{$backupUuid}/restore"));

        return [$r->status(), $r->json() ?? []];
    }

    public function restoreStatus(): array
    {
        $r = $this->client()->timeout(10)->get("/api/servers/{$this->server->uuid}/restore");

        return [$r->status(), $r->json() ?? []];
    }

    /** Response mentah (stream) buat download. Pemanggil yang baca body-nya. */
    public function downloadBackup(string $backupUuid)
    {
        return $this->client()->timeout(600)
            ->withOptions(['stream' => true])
            ->get($this->backupsPath("/{$backupUuid}/download"));
    }
}
