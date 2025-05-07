<?php

namespace App\Services;

use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Illuminate\Support\Str;

class ImageService
{
    public static function resizeAndUpload(string $localPath, int $contactId): ?string
    {
        $manager = new ImageManager(new Driver());
        $image = $manager->read($localPath);

        $originalWidth = $image->width();
        $originalHeight = $image->height();

        $newWidth = 300;
        $newHeight = ($newWidth / $originalWidth) * $originalHeight;

        $image = $image->resize($newWidth, (int) $newHeight, function ($constraint) {
            $constraint->upsize();
        })->toWebp();

        $filename = "contacts/{$contactId}/" . Str::uuid() . ".webp";

        Storage::disk('s3')->put($filename, (string) $image);

        if (!Storage::disk('s3')->exists($filename)) {
            throw new \Exception('Failed to upload image to S3');
        }

        return $filename;
    }
}
