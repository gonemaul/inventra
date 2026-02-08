<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ImageService
{
    /**
     * Upload gambar dengan kompresi otomatis jika > 2MB
     *
     * * @param \Illuminate\Http\UploadedFile $file File dari request
     * @param  string  $path  Folder tujuan (misal: 'shop', 'products')
     * @param  string|null  $oldFile  Path file lama untuk dihapus (opsional)
     * @return string Path file yang baru disimpan
     */
    public function upload($file, $path, $oldFile = null)
    {
        // 1. Hapus file lama jika ada
        if ($oldFile && Storage::disk('s3')->exists($oldFile)) {
            Storage::disk('s3')->delete($oldFile);
        }

        // 2. Cek Ukuran File (Bytes)
        // 2MB = 2 * 1024 * 1024 = 2097152 Bytes
        $fileSize = $file->getSize();
        $fileName = md5(uniqid()).'.webp'; // Kita standarisasi ke WebP biar ringan
        $destinationPath = $path.'/'.$fileName;

        // 3. LOGIKA KOMPRESI
        if ($fileSize > 2097152) {
            // JIKA > 2MB: Kompres Agresif
            $manager = new ImageManager(new Driver);
            $image = $manager->read($file);
            
            // Fix Rotation if EXIF data exists (Safety fallback)
            if(method_exists($image, 'orientate')) {
                $image->orientate();
            }

            // FORCE 1:1 Aspect Ratio (Cover/Fit)
            // 'cover' akan memotong bagian tengah (center crop) agar pas di 800x800
            $image->cover(800, 800);

            // Encode ke WebP dengan kualitas 75%
            $encoded = $image->toWebp(75);

            // Simpan ke Storage Public
            Storage::disk('s3')->put($destinationPath, (string) $encoded);
        } else {
            // JIKA < 2MB:
            // Tetap kita proses agar konsisten (Fix rotasi & Crop 1:1)
            $manager = new ImageManager(new Driver);
            $image = $manager->read($file);
            
            // Fix Rotation
            if(method_exists($image, 'orientate')) {
                $image->orientate();
            }

             // FORCE 1:1 Aspect Ratio
             $image->cover(800, 800);

            $encoded = $image->toWebp(90);

            Storage::disk('s3')->put($destinationPath, (string) $encoded);
        }

        return $destinationPath;
    }
}
