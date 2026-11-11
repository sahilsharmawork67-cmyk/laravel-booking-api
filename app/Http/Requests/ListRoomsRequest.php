<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ListRoomsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Dates are optional, but must be supplied together.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'hotel_id' => ['sometimes', 'integer', 'exists:hotels,id'],
            'check_in' => ['required_with:check_out', 'date_format:Y-m-d'],
            'check_out' => ['required_with:check_in', 'date_format:Y-m-d', 'after:check_in'],
        ];
    }

    public function hasDateRange(): bool
    {
        return $this->filled(['check_in', 'check_out']);
    }

    public function checkIn(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m-d', $this->validated('check_in'));
    }

    public function checkOut(): CarbonImmutable
    {
        return CarbonImmutable::createFromFormat('!Y-m-d', $this->validated('check_out'));
    }
}
