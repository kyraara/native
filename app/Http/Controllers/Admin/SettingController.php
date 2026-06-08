<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $settings = Setting::pluck('value', 'key');
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'wa_number'     => 'required|string|max:20',
            'email'         => 'required|email|max:100',
            'instagram'     => 'required|string|max:100',
            'total_orders'  => 'required|string|max:20',
            'happy_clients' => 'required|string|max:20',
            'subjects'      => 'required|string|max:20',
            'rating'        => 'required|string|max:10',
            'location'      => 'nullable|string|max:100',
        ]);

        $keys = ['wa_number', 'email', 'instagram', 'total_orders', 'happy_clients', 'subjects', 'rating', 'location'];

        foreach ($keys as $key) {
            if ($request->has($key)) {
                Setting::set($key, $request->input($key));
            }
        }

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
