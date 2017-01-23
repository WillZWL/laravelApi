<?php

namespace App\Http\Requests\MarketplaceContentExport;

use App\Http\Requests\Request;

class DownloadRequest extends Request
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
            'marketplace' => 'required',
            'marketplace_id' => 'required',
            'country_id' => 'required',
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
            'marketplace.required' => 'Marketplace must is required',
            'marketplace_id.required' => 'Marketplace ID must is required',
            'country_id.required' => 'Country ID must is required',
        ];
    }
}
