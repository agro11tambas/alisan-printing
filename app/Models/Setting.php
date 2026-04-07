<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';
    protected $keyType    = 'string';
    public $incrementing  = false;

    protected $fillable = ['key', 'value'];
    protected $casts    = ['value' => 'boolean'];

    public static function isEnabled(string $key): bool
    {
        return (bool) static::where('key', $key)->value('value');
    }

    public static function toggle(string $key): void
    {
        $current = static::where('key', $key)->value('value');
        static::where('key', $key)->update(['value' => !$current]);
    }
}
