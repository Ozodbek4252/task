<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Bus\Queueable;

class ImageDeleteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $imagePath;

    public function __construct(string $imagePath)
    {
        $this->imagePath = $imagePath;
    }

    public function handle(): void
    {
        try {
            if (Storage::disk('s3')->exists($this->imagePath)) {
                Storage::disk('s3')->delete($this->imagePath);
            } else {
                Log::warning("Image to delete not found on S3: {$this->imagePath}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to delete image: {$this->imagePath}", [
                'Error' => $e,
            ]);
        }
    }
}
