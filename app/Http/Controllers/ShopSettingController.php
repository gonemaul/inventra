<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class ShopSettingController extends Controller
{
    public function update(Request $request)
    {
        // 1. Validasi
        $request->validate([
            'shop_name'      => 'required|string|max:255',
            'shop_phone'     => 'nullable|string|max:20',
            'shop_address'   => 'nullable|string',
            'receipt_footer' => 'nullable|string', // Pesan di bawah struk
            'shop_logo'      => 'nullable|image|max:1024', // Max 1MB
        ]);

        // 2. Handle Upload Logo (Jika ada file baru)
        if ($request->hasFile('shop_logo')) {
            // Hapus logo lama jika ada
            $oldLogo = Setting::where('key', 'shop_logo')->value('value');
            if ($oldLogo && Storage::disk('public')->exists($oldLogo)) {
                Storage::disk('public')->delete($oldLogo);
            }

            // Upload logo baru ke folder 'public/shop'
            $path = $request->file('shop_logo')->store('shop', 'public');

            // Simpan path ke DB
            Setting::updateOrCreate(['key' => 'shop_logo'], ['value' => $path]);
        }

        // 3. Update Text Fields (Looping biar ringkas)
        $keys = ['shop_name', 'shop_phone', 'shop_address', 'receipt_footer'];

        foreach ($keys as $key) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $request->input($key)]
            );
        }

        return back()->with('success', 'Pengaturan toko berhasil disimpan!');
    }
}
