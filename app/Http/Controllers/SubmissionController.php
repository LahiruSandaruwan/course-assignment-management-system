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
    public function store(StoreSubmissionRequest $request, \App\Models\Assignment $assignment)
    {
        $this->authorize('create', [\App\Models\Submission::class, $assignment]);

        // upsert semantics per unique-constraint decision
        $submission = $assignment->submissions()->updateOrCreate(
            ['student_id' => $request->user()->id],
            [
                'submission_text' => $request->validated('submission_text'),
                'submitted_at' => now(),
                'status' => \App\Enums\SubmissionStatus::Submitted->value,
            ]
        );

        return $this->success(new \App\Http\Resources\SubmissionResource($submission), 'Submission saved successfully', 200); // 200 for upsert
    }

    /**
     * Display the specified resource.
     */
    public function show(\App\Models\Submission $submission)
    {
        $this->authorize('view', $submission);

        return $this->success(new \App\Http\Resources\SubmissionResource($submission));
    }
}
