<?php
namespace App\Http\Requests\Api;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): true { return true; }
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'account_type' => 'nullable|string|in:individual,sme,school,county,ministry,admin,nis,superadmin',
            'phone' => 'nullable|string|max:20',
            'county_id' => 'nullable|integer|exists:counties,id',
        ];
    }
}
