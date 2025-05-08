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

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Storage::disk('s3')->delete($this->imagePath);
        } catch (\Exception $e) {
            Log::error("Failed to delete image: {$this->imagePath}", [
                'exception' => $e,
            ]);
        }
    }
}
