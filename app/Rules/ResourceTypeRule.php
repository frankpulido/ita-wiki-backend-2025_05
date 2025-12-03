<?php

declare (strict_types= 1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Resource;

class ResourceTypeRule implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!in_array($value, Resource::VALID_TYPES)) {
            $fail('Las categorías válidas son las siguientes: ' . implode(', ', Resource::VALID_TYPES));
        }
    }
}
