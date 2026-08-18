<?php
namespace App\Http\Requests\Api;
use Illuminate\Foundation\Http\FormRequest;

class EscrowStoreRequest extends FormRequest
{
    public function authorize(): true { return true; }
    public function rules(): array
    {
        return [
            'order_id' => 'required|exists:orders,id',
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|string|size:3',
            'seller_id' => 'required|exists:users,id',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
