<?php

declare (strict_types= 1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Role;
use App\Rules\GithubIdRule;
use App\Rules\RoleNameRule;

class CreateRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'authorized_github_id' => [new GithubIdRule(), 'exists:roles,github_id'],
            'github_id' => [
                new GithubIdRule(),
                function ($attribute, $value, $fail) {
                    if (Role::where('github_id', $value)->exists()) {
                        $fail('Este github_id ya está creado.');
                    }
                }
            ],
            'role' => ['required', 'string', new RoleNameRule()],
        ];
    }   
}
