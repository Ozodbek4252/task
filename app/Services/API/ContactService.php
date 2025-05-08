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

            $this->handleImageUpload($request, $contact);

            DB::commit();
            return response()->json([
                'message' => 'Contact created successfully',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respondWithError($e, $request, 'create');
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

            if ($request->hasFile('image') && $contact->image_path) {
                $this->handleImageDeletion($contact->image_path);
            }

            $this->handleImageUpload($request, $contact);

            DB::commit();
            return response()->json([
                'message' => 'Contact updated successfully',
            ], 202);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respondWithError($e, $request, 'update');
        }
    }

    public function destroy(Contact $contact)
    {
        DB::beginTransaction();

        try {
            if ($contact->image_path) {
                $this->handleImageDeletion($contact->image_path);
            }

            $contact->delete();

            DB::commit();
            return response()->json([
                'message' => 'Contact deleted successfully',
            ], 204);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->respondWithError($e, null, 'delete');
        }
    }

    private function handleImageUpload($request, $contact)
    {
        if ($request->hasFile('image')) {
            $tempPath = $request->file('image')->store('temp', 'local');
            ProcessImageJob::dispatch($contact->id, $tempPath);
        }
    }

    private function handleImageDeletion($imagePath)
    {
        ImageDeleteJob::dispatch($imagePath);
    }

    private function respondWithError(\Exception $e, $request = null, $action = 'operation')
    {
        Log::error("Failed to {$action} contact: " . $e->getMessage(), [
            'request' => $request ? $request->all() : [],
            'exception' => $e,
        ]);

        return response()->json([
            'message' => "Failed to {$action} contact",
            'error' => $e->getMessage(),
        ], $this->getStatusCode($e));
    }

    private function getStatusCode(\Exception $e)
    {
        $code = $e->getCode();
        return (is_int($code) && $code >= 100 && $code <= 599) ? $code : 500;
    }
}
