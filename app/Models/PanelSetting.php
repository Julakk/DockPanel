<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PanelSetting extends Model
{
    protected $table = 'panel_settings';

    protected $fillable = ['company_name', 'require_2fa', 'default_language'];

    /**
     * Panel cuma punya 1 baris setting. Ambil (atau bikin default kalau belum ada).
     */
    public static function current(): self
    {
        return static::firstOrCreate(['id' => 1]);
    }
}
