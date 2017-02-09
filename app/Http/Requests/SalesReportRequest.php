<?php

namespace App\Http\Requests;

use App\Http\Requests\Request;

class SalesReportRequest extends Request
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
            'date_type' => 'required|in:order_create_date,create_on,dispatch_date',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ];
    }
}
