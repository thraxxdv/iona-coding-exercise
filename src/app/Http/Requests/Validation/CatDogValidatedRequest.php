<?php

namespace App\Http\Requests\Validation;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request class to handle page and limit data validation
 */
class CatDogValidatedRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }
    
    public function rules()
    {
        return [
            'page' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer', 'not_regex:/^0/']
        ];
    }
}