<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = [
        'name',
        'description',
        'status',
        'instructor_id',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'status' => \App\Enums\CourseStatus::class,
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected $attributes = [
        'status' => \App\Enums\CourseStatus::Active->value,
    ];

    /**
     * Get the instructor that owns the course.
     */
    public function instructor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }
}
