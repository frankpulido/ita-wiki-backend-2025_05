<?php

declare (strict_types= 1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Role;

class RoleNameRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!in_array($value, Role::VALID_ROLES)) {
            $fail('Los roles válidos son las siguientes: ' . implode(', ', Role::VALID_ROLES));
        }
    }
}
