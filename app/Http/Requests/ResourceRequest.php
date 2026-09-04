<?php

namespace App\Http\Requests;

use App\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class ResourceRequest extends FormRequest
{
    /** Follow the label/@error markup used in indoor/create.blade.php so the two render alike. */

    public function authorize(): bool
    {
        return true;
    }

    /** Activity the submitted custom_fields belong to: posted value first, existing resource second. */
    public function activity(): Activity
    {
        if ($this->filled('activity_id')) {
            $activity = Activity::find($this->integer('activity_id'));
            if ($activity) {
                return $activity;
            }
        }

        if ($this->filled('activity')) {
            $activity = Activity::where('slug', $this->string('activity'))->first();
            if ($activity) {
                return $activity;
            }
        }

        $resource = $this->route('resource');

        return $resource?->activity ?? Activity::where('slug', 'other')->first() ?? Activity::first();
    }

    /** Custom fields validated dynamically against the activity's schema in config/activities.php. */
    public static function dynamicRules(Activity $activity): array
    {
        $rules = [];

        foreach ($activity->fields() as $field => $def) {
            $key = "custom_fields.{$field}";

            switch ($def['type'] ?? 'text') {
                case 'number':
                    $fieldRules = ['nullable', 'numeric'];
                    if (isset($def['min'])) {
                        $fieldRules[] = "min:{$def['min']}";
                    }
                    if (isset($def['max'])) {
                        $fieldRules[] = "max:{$def['max']}";
                    }
                    $rules[$key] = $fieldRules;
                    break;
                case 'select':
                    $rules[$key] = ['nullable', 'string', Rule::in($def['options'] ?? [])];
                    break;
                case 'boolean':
                    $rules[$key] = ['nullable', 'boolean'];
                    break;
                case 'list':
                    $rules[$key] = ['nullable', 'array'];
                    $rules["custom_fields.{$field}.*"] = ['string', 'max:255'];
                    break;
                default:
                    $rules[$key] = ['nullable', 'string', 'max:255'];
                    break;
            }
        }

        return $rules;
    }

    protected function baseRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'activity_id' => ['nullable', 'integer', 'exists:activities,id'],
            'activity' => ['nullable', 'string', 'exists:activities,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'rate' => ['required', 'numeric', 'min:0'],
            'pricing_unit' => ['required', Rule::in(array_keys(config('activities.pricing_units')))],
            'min_duration_minutes' => ['nullable', 'integer', 'min:15', 'max:1440'],
            'slot_increment_minutes' => ['nullable', 'integer', 'min:5', 'max:1440'],
            'custom_fields' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function rules(): array
    {
        return array_merge($this->baseRules(), static::dynamicRules($this->activity()));
    }
}
