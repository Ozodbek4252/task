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
        ini_set('memory_limit', '512M');

        try {
            $startTimeToTestLocking = microtime(true); // Start timer

            $contact = Contact::find($this->contactId);
            if (!$contact) {
                throw new \Exception('Contact not found');
            }

            // Check if image exists in the temp folder
            if (!Storage::disk('local')->exists($this->tempPath)) {
                throw new \Exception('Image not found in temp folder');
            }

            $fullPath = Storage::disk('local')->path($this->tempPath);

            $filename = ImageService::resizeAndUpload($fullPath, $this->contactId);

            // Update
            $contact->update(['image_path' => $filename]);

            // cleanup temp file
            Storage::disk('local')->delete($this->tempPath);

            $endTimeToTestLocking = microtime(true);

            $lockWaitTime = $endTimeToTestLocking - $startTimeToTestLocking; // Time in seconds
            Log::info("Lock wait time: " . $lockWaitTime . " seconds");
        } catch (\Exception $e) {
            Log::error("Failed to process image: " . $e->getMessage());
        }
    }

    public function failed(\Throwable $e)
    {
        Log::error("ProcessImageJob failed: " . $e->getMessage());

        // Clean up temp file if it still exists
        if (Storage::disk('local')->exists($this->tempPath)) {
            Storage::disk('local')->delete($this->tempPath);
            Log::info("Temp file deleted after failure: " . $this->tempPath);
        }
    }
}
