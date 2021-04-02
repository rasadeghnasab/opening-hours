<?php

namespace App\Http\Requests\OpenHours;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OpenHourStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            // I commented the timeable since it is checked in the route parameters in the RouteServiceProvider
            // 'timeable' => ['required', Rule::in(array_keys(config('timeables')))],
            'day' => ['required',  Rule::in(range(0,6))],
            'from' => ['required', 'date_format:H:i'],
            'to' => ['required', 'after:from', 'date_format:H:i'],
        ];
    }
}
