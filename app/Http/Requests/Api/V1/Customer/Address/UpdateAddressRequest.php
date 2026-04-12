<?php

namespace App\Http\Requests\Api\V1\Customer\Address;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('address'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        $provinceId = $this->input('province_id') ?: $this->route('address')?->province_id;
        $recipientIsRequired = is_null($user->first_name) || is_null($user->last_name) || is_null($user->mobile);

        return [
            'recipient_first_name' => [
                Rule::requiredIf($recipientIsRequired),
                'nullable',
                'required_with:recipient_last_name,recipient_mobile',
                'string',
                'max:100',
            ],
            'recipient_last_name' => [
                Rule::requiredIf($recipientIsRequired),
                'nullable',
                'required_with:recipient_first_name,recipient_mobile',
                'string',
                'max:100',
            ],
            'recipient_mobile' => [
                Rule::requiredIf($recipientIsRequired),
                'nullable',
                'required_with:recipient_first_name,recipient_last_name',
                'regex:/^09\d{9}$/',
            ],

            'province_id' => ['sometimes', 'required', 'integer', 'exists:provinces,id'],
            'city_id' => [
                'sometimes', 'required', 'integer',
                Rule::exists('cities', 'id')->where(static function ($query) use ($provinceId) {
                    return $query->where('province_id', $provinceId);
                }),
            ],

            'title' => ['nullable', 'string', 'max:50'],
            'address_line' => ['sometimes', 'required', 'string', 'max:1000'],

            'plaque' => ['nullable', 'string', 'max:20'],
            'unit' => ['nullable', 'string', 'max:20'],
            'postal_code' => ['sometimes', 'required', 'string', 'digits:10'],

            'latitude' => ['required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['required_with:latitude', 'numeric', 'between:-180,180'],

            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
