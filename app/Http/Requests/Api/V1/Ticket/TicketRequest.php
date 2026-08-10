<?php

namespace App\Http\Requests\Api\V1\Ticket;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class TicketRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->method() == 'PUT' || $this->method() == 'PATCH'){
            return [
                'subject' => ['nullable','string','max:250','min:3'],
                'ticket_category_id' => ['nullable','int','exists:ticket_categories,id'],
                'ticket_priority_id' => ['nullable','int','exists:ticket_priorities,id'],
                'ticket_status_id' => ['nullable','int','int','exists:ticket_statuses,id']
            ];
        }else{
            return [
                'subject' => ['required','string','max:250','min:3'],
                'content' => ['required','string','max:300','min:10'],
                'file' => ['file', File::types(['pdf','image','zip'])->max('5mb') ],
                'ticket_category_id' => ['required','int','exists:ticket_categories,id'],
                'ticket_priority_id' => ['required','int','exists:ticket_priorities,id'],
                'ticket_status_id' => ['required','int','int','exists:ticket_statuses,id'],
            ];
        }
    }
}
