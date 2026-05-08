<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('audit:upload-env', function () {
    $parseIni = function (string|false $value): int {
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
    };

    $formatBytes = function (int $bytes): string {
        if ($bytes <= 0) {
            return '0B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;
        $value = $bytes;

        while ($value >= 1024 && $index < count($units) - 1) {
            $value /= 1024;
            $index++;
        }

        return number_format($value, 2) . $units[$index];
    };

    $uploadMax = $parseIni(ini_get('upload_max_filesize'));
    $postMax = $parseIni(ini_get('post_max_size'));
    $maxFiles = (int) ini_get('max_file_uploads');
    $fileUploads = ini_get('file_uploads');
    $uploadTmpDir = ini_get('upload_tmp_dir') ?: '(default)';

    $this->info('Upload environment diagnostics');
    $this->line('file_uploads: ' . ($fileUploads ?: 'unknown'));
    $this->line('upload_max_filesize: ' . (ini_get('upload_max_filesize') ?: '-') . ' (' . $formatBytes($uploadMax) . ')');
    $this->line('post_max_size: ' . (ini_get('post_max_size') ?: '-') . ' (' . $formatBytes($postMax) . ')');
    $this->line('max_file_uploads: ' . ($maxFiles ?: 0));
    $this->line('upload_tmp_dir: ' . $uploadTmpDir);

    $this->line('Recommended: upload_max_filesize >= 10M, post_max_size >= 50M, max_file_uploads >= 10');

    $tempDisk = config('livewire.temporary_file_upload.disk') ?: config('filesystems.default');
    $tempDirectory = config('livewire.temporary_file_upload.directory') ?: 'livewire-tmp';
    $tempDiskConfig = config("filesystems.disks.{$tempDisk}");

    $this->info('Livewire temporary upload');
    $this->line('disk: ' . ($tempDisk ?: 'default'));
    $this->line('directory: ' . $tempDirectory);

    if (is_array($tempDiskConfig) && ($tempDiskConfig['driver'] ?? null) === 'local') {
        $diskRoot = $tempDiskConfig['root'] ?? null;
        if (is_string($diskRoot) && $diskRoot !== '') {
            $tempPath = rtrim($diskRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $tempDirectory;
            $this->line('path: ' . $tempPath);
            $this->line('exists: ' . (is_dir($tempPath) ? 'yes' : 'no'));
            $this->line('writable: ' . (is_writable($tempPath) ? 'yes' : 'no'));
        }
    }

    $publicDiskConfig = config('filesystems.disks.public');
    if (is_array($publicDiskConfig) && ($publicDiskConfig['driver'] ?? null) === 'local') {
        $publicRoot = $publicDiskConfig['root'] ?? null;
        if (is_string($publicRoot) && $publicRoot !== '') {
            $this->info('Public disk');
            $this->line('root: ' . $publicRoot);
            $this->line('writable: ' . (is_dir($publicRoot) && is_writable($publicRoot) ? 'yes' : 'no'));
        }
    }
})->purpose('Diagnose multi-file upload environment and storage permissions.');

Artisan::command('audit:list-cms', function () {
    $toClassName = function (string $path): string {
        $relative = str_replace(app_path() . DIRECTORY_SEPARATOR, '', $path);
        $relative = str_replace('.php', '', $relative);
        $relative = str_replace(DIRECTORY_SEPARATOR, '\\', $relative);
        return 'App\\' . $relative;
    };

    $livewirePath = app_path('Livewire');
    $controllerPath = app_path('Http/Controllers');

    $livewireComponents = File::exists($livewirePath)
        ? collect(File::allFiles($livewirePath))
            ->map(fn($file) => $toClassName($file->getPathname()))
            ->sort()
            ->values()
        : collect();

    $controllers = File::exists($controllerPath)
        ? collect(File::allFiles($controllerPath))
            ->map(fn($file) => $toClassName($file->getPathname()))
            ->reject(fn($class) => $class === 'App\\Http\\Controllers\\Controller')
            ->sort()
            ->values()
        : collect();

    $this->info('CMS Livewire components (' . $livewireComponents->count() . ')');
    foreach ($livewireComponents as $class) {
        $this->line(' - ' . $class);
    }

    $this->info('HTTP Controllers (' . $controllers->count() . ')');
    foreach ($controllers as $class) {
        $this->line(' - ' . $class);
    }
})->purpose('List Livewire CMS components and HTTP controllers.');

Artisan::command('audit:check-cms', function () {
    $toClassName = function (string $path): string {
        $relative = str_replace(app_path() . DIRECTORY_SEPARATOR, '', $path);
        $relative = str_replace('.php', '', $relative);
        $relative = str_replace(DIRECTORY_SEPARATOR, '\\', $relative);
        return 'App\\' . $relative;
    };

    $livewirePath = app_path('Livewire');
    $controllerPath = app_path('Http/Controllers');

    $livewireComponents = File::exists($livewirePath)
        ? collect(File::allFiles($livewirePath))
            ->map(fn($file) => $toClassName($file->getPathname()))
            ->sort()
            ->values()
        : collect();

    $controllers = File::exists($controllerPath)
        ? collect(File::allFiles($controllerPath))
            ->map(fn($file) => $toClassName($file->getPathname()))
            ->reject(fn($class) => $class === 'App\\Http\\Controllers\\Controller')
            ->sort()
            ->values()
        : collect();

    $missingLivewire = $livewireComponents->filter(fn($class) => !class_exists($class))->values();
    $missingControllers = $controllers->filter(fn($class) => !class_exists($class))->values();

    $failed = false;

    if ($livewireComponents->isEmpty()) {
        $this->error('No Livewire components found.');
        $failed = true;
    }

    if ($controllers->isEmpty()) {
        $this->error('No HTTP controllers found.');
        $failed = true;
    }

    if ($missingLivewire->isNotEmpty()) {
        $this->error('Missing Livewire classes:');
        foreach ($missingLivewire as $class) {
            $this->line(' - ' . $class);
        }
        $failed = true;
    }

    if ($missingControllers->isNotEmpty()) {
        $this->error('Missing controller classes:');
        foreach ($missingControllers as $class) {
            $this->line(' - ' . $class);
        }
        $failed = true;
    }

    if (!$failed) {
        $this->info('CMS check passed.');
    }

    return $failed ? 1 : 0;
})->purpose('Validate Livewire CMS components and HTTP controllers.');

// Cron Job: Kirim email followup untuk data request yang terlambat
// Jalankan setiap hari jam 08:00
Schedule::command('audit:send-followup')->dailyAt('08:00');
