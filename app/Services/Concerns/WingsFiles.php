<?php

namespace App\Services\Concerns;

use GuzzleHttp\Psr7\Utils;

/**
 * Operasi file manager ke Wings (/api/servers/{uuid}/files/*).
 * Dipakai lewat `use WingsFiles;` di WingsService, jadi $this->client()
 * dan $this->server tersedia.
 *
 * Semua method balikin [status HTTP, data] biar controller yang mutusin
 * pesan error ke user.
 */
trait WingsFiles
{
    protected function filesPath(string $action): string
    {
        return "/api/servers/{$this->server->uuid}/files/{$action}";
    }

    public function listFiles(string $path): array
    {
        $r = $this->client()->timeout(15)->get($this->filesPath('list'), ['path' => $path]);

        return [$r->status(), $r->json() ?? []];
    }

    /**
     * Baca isi file buat editor, dibatasi $limit byte lewat HTTP Range
     * (Wings pakai ServeContent, jadi Range didukung).
     *
     * @return array{0:int,1:?string,2:?int,3:?string} [status, isi, ukuran total, pesan error]
     */
    public function readFile(string $path, int $limit): array
    {
        $r = $this->client()->timeout(30)
            ->withHeaders(['Range' => 'bytes=0-'.($limit - 1)])
            ->get($this->filesPath('contents'), ['path' => $path]);

        $status = $r->status();

        if ($status === 416) { // Range nggak overlap = file kosong
            return [200, '', 0, null];
        }
        if ($status === 206) {
            $total = null;
            if (preg_match('#/(\d+)$#', (string) $r->header('Content-Range'), $m)) {
                $total = (int) $m[1];
            }

            return [200, $r->body(), $total, null];
        }
        if ($status === 200) {
            return [200, $r->body(), strlen($r->body()), null];
        }

        return [$status, null, null, $r->json('error')];
    }

    /** Response mentah (stream) buat download. Pemanggil yang baca body-nya. */
    public function downloadFile(string $path)
    {
        return $this->client()->timeout(300)
            ->withOptions(['stream' => true])
            ->get($this->filesPath('contents'), ['path' => $path]);
    }

    /** $contents boleh string atau resource (fopen). */
    public function writeFile(string $path, $contents): array
    {
        if (is_resource($contents)) {
            $contents = Utils::streamFor($contents);
        }

        $r = $this->client()->timeout(300)
            ->withBody($contents, 'application/octet-stream')
            ->post($this->filesPath('write').'?path='.rawurlencode($path));

        return [$r->status(), $r->json() ?? []];
    }

    public function createDirectory(string $path): array
    {
        $r = $this->client()->timeout(15)->post($this->filesPath('mkdir'), ['path' => $path]);

        return [$r->status(), $r->json() ?? []];
    }

    public function renameFile(string $from, string $to): array
    {
        $r = $this->client()->timeout(15)->post($this->filesPath('rename'), ['from' => $from, 'to' => $to]);

        return [$r->status(), $r->json() ?? []];
    }

    public function deleteFiles(array $paths): array
    {
        $r = $this->client()->timeout(60)->post($this->filesPath('delete'), ['paths' => array_values($paths)]);

        return [$r->status(), $r->json() ?? []];
    }
}
