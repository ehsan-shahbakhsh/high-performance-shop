<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class VerifyRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $isEmailIntent = str_contains($this->identifier, '@');

        return [
            'identifier' => [
                'required', 'string',
                $isEmailIntent
                    ? 'email:rfc,dns'
                    : 'regex:/^09\d{9}$/'
            ],
            'code' => ['required', 'int', 'digits:4'],
        ];
    }

    public function isEmail(): bool
    {
        return filter_var($this->identifier, FILTER_VALIDATE_EMAIL);
    }

    public function getFieldType(): string
    {
        return $this->isEmail() ? 'email' : 'mobile';
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('identifier')) {
            $this->merge([
                'identifier' => convert_to_english_digits($this->identifier),
            ]);
        }
    }
}
