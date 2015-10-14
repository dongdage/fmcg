<?php

namespace App\Http\Requests\Admin;

class CreateAdvertRequest extends Request
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required',
            'image' => 'required',
            'url' => 'required|url',
            'start_at' => 'required|date',
            'end_at' => 'date|after:start_at',
        ];
    }
}