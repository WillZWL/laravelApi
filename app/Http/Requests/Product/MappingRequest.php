<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\Request;

class MappingRequest extends Request
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
            'sku' => 'required',
            'merchant_id' => 'required',
            'merchant_sku' => 'required',
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
            'sku.required' => 'Product sku must is required',
            'merchant_id.required' => 'Merchant ID must is required',
            'merchant_sku.required' => 'Merchant Sku must is required',
        ];
    }
}
