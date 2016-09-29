<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\Request;

class SupplierProductRequest extends Request
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
            'supplier_id' => 'required',
            'declared_desc' => 'required',
            'declared_value' => 'required',
            'cost' => 'required',
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
            'supplier_id.required' => 'Supplier ID must is required',
            'declared_desc.required' => 'Declared description must is required',
            'declared_value.required' => 'Declared value must is required',
            'cost.required' => 'Supplier cost must is required',
        ];
    }
}
