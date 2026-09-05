<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assignment extends Model
{
    /** @use HasFactory<\Database\Factories\AssignmentFactory> */
    use HasFactory;

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'due_date',
        'max_score',
        'status',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'max_score' => 'integer',
        'status' => \App\Enums\AssignmentStatus::class,
    ];

    protected $attributes = [
        'status' => \App\Enums\AssignmentStatus::Draft->value,
    ];

    /**
     * Get the course that owns the assignment.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
