<?php

namespace App\Http\Requests\Product;

use App\Http\Requests\Request;

class CreateRequest extends Request
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
            'name' => 'required',
            'prod_grp_cd_name' => 'required',
            'colour_id' => 'required',
            'version_id' => 'required',
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
            'name.required' => 'Product name must is required',
            'prod_grp_cd_name.required' => 'Product group name must is required',
            'colour_id.required' => 'Product colour_id must is required',
            'version_id.required' => 'Product version_id must is required',
            'brand_id.required' => 'Brand ID must is required',
            'cat_id.email' => 'Category ID must is required',
        ];
    }
}
