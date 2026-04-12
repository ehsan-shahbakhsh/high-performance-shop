<?php

namespace App\Http\Requests\Api\V1\Customer\Address;

use App\Models\Address;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Address::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $user = $this->user();
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

            'province_id' => ['required', 'integer', 'exists:provinces,id'],
            'city_id' => [
                'required', 'integer',
                Rule::exists('cities', 'id')->where(function ($query) {
                    return $query->where('province_id', $this->province_id);
                }),
            ],

            'title' => ['nullable', 'string', 'max:50'],
            'address_line' => ['required', 'string', 'max:1000'],

            'plaque' => ['nullable', 'string', 'max:30'],
            'unit' => ['nullable', 'string', 'max:30'],
            'postal_code' => ['required', 'string', 'digits:10'],

            'latitude' => ['required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['required_with:latitude', 'numeric', 'between:-180,180'],

            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
