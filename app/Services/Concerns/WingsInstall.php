<?php

namespace App\Services\Concerns;

/**
 * Install script egg lewat Wings (/api/servers/{uuid}/install). Dipakai lewat
 * `use WingsInstall;` di WingsService, jadi $this->client(), $this->server,
 * dan $this->envVariables() tersedia. Semua method balikin [status HTTP, data].
 */
trait WingsInstall
{
    public function startInstall(): array
    {
        $egg = $this->server->egg;
        $script = str_replace("\r\n", "\n", (string) $egg?->script_install);

        $r = $this->client()->timeout(15)->post("/api/servers/{$this->server->uuid}/install", [
            'script' => $script,
            'container' => (string) ($egg?->script_container ?: $this->server->image),
            'env_variables' => (object) $this->envVariables(),
        ]);

        return [$r->status(), $r->json() ?? []];
    }

    public function installStatus(): array
    {
        $r = $this->client()->timeout(5)->get("/api/servers/{$this->server->uuid}/install");

        return [$r->status(), $r->json() ?? []];
    }
}
