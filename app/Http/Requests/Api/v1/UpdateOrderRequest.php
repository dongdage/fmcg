<?php

namespace App\Http\Requests\Api\v1;


class UpdateOrderRequest extends UserRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'num' => 'required|integer|min:1|max:10000',
            'price' => 'sometimes|required|numeric|min:0',
            'order_id' => 'required|integer',
            'pivot_id' => 'required|integer'
        ];
    }
}
