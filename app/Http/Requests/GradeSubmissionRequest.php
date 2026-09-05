<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GradeSubmissionRequest extends FormRequest
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
        return [
            'score' => [
                'required',
                'integer',
                'min:0',
                function ($attribute, $value, $fail) {
                    $maxScore = $this->route('submission')?->assignment?->max_score;
                    if ($maxScore !== null && $value > $maxScore) {
                        $fail("The score must not exceed the assignment's maximum score ({$maxScore}).");
                    }
                },
            ],
            'instructor_feedback' => 'nullable|string',
        ];
    }
}
