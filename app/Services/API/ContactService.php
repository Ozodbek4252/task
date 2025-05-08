<?php

namespace App\Services\API;

use App\Http\Requests\API\ContactRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Jobs\ProcessImageJob;
use App\Jobs\ImageDeleteJob;
use App\Models\Contact;

class ContactService
{
    public function store(ContactRequest $request)
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
            Log::error('Failed to create contact: ' . $e->getMessage(), [
                'request' => $request->all(),
                'exception' => $e,
            ]);

            DB::rollBack();
            return response()->json(
                [
                    'message' => 'Failed to create contact',
                    'error' => $e->getMessage(),
                ],
                is_int($e->getCode()) && $e->getCode() >= 100 && $e->getCode() <= 599 ? $e->getCode() : 500
            );
        }
    }

    public function update(ContactRequest $request, Contact $contact)
    {
        DB::beginTransaction();

        try {
            $contact->update($request->only([
                'first_name',
                'last_name',
                'phone_number',
                'address',
            ]));

            if ($request->hasFile('image')) {
                // Delete the old image if it exists
                if ($contact->image_path) {
                    // Delete the old image with job
                    ImageDeleteJob::dispatch($contact->image_path);
                }

                $tempPath = $request->file('image')->store('temp', 'local');

                // Queue the image processing job
                ProcessImageJob::dispatch($contact->id, $tempPath);
            }

            DB::commit();
            return response()->json([
                'message' => 'Contact updated successfully',
            ], 202);
        } catch (\Exception $e) {
            Log::error('Failed to update contact: ' . $e->getMessage(), [
                'request' => $request->all(),
                'exception' => $e,
            ]);

            DB::rollBack();
            return response()->json(
                [
                    'message' => 'Failed to update contact',
                    'error' => $e->getMessage(),
                ],
                is_int($e->getCode()) && $e->getCode() >= 100 && $e->getCode() <= 599 ? $e->getCode() : 500
            );
        }
    }

    public function destroy(Contact $contact)
    {
        DB::beginTransaction();

        try {
            // Delete the image if it exists
            if ($contact->image_path) {
                // Delete the old image with job
                ImageDeleteJob::dispatch($contact->image_path);
            }

            $contact->delete();

            DB::commit();
            return response()->json([
                'message' => 'Contact deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            Log::error('Failed to delete contact: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            DB::rollBack();
            return response()->json(
                [
                    'message' => 'Failed to delete contact',
                    'error' => $e->getMessage(),
                ],
                is_int($e->getCode()) && $e->getCode() >= 100 && $e->getCode() <= 599 ? $e->getCode() : 500
            );
        }
    }
}
