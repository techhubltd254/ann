<?php
namespace App\Http\Requests\Api;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketBookingRequest extends FormRequest
{
    public function authorize(): true { return true; }
    public function rules(): array
    {
        return [
            'ticket_type_id' => 'required|exists:ticket_types,id',
            'quantity' => 'required|integer|min:1|max:100',
            'holder_name' => 'nullable|string|max:255',
            'holder_email' => 'nullable|email|max:255',
        ];
    }
}
