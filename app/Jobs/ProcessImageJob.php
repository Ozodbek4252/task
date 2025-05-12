<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\ImageService;
use Illuminate\Bus\Queueable;
use App\Models\Contact;

class ProcessImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 10;
    public $timeout = 180;

    protected string $tempPath;
    protected int $contactId;

    public function __construct(int $contactId, string $tempPath)
    {
        $this->tempPath = $tempPath;
        $this->contactId = $contactId;
    }

    public function handle(): void
    {
        ini_set('memory_limit', '512M');

        try {
            $startTime = microtime(true);

            $contact = Contact::findOrFail($this->contactId);

            // Check if image exists in the temp folder
            if (!Storage::disk('local')->exists($this->tempPath)) {
                throw new \Exception('Image not found at path: ' . $this->tempPath);
            }

            $localPath = Storage::disk('local')->path($this->tempPath);

            $imagePath = ImageService::resizeAndUpload($localPath, $this->contactId);

            $contact->update(['image_path' => $imagePath]);

            $duration = round(microtime(true) - $startTime, 3); // Time in seconds
            Log::info("Image processed for contact #{$contact->id} in {$duration}s");
        } catch (\Exception $e) {
            Log::error("Failed to process image: " . $e->getMessage());
        }

        $this->cleanUpTempFile();
    }

    public function failed(\Throwable $e)
    {
        Log::error("ProcessImageJob failed: " . $e->getMessage());
        $this->cleanUpTempFile();
    }

    private function cleanUpTempFile(): void
    {
        if (Storage::disk('local')->exists($this->tempPath)) {
            Storage::disk('local')->delete($this->tempPath);
            Log::info("Temp file deleted: " . $this->tempPath);
        }
    }
}
