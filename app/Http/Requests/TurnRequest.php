<?php

namespace App\Http\Requests;

use App\Http\Requests\JsonRequest;

class TurnRequest extends JsonRequest
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
            'game' => 'required|exists:games,games,time_finished,NOT_NULL|numeric',
            'figure' => 'required|numeric',
            'x' => 'required|numeric|between:1,8',
            'y' => 'required|numeric|between:1,8',
        ];
    }
}
