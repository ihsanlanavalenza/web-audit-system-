<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    private const MIN_UPLOAD_FILESIZE_BYTES = 10485760; // 10 MB
    private const MIN_POST_MAX_SIZE_BYTES = 52428800; // 50 MB
    private const MIN_MAX_FILE_UPLOADS = 10;

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
        if (!app()->environment('production')) {
            return;
        }

        $uploadMaxFilesize = $this->parseIniSizeToBytes(ini_get('upload_max_filesize'));
        $postMaxSize = $this->parseIniSizeToBytes(ini_get('post_max_size'));
        $maxFileUploads = (int) ini_get('max_file_uploads');

        if ($uploadMaxFilesize > 0 && $uploadMaxFilesize < self::MIN_UPLOAD_FILESIZE_BYTES) {
            Log::warning('PHP upload_max_filesize terlalu kecil untuk kebutuhan upload aplikasi.', [
                'current' => ini_get('upload_max_filesize'),
                'recommended_min' => '10M',
            ]);
        }

        if ($postMaxSize > 0 && $postMaxSize < self::MIN_POST_MAX_SIZE_BYTES) {
            Log::warning('PHP post_max_size terlalu kecil untuk kebutuhan multi upload aplikasi.', [
                'current' => ini_get('post_max_size'),
                'recommended_min' => '50M',
            ]);
        }

        if ($maxFileUploads > 0 && $maxFileUploads < self::MIN_MAX_FILE_UPLOADS) {
            Log::warning('PHP max_file_uploads terlalu kecil untuk kebutuhan multi upload aplikasi.', [
                'current' => $maxFileUploads,
                'recommended_min' => self::MIN_MAX_FILE_UPLOADS,
            ]);
        }

        $tempDisk = config('livewire.temporary_file_upload.disk') ?: config('filesystems.default');
        $tempDirectory = config('livewire.temporary_file_upload.directory') ?: 'livewire-tmp';
        $tempDiskConfig = config("filesystems.disks.{$tempDisk}");

        if (is_array($tempDiskConfig) && ($tempDiskConfig['driver'] ?? null) === 'local') {
            $diskRoot = $tempDiskConfig['root'] ?? null;
            if (is_string($diskRoot) && $diskRoot !== '') {
                $livewireTmpPath = rtrim($diskRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $tempDirectory;

                if (!is_dir($livewireTmpPath)) {
                    @mkdir($livewireTmpPath, 0775, true);
                }

                if (!is_writable($livewireTmpPath)) {
                    Log::warning('Direktori Livewire temporary upload tidak writable.', [
                        'path' => $livewireTmpPath,
                        'disk' => $tempDisk,
                    ]);
                }
            }
        }

        $publicDiskConfig = config('filesystems.disks.public');
        if (is_array($publicDiskConfig) && ($publicDiskConfig['driver'] ?? null) === 'local') {
            $publicRoot = $publicDiskConfig['root'] ?? null;
            if (is_string($publicRoot) && $publicRoot !== '' && is_dir($publicRoot) && !is_writable($publicRoot)) {
                Log::warning('Direktori public disk tidak writable untuk upload.', [
                    'path' => $publicRoot,
                ]);
            }
        }

        $uploadTmpDir = ini_get('upload_tmp_dir');
        if ($uploadTmpDir && is_dir($uploadTmpDir) && !is_writable($uploadTmpDir)) {
            Log::warning('Direktori upload_tmp_dir PHP tidak writable.', [
                'path' => $uploadTmpDir,
            ]);
        }
    }

    private function parseIniSizeToBytes(string|false $value): int
    {
        if ($value === false || $value === '') {
            return 0;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        $unit = strtolower(substr($value, -1));
        $bytes = (float) $value;

        return match ($unit) {
            'g' => (int) ($bytes * 1024 * 1024 * 1024),
            'm' => (int) ($bytes * 1024 * 1024),
            'k' => (int) ($bytes * 1024),
            default => (int) $bytes,
        };
    }
}
