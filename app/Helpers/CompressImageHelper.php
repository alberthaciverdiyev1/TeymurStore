<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Intervention\Image\ImageManager;

if (!function_exists('compressAndUploadImage')) {
    function compressAndUploadImage(UploadedFile $file, string $subDir = null): string
    {
        \Log::info("Compressing image");

        $subDir = $subDir ? trim($subDir, '/') : '';
        $storagePath = $subDir ? "public/{$subDir}" : "public";

        $extension = $file->getClientOriginalExtension();
        $filename = time() . '_' . uniqid() . '.' . $extension;
        $fullPath = $storagePath . '/' . $filename;

        if (!Storage::exists($storagePath)) {
            Storage::makeDirectory($storagePath, 0755, true);
        }

        $imageManager = new ImageManager(['driver' => 'gd']);
        $compressionPercent = 80;

        try {
            $image = $imageManager->make($file->getRealPath())
                ->orientate()
                ->save(storage_path("app/{$fullPath}"), $compressionPercent);

            \Log::info("Image successfully compressed: " . storage_path("app/{$fullPath}"));
        } catch (\Exception $e) {
            \Log::error("Image compression failed for {$filename}: " . $e->getMessage());
            $file->storeAs($storagePath, $filename);
        }

        return Storage::url($fullPath);
    }
}
