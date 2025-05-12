<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Requests\API\ContactRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use App\Services\API\ContactService;
use Illuminate\Http\UploadedFile;
use App\Jobs\ProcessImageJob;

class ContactServiceTest extends TestCase
{
    public function test_it_stores_a_contact_and_dispatches_image_job()
    {
        // Fake the queue and storage
        Queue::fake();
        Storage::fake('local');

        // Mock request
        $request = ContactRequest::create('/contacts', 'POST', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone_number' => '123456789',
            'address' => 'Test Address',
        ], [], [
            'image' => UploadedFile::fake()->image('avatar.jpg')
        ]);

        // Act
        $service = new ContactService();
        $response = $service->store($request);

        // Assert
        $this->assertEquals(201, $response->status());
        $this->assertDatabaseHas('contacts', [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);

        Queue::assertPushed(ProcessImageJob::class);
    }
}
