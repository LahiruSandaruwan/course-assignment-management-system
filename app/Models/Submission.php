<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'student_id',
        'submission_text',
        'submitted_at',
        'status',
        'score',
        'instructor_feedback',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'score' => 'integer',
        'status' => \App\Enums\SubmissionStatus::class,
    ];

    protected $attributes = [
        'status' => \App\Enums\SubmissionStatus::Submitted->value,
    ];

    /**
     * Get the assignment that owns the submission.
     */
    public function assignment(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /**
     * Get the user (student) that owns the submission.
     */
    public function student(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
