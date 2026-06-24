<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PostStoreRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'excerpt' => ['required', 'string', 'max:5000'],
            'category' => ['required', 'string', Rule::in(['Laravel', 'Vue', 'Database', 'DevOps', 'Career'])],
            'tags' => ['sometimes', 'array', 'max:8'],
            'tags.*' => ['string', 'max:30'],
            'keepAttachmentPaths' => ['sometimes', 'array'],
            'keepAttachmentPaths.*' => ['string'],
            'attachments' => ['sometimes', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:10240'],
        ];
    }
}
