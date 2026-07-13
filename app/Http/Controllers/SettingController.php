<?php

namespace App\Http\Controllers;

use App\Models\PanelSetting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        $setting = PanelSetting::current();

        return view('settings.edit', compact('setting'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'require_2fa' => 'required|in:not_required,admin_only,all_users',
            'default_language' => 'required|string|max:5',
        ]);

        $setting = PanelSetting::current();
        $setting->update($validated);

        return redirect()->route('settings.edit')->with('success', 'Panel settings diupdate.');
    }
}
