<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

/**
*
*/
class MarketplaceCourierMappingRequest extends Request
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
            'courier_id' => 'required',
            'marketplace' => 'required',
            'marketplace_courier_name' => 'required'
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
            'courier_id' => 'ESG Courier ID Must Required',
            'marketplace' => 'Marketplace Must Required',
            'marketplace_courier_name' => 'Marketplace Courier Name Must Required'
        ];
    }
}