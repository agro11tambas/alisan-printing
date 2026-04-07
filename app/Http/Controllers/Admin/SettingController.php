<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all();
        return view('erp.pages.settings.index', compact('settings'));
    }

    public function toggle(string $key)
    {
        Setting::findOrFail($key);
        Setting::toggle($key);

        return back()->with('success', "Setting '$key' berhasil diupdate.");
    }
}
