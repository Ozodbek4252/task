<?php

namespace Database\Seeders;

use App\Models\Contact;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 0; $i < 10; $i++) {
            Contact::create([
                'first_name' => 'John' . $i,
                'last_name' => 'Doe' . $i,
                'phone_number' => '123456789' . $i,
                'address' => '123 Main St' . $i,
                'image_path' => 'path/to/image' . $i,
            ]);
        }
    }
}
