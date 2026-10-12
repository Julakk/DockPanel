<?php

namespace App\Services;

use App\Models\Egg;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Import egg format Pterodactyl (PTDL_v1 / PTDL_v2) jadi Egg DockPanel.
 */
class EggImporter
{
    public function import(array $data, int $nestId): Egg
    {
        if (empty($data['name']) || empty($data['startup'])) {
            throw new InvalidArgumentException('JSON harus punya field "name" dan "startup" minimal.');
        }

        // v2: docker_images = {"Label": "image"}, v1: docker_image = "image"
        $images = is_array($data['docker_images'] ?? null) ? $data['docker_images'] : null;
        $default = $images ? (string) reset($images) : (string) ($data['docker_image'] ?? '');

        if ($default === '') {
            throw new InvalidArgumentException('Egg nggak punya docker image (docker_images / docker_image).');
        }

        $config = is_array($data['config'] ?? null) ? $data['config'] : [];
        $install = is_array($data['scripts']['installation'] ?? null) ? $data['scripts']['installation'] : [];

        $variables = [];
        foreach ((array) ($data['variables'] ?? []) as $var) {
            $env = is_array($var) ? (string) ($var['env_variable'] ?? '') : '';

            if (! preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $env)) {
                throw new InvalidArgumentException("Nama env_variable nggak valid: '{$env}'.");
            }

            $variables[] = [
                'name' => (string) ($var['name'] ?? $env),
                'env_variable' => $env,
                'description' => isset($var['description']) ? (string) $var['description'] : null,
                'default_value' => isset($var['default_value']) && is_scalar($var['default_value'])
                    ? (string) $var['default_value'] : null,
                'user_viewable' => (bool) ($var['user_viewable'] ?? true),
                'user_editable' => (bool) ($var['user_editable'] ?? true),
                'rules' => Str::limit((string) ($var['rules'] ?? 'nullable|string'), 255, ''),
            ];
        }

        return DB::transaction(function () use ($data, $nestId, $images, $default, $config, $install, $variables) {
            $egg = Egg::create([
                'nest_id' => $nestId,
                'name' => (string) $data['name'],
                'description' => isset($data['description']) ? (string) $data['description'] : null,
                'docker_image' => $default,
                'docker_images' => $images ?: null,
                'startup' => (string) $data['startup'],
                'config_files' => $this->decode($config['files'] ?? $data['config_files'] ?? null),
                'config_startup' => $this->decode($config['startup'] ?? null),
                'config_stop' => $config['stop'] ?? null,
                'script_container' => (string) ($install['container'] ?? 'alpine:3.4'),
                'script_install' => isset($install['script']) ? (string) $install['script'] : null,
            ]);

            foreach ($variables as $var) {
                $egg->variables()->create($var);
            }

            return $egg;
        });
    }

    /** Di egg Pterodactyl, config.files / config.startup berupa string JSON. */
    private function decode(mixed $value): ?array
    {
        if (is_array($value)) {
            return $value ?: null;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) && $decoded ? $decoded : null;
        }

        return null;
    }
}
