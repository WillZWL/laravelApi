<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\Request;

class UpdateRequest extends Request
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
            'name' => 'required',
            'brand_id' => 'required',
            'cat_id' => 'required',
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
            'name.required' => 'Product name must is required',
            'brand_id.required' => 'Brand ID must is required',
            'cat_id.email' => 'Category ID must is required',
        ];
    }
}
