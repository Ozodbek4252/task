<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;
use App\Models\Contact;

use Intervention\Image\ImageManager;
use Intervention\Image\Facades\Image;

class ProcessImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tempPath;
    protected $contactId;

    public function __construct($contactId, $tempPath)
    {
        $this->tempPath = $tempPath;
        $this->contactId = $contactId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $contact = Contact::find($this->contactId);
            if (!$contact) return;

            $fullPath = Storage::disk('local')->path($this->tempPath);

            $manager = new ImageManager(new Driver());
            $image = $manager->read($fullPath);

            // Get original width and height
            $originalWidth = $image->width();
            $originalHeight = $image->height();

            // Calculate the new height based on the aspect ratio
            $newWidth = 300;
            $newHeight = ($newWidth / $originalWidth) * $originalHeight;

            $image = $image->resize($newWidth, (int) $newHeight, function ($constraint) {
                $constraint->upsize();
            })->toWebp();
            // ->toJpeg();

            // Define S3 path
            $filename = 'contacts/' . uniqid() . '.webp';

            // Upload to S3
            Storage::disk('s3')->put($filename, (string) $image);

            if (!Storage::disk('s3')->exists($filename)) {
                throw new \Exception('Failed to upload image to S3');
            }

            // Update
            $contact->update(['image_path' => $filename]);

            // cleanup temp file
            Storage::disk('local')->delete($this->tempPath);
        } catch (\Exception $e) {
            Log::error("Failed to process image: " . $e->getMessage());
        }
    }
}
