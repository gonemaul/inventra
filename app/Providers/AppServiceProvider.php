<?php

namespace App\Providers;

use App\Listeners\BackupTelegramNotification;
use Google\Service\Drive;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use Masbug\Flysystem\GoogleDriveAdapter;        // <--- Tambahkan ini
use Spatie\Backup\Events\BackupHasFailed; // <--- Tambahkan ini
use Spatie\Backup\Events\BackupWasSuccessful; // <--- Tambahkan ini

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
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
        Event::listen(
            BackupWasSuccessful::class,
            BackupTelegramNotification::class,
        );

        Event::listen(
            BackupHasFailed::class,
            BackupTelegramNotification::class,
        );
        try {
            Storage::extend('google', function ($app, $config) {
                $client = new \Google\Client;
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
