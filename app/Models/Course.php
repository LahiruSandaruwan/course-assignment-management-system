<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Course extends Model
{
    use HasFactory;
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

    /**
     * Get the students enrolled in the course.
     */
    public function students(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_user')->withTimestamps();
    }

    /**
     * Get the assignments for the course.
     */
    public function assignments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Assignment::class);
    }
}
