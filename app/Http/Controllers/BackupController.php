<?php

namespace App\Http\Controllers;

use ZipArchive;
use Carbon\Carbon;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Exception\ProcessFailedException;

class BackupController extends Controller
{
    // Mengambil nama folder backup dari config spatie
    // Biasanya nama project, misal: 'Laravel'
    protected function getBackupPath($fileName = '')
    {
        $name = config('backup.backup.name');
        return $name . '/' . $fileName;
    }

    // 1. ACTION: BUAT BACKUP BARU
    public function store()
    {
        try {
            //     // Kita pakai flag --only-db agar proses cepat & ringan (file tidak ikut)
            //     // --disable-notifications agar tidak error jika email belum disetting
            Artisan::call('backup:run --only-db');
            $output = Artisan::output();
            if (str_contains(strtolower($output), 'failed')) {
                dd("BACKUP GAGAL: " . $output); // Matikan proses dan tampilkan error di layar
            }
            //     // Log output artisan untuk debugging jika perlu
            // Log::info("Backup Output: " . Artisan::output());
            Cache::forever('last_backup_info', [
                'date'     => Carbon::now()->translatedFormat('d F Y, H:i:s'), // Format: 13 Desember 2025, 12:00:00
                'user'     => Auth::user()->name ?? 'System' // Opsional: siapa yang merestore
            ]);
            return back()->with('success', 'Backup database berhasil dibuat!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membuat backup: ' . $e->getMessage());
        }
    }

    // 2. ACTION: DOWNLOAD KE PC
    public function download($fileName)
    {
        $path = $this->getBackupPath($fileName);

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->download($path);
        }
        return back()->with('error', 'File tidak ditemukan.');
    }

    // 3. ACTION: HAPUS FILE
    public function destroy($fileName)
    {
        $path = $this->getBackupPath($fileName);

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
            return back()->with('success', 'File backup berhasil dihapus.');
        }

        return back()->with('error', 'File tidak ditemukan.');
    }

    // 1. UPDATE SETTING JADWAL (Toggle ON/OFF)
    public function updateSetting(Request $request)
    {
        // Validasi input
        $request->validate(['enabled' => 'required|boolean']);

        // Update di database settings
        Setting::updateOrCreate(
            ['key' => 'enable_auto_backup'],
            ['value' => $request->enabled ? 'true' : 'false']
        );

        $status = $request->enabled ? 'diaktifkan' : 'dimatikan';
        return back()->with('success', "Jadwal backup otomatis berhasil {$status}.");
    }

    // 2. RESTORE DARI FILE LIST
    public function restore($fileName)
    {
        $path = $this->getBackupPath($fileName); // public/Inventra/namafile.zip
        $fullPath = Storage::disk('public')->path($path); // C:\laragon\...\storage\app\public\Inventra\...

        if (!file_exists($fullPath)) {
            return back()->with('error', 'File backup tidak ditemukan di server.');
        }

        return $this->processRestore($fullPath, $fileName);
    }

    // 3. RESTORE DARI UPLOAD
    public function uploadRestore(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|mimes:zip'
        ]);

        $file = $request->file('backup_file');
        $originalName = $file->getClientOriginalName();
        // Gunakan nama unik agar tidak bentrok kalau ada 2 admin upload barengan
        $fileName = 'restore_temp_' . time() . '.zip';

        // 1. Simpan ke Public Disk
        $tempPath = Storage::disk('public')->putFileAs('temp', $file, $fileName);
        $fullPath = Storage::disk('public')->path($tempPath);

        // Pastikan file permission aman untuk dibaca ZipArchive (terutama di Docker)
        @chmod($fullPath, 0644);

        try {
            // 2. Proses Restore
            $result = $this->processRestore($fullPath, $originalName . ' (Uploaded)');

            return $result;
        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan sistem: ' . $e->getMessage());
        } finally {
            // 3. Hapus file temp (WAJIB PAKAI DISK PUBLIC)
            // Menggunakan finally agar sukses/gagal file tetap dihapus
            if (Storage::disk('public')->exists($tempPath)) {
                Storage::disk('public')->delete($tempPath);
            }
        }
    }

    private function processRestore($zipPath, $displayName)
    {
        // A. PERSIAPAN FOLDER EKSTRAK
        // Gunakan folder unik untuk setiap proses agar tidak tercampur
        $extractDirName = 'temp/restore_' . time();
        $extractPath = Storage::path($extractDirName); // Path absolut sistem

        // Pastikan folder bersih dari awal
        if (is_dir($extractPath)) {
            $this->deleteDirectory($extractPath);
        }
        mkdir($extractPath, 0755, true);

        try {
            // B. EKSTRAK ZIP
            $zip = new ZipArchive;
            if ($zip->open($zipPath) !== TRUE) {
                return back()->with('error', 'Gagal membuka file ZIP. File mungkin korup.');
            }

            $zip->extractTo($extractPath);
            $zip->close();

            // C. CARI FILE .SQL
            $sqlFile = $this->findSqlFile($extractPath);

            if (!$sqlFile) {
                return back()->with('error', 'File SQL tidak ditemukan dalam backup ini.');
            }

            // D. JALANKAN IMPORT
            $this->executeSystemRestore($sqlFile);
            Cache::forever('last_restore_info', [
                'filename' => $displayName,
                'date'     => Carbon::now()->translatedFormat('d F Y, H:i:s'), // Format: 13 Desember 2025, 12:00:00
                'user'     => Auth::user()->name ?? 'System' // Opsional: siapa yang merestore
            ]);
            $sessionDriver = config('session.driver');
            if ($sessionDriver === 'file') {
                $this->cleanSessionFiles();
            } elseif ($sessionDriver === 'database') {
                DB::table('sessions')->truncate();
            }
            Auth::guard('web')->logout();
            request()->session()->invalidate();
            request()->session()->regenerateToken();
            return redirect('/login')->with('warning', 'Sistem telah di-restore. Silakan login kembali demi keamanan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal restore: ' . $e->getMessage());
        } finally {
            // E. BERSIHKAN FOLDER HASIL EKSTRAK (PENTING)
            // Hapus folder temp ekstrak baik sukses maupun gagal
            if (is_dir($extractPath)) {
                // Hapus folder recursively (pastikan Anda punya fungsi deleteDirectory atau gunakan File facade)
                // Jika pakai Laravel File Facade:
                // \Illuminate\Support\Facades\File::deleteDirectory($extractPath);

                // Atau pakai fungsi manual Anda:
                $this->deleteDirectory($extractPath);
            }
        }
    }

    // --- LOGIC SYSTEM COMMAND (Command Line) ---
    private function executeSystemRestore($sqlPath)
    {
        $driver = DB::connection()->getDriverName();
        $dbConfig = config("database.connections.$driver");
        DB::purge($driver);
        if ($driver === 'mysql') {
            Artisan::call('db:wipe', ['--force' => true]);
            // ... Logic MySQL (biarkan atau copy dari sebelumnya) ...
            $binaryPath = env('DB_DUMP_BINARY_PATH', '');
            $binary = $binaryPath ? $binaryPath . 'mysql' : 'mysql';

            $command = sprintf(
                '"%s" --user="%s" --password="%s" --host="%s" --port="%s" "%s" < "%s"',
                $binary,
                $dbConfig['username'],
                $dbConfig['password'],
                $dbConfig['host'],
                $dbConfig['port'],
                $dbConfig['database'],
                $sqlPath
            );
            $this->runCommand($command);
        } elseif ($driver === 'sqlite') {
            // 1. Path sqlite3.exe (D:/.../bin/sqlite3.exe)
            $binaryPath = env('DB_DUMP_BINARY_PATH');
            $exe = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'sqlite3.exe' : 'sqlite3';
            $binary = $binaryPath . $exe;

            // 2. Path Database Tujuan (D:/.../database.sqlite)
            $databasePath = $dbConfig['database'];

            // Kita gunakan native PHP fopen dengan mode 'w' (write)
            // Mode ini otomatis memotong (truncate) file jadi kosong.
            if (file_exists($databasePath)) {
                $handle = fopen($databasePath, 'w');
                fclose($handle);
            } else {
                // Jika file tidak ada, buat baru
                touch($databasePath);
            }
            // 3. Command: "sqlite3.exe" "db.sqlite" < "extracted_file.sql"
            // (Perhatikan: $sqlPath disini adalah file .sql hasil ekstrak, BUKAN .zip lagi)
            $command = sprintf(
                '"%s" "%s" < "%s"',
                $binary,
                $databasePath,
                $sqlPath
            );

            $this->runCommand($command);
        }
        DB::reconnect($driver);
    }

    private function runCommand($command)
    {
        $process = Process::fromShellCommandline($command);
        $process->setTimeout(null);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    // Helper: Cari file .sql
    private function findSqlFile($dir)
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'sql') {
                return $file->getRealPath();
            }
        }
        return null;
    }

    // Helper: Hapus folder
    private function deleteDirectory($dir)
    {
        if (!file_exists($dir)) return true;
        if (!is_dir($dir)) return unlink($dir);
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') continue;
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
        }
        return rmdir($dir);
    }
    private function cleanSessionFiles()
    {
        $directory = storage_path('framework/sessions');
        // Hapus semua file kecuali .gitignore
        foreach (File::glob("{$directory}/*") as $file) {
            if (!str_ends_with($file, '.gitignore')) {
                File::delete($file);
            }
        }
    }
}
