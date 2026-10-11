<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $phoneRegex = '/^(?:\+251|251|0)?([79]\d{8})$/';

        return [
            'customer_name' => ['required', 'string', 'min:2', 'max:100'],
            'phone' => [
                'required',
                'string',
                "regex:{$phoneRegex}",
            ],
            'alt_phone' => [
                'nullable',
                'string',
                "regex:{$phoneRegex}",
            ],
            'city' => ['required', 'string', 'max:100'],
            'delivery_method' => ['required', 'string', 'in:delivery,pickup'],
            'address' => [
                'required_if:delivery_method,delivery',
                'nullable',
                'string',
                'max:500',
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
            // Honeypot field (must remain empty to prevent spam bots)
            'website' => ['nullable', 'max:0'],
            'idempotency_token' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Custom error messages for Ethiopian phone validation and honeypot.
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Please provide a valid Ethiopian mobile number (e.g. 0911 234567 or 0711 234567).',
            'alt_phone.regex' => 'Please provide a valid alternate Ethiopian mobile number.',
            'address.required_if' => 'Please provide your delivery address or landmark in Addis Ababa.',
            'website.max' => 'Spam detected. Order rejected.',
        ];
    }

    /**
     * Normalize an Ethiopian phone number to canonical +251XXXXXXXXX format.
     */
    public function normalizePhone(string $field = 'phone'): ?string
    {
        $value = $this->input($field);
        if (!$value) {
            return null;
        }

        $clean = preg_replace('/[^\d+]/', '', (string) $value);

        if (preg_match('/^(?:\+251|251|0)?([79]\d{8})$/', $clean, $matches)) {
            return '+251' . $matches[1];
        }

        return $clean;
    }
}
