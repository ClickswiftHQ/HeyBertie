<?php

namespace App\Http\Requests\Admin;

use App\Rules\ValidHandle;
use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateHandleRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'handle' => ['required', 'string', new ValidHandle($this->route('business')?->id)],
        ];
    }
}
