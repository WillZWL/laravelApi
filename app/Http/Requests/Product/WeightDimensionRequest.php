<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\Request;

class WeightDimensionRequest extends Request
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
            'vol_weight' => 'required',
            'weight' => 'required',
            'length' => 'required',
            'width' => 'required',
            'height' => 'required',
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
            'vol_weight.required' => 'Merchant ID must is required',
            'weight.required' => 'Merchant Sku must is required',
            'length.required' => 'Merchant Sku must is required',
            'width.required' => 'Merchant Sku must is required',
            'height.required' => 'Merchant Sku must is required',
        ];
    }
}
