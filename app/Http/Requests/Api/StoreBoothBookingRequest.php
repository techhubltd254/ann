<?php
namespace App\Http\Requests\Api;
use Illuminate\Foundation\Http\FormRequest;

class StoreBoothBookingRequest extends FormRequest
{
    public function authorize(): true { return true; }
    public function rules(): array
    {
        return [
            'exhibition_id' => 'required|exists:exhibitions,id',
            'booths' => 'required|array|min:1',
            'booths.*.booth_id' => 'required|exists:booths,id',
            'booths.*.exhibitor_name' => 'nullable|string|max:255',
            'booths.*.exhibitor_email' => 'nullable|email|max:255',
            'booths.*.exhibitor_phone' => 'nullable|string|max:20',
            'booths.*.requirements' => 'nullable|array',
            'billing_info' => 'nullable|array',
            'notes' => 'nullable|string',
        ];
    }
}
