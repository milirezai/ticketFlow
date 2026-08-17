<?php

namespace App\Rules;

use App\Models\Ticket\Ticket;
use App\Models\User\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class ExpertForCategory implements ValidationRule
{
    public function __construct(protected User $expert) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $ticket = Ticket::find($value);
        if (!$ticket) {
            $fail('The selected ticket does not exist.');
            return;
        }
        if (!$this->expert->isExpert()) {
            $fail('The selected user is not an expert.');
            return;
        }
        if (!$this->expert->expertCategories()->whereKey($ticket->ticket_category_id)->exists()) {
            $fail('The selected expert is not assigned to the ticket category.');
        }
    }
}
