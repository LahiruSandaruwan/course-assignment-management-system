<?php

namespace App\Http\Controllers;

use App\Models\Submission;
use Illuminate\Http\Request;
use App\Http\Requests\StoreSubmissionRequest;

class SubmissionController extends Controller
{
    use \App\Traits\ApiResponses;
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, \App\Models\Assignment $assignment)
    {
        $this->authorize('viewAny', [\App\Models\Submission::class, $assignment]);

        $query = $assignment->submissions();

        return $this->success(
            \App\Http\Resources\SubmissionResource::collection($query->paginate(15))->response()->getData(true)
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSubmissionRequest $request, \App\Models\Assignment $assignment, \App\Services\SubmissionService $service)
    {
        $this->authorize('create', [\App\Models\Submission::class, $assignment]);

        try {
            $submission = $service->submit(
                $assignment,
                $request->user(),
                $request->validated('submission_text')
            );

            return $this->success(new \App\Http\Resources\SubmissionResource($submission), 'Submission saved successfully', 200);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(\App\Models\Submission $submission)
    {
        $this->authorize('view', $submission);

        return $this->success(new \App\Http\Resources\SubmissionResource($submission));
    }

    /**
     * Grade the specified submission.
     */
    public function grade(\App\Http\Requests\GradeSubmissionRequest $request, \App\Models\Submission $submission, \App\Services\GradingService $service)
    {
        $this->authorize('grade', $submission);

        try {
            $submission = $service->grade(
                $submission,
                $request->validated('score'),
                $request->validated('instructor_feedback')
            );

            return $this->success(new \App\Http\Resources\SubmissionResource($submission), 'Submission graded successfully', 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error($e->getMessage(), 422, $e->errors());
        }
    }
}
