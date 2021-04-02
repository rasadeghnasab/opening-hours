<?php

namespace App\Http\Requests\Stores;

use Illuminate\Foundation\Http\FormRequest;

class StoreShowRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $tenant_id = $this->route('tenant');
        $store = $this->route('store');

        // Note: Next lines can be replaced by the Laravel Policy system in a real world application.
        $has_access = $store->tenant_id === $tenant_id;

        if(!$has_access) {
            abort(403, "Store doesn't belong to the tenant");
        }

        return $has_access;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required']
        ];
    }
}
