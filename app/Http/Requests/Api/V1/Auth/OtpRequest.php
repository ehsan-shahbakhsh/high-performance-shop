<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class OtpRequest extends FormRequest
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
        $this->merge([
            'identifier' => $this->toEnglishNumbers($this->identifier),
        ]);
    }

    private function toEnglishNumbers(string $string): string
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $string = str_replace($persian, $english, $string);
        return str_replace($arabic, $english, $string);
    }
}
