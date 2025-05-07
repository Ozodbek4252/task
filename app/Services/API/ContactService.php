<?php

namespace App\Services\API;

use App\Http\Requests\API\ContactStoreRequest;
use Illuminate\Support\Facades\DB;
use App\Jobs\ProcessImageJob;
use App\Models\Contact;

class ContactService
{
    public function store(ContactStoreRequest $request)
    {
        DB::beginTransaction();

        try {
            $contact = Contact::create($request->only([
                'first_name',
                'last_name',
                'phone_number',
                'address',
            ]));

            if ($request->hasFile('image')) {
                $tempPath = $request->file('image')->store('temp', 'local');

                // Queue the image processing job
                ProcessImageJob::dispatch($contact->id, $tempPath);
            }

            DB::commit();
            return response()->json([
                'message' => 'Contact created successfully',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create contact',
                'error' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }
}
