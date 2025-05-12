<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Services\ImageService;
use App\Jobs\ProcessImageJob;
use App\Models\Contact;
use Mockery;

class ProcessImageJobTest extends TestCase
{
    public function test_job_logs_error_if_image_missing()
    {
        // Create a fake contact
        $contact = Contact::factory()->create();
        $tempPath = 'temp/missing_image.jpg';

        // Fake local disk without the image
        Storage::fake('local');

        // Spy on logging
        Log::shouldReceive('error')
            ->once()
            ->with(Mockery::on(function ($message) use ($tempPath) {
                return str_contains($message, $tempPath);
            }));

        // Run the job
        $job = new ProcessImageJob($contact->id, $tempPath);
        $job->handle();
    }

    public function test_job_processes_and_deletes_temp_image()
    {
        $contact = Contact::factory()->create();
        $tempPath = 'temp/fake.jpg';

        // Put a fake image in local disk
        Storage::fake('local');
        Storage::disk('local')->put($tempPath, 'dummy content');

        // Fake image processing result
        $mockImageService = Mockery::mock('alias:' . ImageService::class);
        $mockImageService->shouldReceive('resizeAndUpload')
            ->once()
            ->andReturn('uploads/final.jpg');

        Log::shouldReceive('info')->once(); // For timing log
        Log::shouldReceive('info')->once(); // For temp file deleted

        $job = new ProcessImageJob($contact->id, $tempPath);
        $job->handle();

        // Make sure the temp file is deleted
        Storage::disk('local')->assertMissing($tempPath);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'image_path' => 'uploads/final.jpg',
        ]);
    }
}
