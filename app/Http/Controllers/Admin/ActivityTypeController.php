<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ActivityTypeController extends Controller
{
    public function index(): View
    {
        return view('admin.activity-types.index', [
            'types' => ActivityType::withCount(['services', 'games'])->orderBy('sort_order')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.activity-types.form', ['type' => new ActivityType(['icon' => 'fa-solid fa-medal', 'color' => '#4f46e5', 'unit_label' => 'Court', 'default_slot_minutes' => 60])]);
    }

    public function store(Request $request): RedirectResponse
    {
        ActivityType::create($this->validated($request));

        return redirect()->route('admin.activity-types.index')->with('message', 'Activity type added.');
    }

    public function edit(ActivityType $activityType): View
    {
        return view('admin.activity-types.form', ['type' => $activityType]);
    }

    public function update(Request $request, ActivityType $activityType): RedirectResponse
    {
        $activityType->update($this->validated($request, $activityType));

        return redirect()->route('admin.activity-types.index')->with('message', 'Activity type updated.');
    }

    public function destroy(ActivityType $activityType): RedirectResponse
    {
        if ($activityType->services()->exists()) {
            return back()->withErrors(['type' => 'Vendors still list services under this activity. Reassign them first.']);
        }
        $activityType->delete();

        return back()->with('message', 'Activity type deleted.');
    }

    protected function validated(Request $request, ?ActivityType $existing = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('activity_types', 'name')->ignore($existing?->id)],
            'icon' => ['required', 'string', 'max:60'],
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'unit_label' => ['required', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:500'],
            'requires_game' => ['nullable', 'boolean'],
            'default_slot_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'is_featured' => ['nullable', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
        ]);

        $data['requires_game'] = (bool) ($data['requires_game'] ?? false);
        $data['is_featured'] = (bool) ($data['is_featured'] ?? false);

        return $data;
    }
}
