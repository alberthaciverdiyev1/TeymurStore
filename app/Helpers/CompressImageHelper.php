<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Illuminate\Support\Str;

if (!function_exists('compressAndUploadImage')) {
    /**
     * Compress and store uploaded image in storage/app/public
     *
     * @param UploadedFile $file
     * @param string|null $subDir
     * @param string|null $fileNamePrefix
     * @return string Public URL
     */
    function compressAndUploadImage(UploadedFile $file, string $subDir = null, string $fileNamePrefix = null): string
    {
        $subDir = $subDir ? trim($subDir, '/') : '';
        $storagePath = $subDir ? "public/{$subDir}" : "public";

        if (!Storage::exists($storagePath)) {
            Storage::makeDirectory($storagePath, 0755, true);
        }

        $originalExtension = strtolower($file->getClientOriginalExtension());
        $baseName = $fileNamePrefix ? $fileNamePrefix.'-' : '';
        $baseName .= Str::uuid();

        $imageManager = new ImageManager(['driver' => 'gd']);
        $compressionPercent = 80;

        try {
            if (in_array($originalExtension, ['jpg', 'jpeg'])) {
                $filename = $baseName.'.'.$originalExtension;
                $fullPath = $storagePath.'/'.$filename;

                $imageManager->make($file->getRealPath())
                    ->orientate()
                    ->save(storage_path("app/{$fullPath}"), $compressionPercent);

            } elseif ($originalExtension === 'png') {
                $filename = $baseName.'.webp';
                $fullPath = $storagePath.'/'.$filename;

                $imageManager->make($file->getRealPath())
                    ->orientate()
                    ->encode('webp', $compressionPercent)
                    ->save(storage_path("app/{$fullPath}"));

            } elseif ($originalExtension === 'webp') {
                $filename = $baseName.'.webp';
                $fullPath = $storagePath.'/'.$filename;

                $imageManager->make($file->getRealPath())
                    ->orientate()
                    ->save(storage_path("app/{$fullPath}"), $compressionPercent);

            } elseif ($originalExtension === 'svg') {
                $filename = $baseName.'.svg';
                $fullPath = $storagePath.'/'.$filename;
                $file->storeAs($storagePath, $filename);

            } else {
                $filename = $baseName.'.'.$originalExtension;
                $fullPath = $storagePath.'/'.$filename;
                $file->storeAs($storagePath, $filename);
            }

        } catch (\Exception $e) {
            \Log::error("Image compression failed for {$file->getClientOriginalName()}: ".$e->getMessage());
            $filename = $baseName.'.'.$originalExtension;
            $fullPath = $storagePath.'/'.$filename;
            $file->storeAs($storagePath, $filename);
        }

        return Storage::url($fullPath);
    }
}
