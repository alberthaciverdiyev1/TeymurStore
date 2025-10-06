<?php

use Intervention\Image\Image;

if (!function_exists('compressAndUploadImage'))
{
    function compressAndUploadImage(Request $request): string
    {
        $request->validate([
            'image' => 'required|image',
        ]);

        $image = $request->file('images');

        $originalSize = $image->getSize();

        $filename = time() . '.' . $image->getClientOriginalExtension();
        $path = public_path('uploads/' . $filename);

        if ($originalSize > 0) {
            $compressedSize = $originalSize * 0.6; // 60%-ə endir
            Image::make($image)
                ->encode($image->getClientOriginalExtension(), 60) // keyfiyyət 60%
                ->save($path);
        } else {
            $image->move(public_path('uploads'), $filename);
        }

        return response()->json([
            'success' => true,
            'path' => url('uploads/' . $filename)
        ]);
    }
}
