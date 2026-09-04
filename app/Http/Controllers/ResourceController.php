<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreResourceRequest;
use App\Http\Requests\UpdateResourceRequest;
use App\Models\Indoor;
use App\Models\Resource;

class ResourceController extends Controller
{
    /** Owner CRUD for a venue's bookable units. */
    public function index(Indoor $indoors)
    {
        $this->authorizeOwner($indoors);

        return view('indoor.resources', [
            'indoor' => $indoors,
            'indoors' => auth()->user()->indoors,
            'resources' => $indoors->resources()->with('activity')->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreResourceRequest $request, Indoor $indoors)
    {
        $this->authorizeOwner($indoors);

        $data = $request->validated();
        unset($data['activity']);
        $data['activity_id'] = $request->activity()->id;

        $indoors->resources()->create($data);

        return redirect()->route('resources.index', $indoors->id)->with('message', 'Resource created successfully!');
    }

    public function update(UpdateResourceRequest $request, Indoor $indoors, Resource $resource)
    {
        $this->authorizeOwner($indoors);
        $this->ensureBelongsToVenue($indoors, $resource);

        $data = $request->validated();
        unset($data['activity']);
        $data['activity_id'] = $request->activity()->id;

        $resource->update($data);

        return redirect()->route('resources.index', $indoors->id)->with('message', 'Resource updated successfully!');
    }

    public function destroy(Indoor $indoors, Resource $resource)
    {
        $this->authorizeOwner($indoors);
        $this->ensureBelongsToVenue($indoors, $resource);

        $resource->delete();

        return redirect()->route('resources.index', $indoors->id)->with('message', 'Resource deleted successfully!');
    }

    private function authorizeOwner(Indoor $indoors): void
    {
        if ($indoors->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action');
        }
    }

    private function ensureBelongsToVenue(Indoor $indoors, Resource $resource): void
    {
        if ($resource->indoor_id !== $indoors->id) {
            abort(404);
        }
    }
}
