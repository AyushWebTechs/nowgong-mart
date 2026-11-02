<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingsRequest;
use App\Services\StoreSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(StoreSettings $settings): View
    {
        return view('admin.settings', ['settings' => $settings]);
    }

    public function update(SettingsRequest $request, StoreSettings $settings): RedirectResponse
    {
        $data = $request->safe()->except('upi_qr');
        $data['accepting_orders'] = $request->boolean('accepting_orders') ? '1' : '0';
        $data['customer_cancel_allowed'] = $request->boolean('customer_cancel_allowed') ? '1' : '0';

        if ($request->hasFile('upi_qr')) {
            if ($old = $settings->get('upi_qr_path')) {
                Storage::disk('public')->delete($old);
            }
            $data['upi_qr_path'] = $request->file('upi_qr')->store('settings', 'public');
        }

        $settings->set($data);

        return back()->with('success', 'Store settings saved.');
    }
}
