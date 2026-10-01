<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Server;
use App\Services\WingsService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * File manager di client area. Browser ngomong ke Panel, Panel nge-proxy ke
 * Wings pakai daemon_token (token itu nggak pernah sampai ke browser).
 * Semua response JSON, dipakai JS di tab Files.
 */
class ClientFileController extends Controller
{
    private const EDIT_LIMIT = 2 * 1024 * 1024;

    private const PATH_RULES = ['required', 'string', 'max:1024', 'not_regex:/\x00/'];

    /**
     * Owner/admin = akses penuh. Subuser harus punya permission $perm
     * (files.read / files.write). Bukan siapa-siapa = 403.
     */
    private function wings(Request $request, Server $server, string $perm, bool $write = false): WingsService
    {
        $user = $request->user();
        $isManager = (bool) ($user->root_admin ?? false) || (int) $server->owner_id === (int) $user->id;

        if (! $isManager) {
            $sub = $server->subusers()->where('users.id', $user->id)->first();
            abort_unless($sub, 403);

            $perms = json_decode($sub->pivot->permissions ?? '[]', true);
            abort_unless(is_array($perms) && in_array($perm, $perms, true), 403);
        }

        abort_if($write && $server->suspended, 403, 'Server lagi di-suspend.');

        $server->loadMissing('node');
        abort_unless($server->node, 422, 'Node belum di-set buat server ini.');

        return new WingsService($server);
    }

    private function path(Request $request, string $key = 'path'): string
    {
        $p = $request->input($key) ?? '/';
        abort_unless(is_string($p) && strlen($p) <= 1024 && ! str_contains($p, "\0"), 422, 'Path nggak valid.');

        return $p;
    }

    private function call(callable $fn): JsonResponse|StreamedResponse
    {
        try {
            return $fn();
        } catch (ConnectionException $e) {
            return response()->json(['error' => 'Wings nggak bisa dijangkau. Node mati atau port diblok firewall.'], 502);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['error' => 'Operasi file gagal.'], 500);
        }
    }

    private function fail(int $status, ?string $message = null): JsonResponse
    {
        if ($status === 401) {
            return response()->json(['error' => 'Token node ditolak Wings. Cek daemon_token di node.'], 502);
        }

        return response()->json(
            ['error' => $message ?: 'Wings balikin error.'],
            $status >= 500 || $status < 400 ? 502 : $status,
        );
    }

    private function respond(array $res): JsonResponse
    {
        [$status, $data] = $res;

        if ($status >= 200 && $status < 300) {
            return response()->json($data);
        }

        return $this->fail($status, $data['error'] ?? null);
    }

    private function logIf(JsonResponse $resp, Server $server, string $event, array $props): JsonResponse
    {
        if ($resp->getStatusCode() < 300) {
            ActivityLog::record($event, $props, $server);
        }

        return $resp;
    }

    public function list(Request $request, Server $server): JsonResponse|StreamedResponse
    {
        $wings = $this->wings($request, $server, 'files.read');
        $path = $this->path($request);

        return $this->call(fn () => $this->respond($wings->listFiles($path)));
    }

    /** Isi file buat editor (teks, maks 2 MB). */
    public function contents(Request $request, Server $server): JsonResponse|StreamedResponse
    {
        $wings = $this->wings($request, $server, 'files.read');
        $path = $this->path($request);

        return $this->call(function () use ($wings, $path) {
            [$status, $body, $total, $err] = $wings->readFile($path, self::EDIT_LIMIT);

            if ($status !== 200) {
                return $this->fail($status, $err);
            }
            if ($total !== null && $total > self::EDIT_LIMIT) {
                return response()->json(['error' => 'File lebih dari 2 MB. Download aja kalau mau lihat isinya.'], 413);
            }
            if (str_contains($body, "\0") || ! mb_check_encoding($body, 'UTF-8')) {
                return response()->json(['error' => 'File ini binary, nggak bisa diedit di browser. Download aja.'], 415);
            }

            return response()->json(['path' => $path, 'content' => $body]);
        });
    }

    public function download(Request $request, Server $server): JsonResponse|StreamedResponse
    {
        $wings = $this->wings($request, $server, 'files.read');
        $path = $this->path($request);

        try {
            $resp = $wings->downloadFile($path);
        } catch (ConnectionException $e) {
            return response()->json(['error' => 'Wings nggak bisa dijangkau.'], 502);
        }

        if ($resp->status() !== 200) {
            return $this->fail($resp->status(), $resp->json('error'));
        }

        $body = $resp->toPsrResponse()->getBody();
        $name = basename(str_replace('\\', '/', $path)) ?: 'download';

        return response()->streamDownload(function () use ($body) {
            while (! $body->eof()) {
                echo $body->read(8192);
                flush();
            }
        }, $name, ['Content-Type' => 'application/octet-stream']);
    }

    /**
     * Simpan hasil edit. Isi file dikirim sebagai body mentah (bukan field form)
     * soalnya middleware TrimStrings bakal motong spasi/newline di ujung teks.
     * Path lewat query string: POST .../files/save?path=/server.cfg
     */
    public function save(Request $request, Server $server): JsonResponse|StreamedResponse
    {
        $wings = $this->wings($request, $server, 'files.write', true);
        $path = $this->path($request);
        abort_if(trim($path, '/') === '', 422, 'Path nggak valid.');

        $content = $request->getContent();
        if (strlen($content) > self::EDIT_LIMIT) {
            return response()->json(['error' => 'Isi file lebih dari 2 MB.'], 413);
        }

        return $this->call(fn () => $this->logIf(
            $this->respond($wings->writeFile($path, $content)),
            $server, 'server:file.write', ['path' => $path],
        ));
    }

    /** Upload satu atau beberapa file ke folder `path`. Field: files[] */
    public function upload(Request $request, Server $server): JsonResponse|StreamedResponse
    {
        $wings = $this->wings($request, $server, 'files.write', true);
        $dir = rtrim($this->path($request), '/');

        $request->validate([
            'files' => ['required', 'array', 'max:20'],
            'files.*' => ['file'],
        ], [
            'files.required' => 'Nggak ada file yang keupload (mungkin kegedean buat limit upload PHP/nginx di Panel).',
        ]);

        $files = $request->file('files');

        return $this->call(function () use ($wings, $server, $dir, $files) {
            $done = [];

            foreach ($files as $file) {
                $name = basename(str_replace('\\', '/', (string) $file->getClientOriginalName()));

                if (! $file->isValid() || in_array($name, ['', '.', '..'], true) || str_contains($name, "\0")) {
                    return response()->json(['error' => "Upload gagal atau nama file nggak valid: {$name}"], 422);
                }

                $target = $dir.'/'.$name;
                $stream = fopen($file->getRealPath(), 'rb');

                try {
                    [$status, $data] = $wings->writeFile($target, $stream);
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }

                if ($status < 200 || $status >= 300) {
                    return $this->fail($status, $data['error'] ?? null);
                }

                $done[] = $name;
            }

            ActivityLog::record('server:file.upload', ['dir' => $dir === '' ? '/' : $dir, 'files' => $done], $server);

            return response()->json(['success' => true, 'uploaded' => $done]);
        });
    }

    public function mkdir(Request $request, Server $server): JsonResponse|StreamedResponse
    {
        $wings = $this->wings($request, $server, 'files.write', true);
        $data = $request->validate(['path' => self::PATH_RULES]);

        return $this->call(fn () => $this->logIf(
            $this->respond($wings->createDirectory($data['path'])),
            $server, 'server:file.mkdir', ['path' => $data['path']],
        ));
    }

    public function rename(Request $request, Server $server): JsonResponse|StreamedResponse
    {
        $wings = $this->wings($request, $server, 'files.write', true);
        $data = $request->validate(['from' => self::PATH_RULES, 'to' => self::PATH_RULES]);

        return $this->call(fn () => $this->logIf(
            $this->respond($wings->renameFile($data['from'], $data['to'])),
            $server, 'server:file.rename', ['from' => $data['from'], 'to' => $data['to']],
        ));
    }

    public function delete(Request $request, Server $server): JsonResponse|StreamedResponse
    {
        $wings = $this->wings($request, $server, 'files.write', true);
        $data = $request->validate([
            'paths' => ['required', 'array', 'min:1', 'max:100'],
            'paths.*' => self::PATH_RULES,
        ]);

        return $this->call(fn () => $this->logIf(
            $this->respond($wings->deleteFiles($data['paths'])),
            $server, 'server:file.delete', ['paths' => $data['paths']],
        ));
    }
}
