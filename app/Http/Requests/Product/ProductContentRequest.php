<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\Request;

class ProductContentRequest extends Request
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
            'lang_id' => 'required',
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
            'lang_id.required' => 'Lang ID must is required',
        ];
    }
}
