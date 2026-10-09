<?php

namespace App\Http\Requests;

use App\Enums\SyncTargetType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSyncTargetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize the synchronization target data before validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => strtolower(trim((string) $this->input('name'))),
        ]);
    }

    /**
     * Get the validation rules for storing a synchronization target.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('sync_targets', 'name')],
            'type' => ['required', Rule::enum(SyncTargetType::class)],
        ];
    }
}