<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ticketId' => ['required', 'string', 'max:32'],
            'personalNumber' => ['required', 'string', 'size:11', 'regex:/^\d{11}$/'],
            'eventId' => ['nullable', 'string', 'uuid'],
            'sig' => ['nullable', 'string', 'size:64'],
            'version' => ['nullable', 'integer'],
        ];
    }
}
