<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDomainRequest;
use App\Http\Requests\UpdateDomainRequest;
use App\Http\Resources\DomainResource;
use App\Models\Domain;

class DomainController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:6,1')->only('store');
    }

    public function index()
    {
        return DomainResource::collection(user()->domains()->with('defaultRecipient')->withCount('aliases')->latest()->get());
    }

    public function show($id)
    {
        $domain = user()->domains()->findOrFail($id);

        return new DomainResource($domain->load('defaultRecipient')->loadCount('aliases'));
    }

    public function store(StoreDomainRequest $request)
    {
        $domain = new Domain;
        $domain->domain = $request->domain;

        if (! $domain->checkVerification()) {
            return response('Verification record not found, please add the following TXT record to your domain: aa-verify='.sha1(config('anonaddy.secret').user()->id.user()->domains->count()), 404);
        }

        user()->domains()->save($domain);

        $domain->markDomainAsVerified();

        return new DomainResource($domain->refresh()->load('defaultRecipient')->loadCount('aliases'));
    }

    public function update(UpdateDomainRequest $request, $id)
    {
        $domain = user()->domains()->findOrFail($id);

        if ($request->has('description')) {
            $domain->description = $request->description;
        }

        if ($request->has('from_name')) {
            $domain->from_name = $request->from_name;
        }

        if ($request->has('auto_create_regex')) {
            $domain->auto_create_regex = $request->auto_create_regex;
        }

        $domain->save();

        return new DomainResource($domain->refresh()->load('defaultRecipient')->loadCount('aliases'));
    }

    public function destroy($id)
    {
        $domain = user()->domains()->findOrFail($id);

        $domain->delete();

        return response('', 204);
    }
}
