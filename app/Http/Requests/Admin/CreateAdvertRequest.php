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
            'category_id' => 'sometimes|required|exists:category,id',
            'name' => 'required',
            'image' => 'required',
            'url' => 'required|url',
            'sort' => 'required|integer',
            'start_at' => 'required|date',
            'end_at' => 'date|after:start_at',
        ];
    }
}
