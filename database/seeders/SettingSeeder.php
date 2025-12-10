<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            'enable_auto_backup' => 'false', // Yang tadi

            // Identitas Toko
            'shop_name' => 'Inventra Store',
            'shop_address' => 'Jl. Malaya Kusuma, Kediri',
            'shop_phone' => '0859-5273-2791',
            'shop_logo' => null, // Path gambar nanti

            // Pengaturan Struk
            'receipt_footer' => 'Terima kasih telah berbelanja.\nBarang yang dibeli tidak dapat ditukar.',
            'printer_width' => '58', // 58mm atau 80mm
        ];

        foreach ($defaults as $key => $value) {
            \App\Models\Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
