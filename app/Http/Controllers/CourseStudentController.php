<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnrollStudentRequest;
use App\Http\Resources\UserResource;
use App\Models\Course;
use App\Models\User;
use App\Services\EnrollmentService;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CourseStudentController extends Controller
{
    use ApiResponses, AuthorizesRequests;

    public function __construct(
        private EnrollmentService $enrollmentService
    ) {}

    /**
     * Display a listing of students enrolled in the course.
     */
    public function index(Request $request, Course $course): JsonResponse
    {
        $this->authorize('view', $course);

        $students = $course->students()->paginate(15);

        return $this->success(UserResource::collection($students)->response()->getData(true));
    }

    /**
     * Enroll a student in the course.
     */
    public function store(EnrollStudentRequest $request, Course $course): JsonResponse
    {
        $this->authorize('enroll', $course);

        $student = User::find($request->user_id);
        
        $this->enrollmentService->enrollStudent($course, $student);

        return $this->success(null, 'Student enrolled successfully', 201);
    }

    /**
     * Remove a student from the course.
     */
    public function destroy(Request $request, Course $course, User $student): JsonResponse
    {
        $this->authorize('enroll', $course); // Same permission logic applies for removal

        $this->enrollmentService->removeStudent($course, $student);

        return $this->success(null, 'Student removed successfully');
    }
}
