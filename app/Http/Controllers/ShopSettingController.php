<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\ImageService;
use Illuminate\Http\Request;

class ShopSettingController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    public function update(Request $request)
    {
        // 1. Validasi
        $request->validate([
            'shop_name' => 'required|string|max:255',
            'shop_phone' => 'nullable|string|max:20',
            'shop_address' => 'nullable|string',
            'receipt_footer' => 'nullable|string', // Pesan di bawah struk
            'shop_logo' => 'nullable|image|max:20480',
        ]);

        // 2. Handle Upload Logo (Jika ada file baru)
        if ($request->hasFile('shop_logo')) {
            // Hapus logo lama jika ada
            $oldLogo = Setting::where('key', 'shop_logo')->value('value');
            $newPath = $this->imageService->upload(
                $request->file('shop_logo'),
                'shop',
                $oldLogo
            );
            // Simpan path ke DB
            Setting::updateOrCreate(['key' => 'shop_logo'], ['value' => $newPath]);
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
