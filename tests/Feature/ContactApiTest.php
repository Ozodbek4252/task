<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Jobs\ProcessImageJob;
use App\Models\Contact;

class ContactApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_contact_with_image()
    {
        // Fake the queue and S3 storage
        Queue::fake();
        Storage::fake('s3');

        // Fake image upload
        $image = UploadedFile::fake()->image('photo.jpg');

        // Send POST request to API
        $response = $this->postJson('/api/contacts', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'phone_number' => '1234567890',
            'address' => '123 Street',
            'image' => $image,
        ]);

        // Assert status code
        $response->assertStatus(201);

        // Assert the contact is stored
        $this->assertDatabaseHas('contacts', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
        ]);

        // Assert that the image processing job was dispatched
        Queue::assertPushed(ProcessImageJob::class);
    }

    public function test_can_update_contact()
    {
        Queue::fake();
        Storage::fake('s3');

        $contact = Contact::factory()->create();

        $newImage = UploadedFile::fake()->image('new.jpg');

        $response = $this->putJson("/api/contacts/{$contact->id}", [
            'first_name' => 'Updated',
            'last_name' => 'User',
            'phone_number' => '1112223333',
            'address' => 'Updated Address',
            'image' => $newImage,
        ]);

        $response->assertStatus(202);
        $this->assertDatabaseHas('contacts', ['first_name' => 'Updated']);
        Queue::assertPushed(ProcessImageJob::class);
    }

    public function test_can_delete_contact()
    {
        Queue::fake();

        $contact = Contact::factory()->create(['image_path' => 'some/image.jpg']);

        $response = $this->deleteJson("/api/contacts/{$contact->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    public function test_can_list_contacts()
    {
        Contact::factory()->count(5)->create();

        $response = $this->getJson('/api/contacts?per_page=3');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
    }

    public function test_validation_fails_without_required_fields()
    {
        $response = $this->postJson('/api/contacts', []);

        $response->assertStatus(422); // Unprocessable Entity
        $response->assertJsonValidationErrors(['first_name', 'last_name', 'phone_number']);
    }
}
