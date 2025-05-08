<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\ContactRequest;
use App\Http\Resources\API\ContactResource;
use App\Services\API\ContactService;
use Illuminate\Http\Request;
use App\Models\Contact;

class ContactController extends Controller
{
    public function __construct(protected ContactService $service) {}

    public function index(Request $request)
    {
        $contacts = Contact::paginate($request->input('per_page', 10));

        return ContactResource::collection($contacts);
    }

    public function store(ContactRequest $request)
    {
        return $this->service->store($request);
    }

    public function update(ContactRequest $request, Contact $contact)
    {
        return $this->service->update($request, $contact);
    }
}
