<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'resource_id' => [
                'required',
                'integer',
                Rule::exists('resources', 'id')
                    ->where(fn ($query) => $query->where('indoor_id', $this->route('indoors')->id)),
            ],
            'start_time' => ['required', 'date_format:Y-m-d\TH:i'],
            'finish_time' => ['required', 'date_format:Y-m-d\TH:i', 'after:start_time'],
            'phoneNumber' => 'required|numeric|digits:10',
            'custName' => 'required|string|max:255',
            'unit_quantity' => 'sometimes|integer|min:1',
            'selected_options' => 'sometimes|array',
            'selected_options.*' => 'string|max:255',
        ];
    }
}
