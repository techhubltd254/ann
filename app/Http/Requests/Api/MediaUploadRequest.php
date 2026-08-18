<?php
namespace App\Http\Requests\Api;
use Illuminate\Foundation\Http\FormRequest;

class MediaUploadRequest extends FormRequest
{
    public function authorize(): true { return true; }
    public function rules(): array
    {
        return [
            'files' => 'required|array|max:10',
            'files.*' => 'required|file|mimes:jpg,jpeg,png,webp,gif,svg,mp4,webm,mov,glb,mp3,wav,pdf|max:102400',
            'slot' => 'nullable|string|max:50',
            'owner_type' => 'nullable|string|max:255',
            'owner_id' => 'nullable|integer|min:1',
            'alt_text' => 'nullable|string|max:500',
        ];
    }
}
