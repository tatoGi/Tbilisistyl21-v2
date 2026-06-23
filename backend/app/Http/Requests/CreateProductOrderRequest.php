<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'productId' => ['required', 'uuid', 'exists:products,id'],
            'size' => ['required', 'string', 'max:10'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc'],
            'phone' => ['required', 'string', 'min:9', 'regex:/^\+?[0-9]+$/'],
        ];
    }
}
