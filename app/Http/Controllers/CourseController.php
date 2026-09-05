<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Services\CourseService;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CourseController extends Controller
{
    use ApiResponses, AuthorizesRequests;

    public function __construct(
        private CourseService $courseService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Course::class);

        $courses = $this->courseService->getCoursesForUser($request->user());

        return $this->success(CourseResource::collection($courses));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCourseRequest $request): JsonResponse
    {
        $this->authorize('create', Course::class);

        $course = $this->courseService->createCourse($request->validated(), $request->user());

        return $this->success(new CourseResource($course), 'Course created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Course $course): JsonResponse
    {
        $this->authorize('view', $course);

        $course->load('instructor');

        return $this->success(new CourseResource($course));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCourseRequest $request, Course $course): JsonResponse
    {
        $this->authorize('update', $course);

        $this->courseService->updateCourse($course, $request->validated());

        return $this->success(new CourseResource($course), 'Course updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Course $course): JsonResponse
    {
        $this->authorize('delete', $course);

        $this->courseService->deleteCourse($course);

        return $this->success(null, 'Course deleted successfully');
    }
}
