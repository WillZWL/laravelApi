<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class IwmsAllocationRequest extends Request
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
            'warehouse' => 'required',
        ];
    }

    /**
     * Show message to blade
     *
     * @return Array
     */
    public function messages()
    {
        return [
            'warehouse.required' => 'Warehouse ID must is required',
        ];
    }
}