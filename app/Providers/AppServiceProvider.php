<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;        // <--- Tambahkan ini
use Illuminate\Filesystem\FilesystemAdapter; // <--- Tambahkan ini
use Masbug\Flysystem\GoogleDriveAdapter; // <--- Tambahkan ini
use Google\Service\Drive;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            Storage::extend('google', function ($app, $config) {
                $client = new \Google\Client();
                $client->setClientId($config['clientId']);
                $client->setClientSecret($config['clientSecret']);
                $client->refreshToken($config['refreshToken']);

                $service = new Drive($client);

                // $folderId = !empty($config['folder']) ? $config['folder'] : 'root';
                $folderId = 'BACKUP_INVENTRA';
                // 'useHasDir' => true membantu adapter membedakan folder dan file
                $options = [
                    // 'useHasDir' => true,
                ];

                $adapter = new GoogleDriveAdapter($service, $folderId, $options);
                $driver = new Filesystem($adapter);

                return new FilesystemAdapter($driver, $adapter);
            });
        } catch (\Exception $e) {
            // Log error jika driver gagal dimuat, agar aplikasi tidak crash total
            // Log::error("Gagal memuat driver Google Drive: " . $e->getMessage());
        }
        Vite::prefetch(concurrency: 3);
    }
}
