<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTicketOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'ticketId' => ['required', 'uuid', 'exists:tickets,id'],
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z\s\-]+$/'],
            'surname' => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z\s\-]+$/'],
            'email' => ['required', 'email:rfc'],
            'personalNumber' => ['required', 'string', 'digits:11'],
        ];
    }
}
