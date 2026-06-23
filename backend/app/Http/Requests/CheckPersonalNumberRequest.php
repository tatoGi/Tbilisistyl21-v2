<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckPersonalNumberRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'personalNumber' => ['required', 'string', 'digits:11'],
        ];
    }
}
