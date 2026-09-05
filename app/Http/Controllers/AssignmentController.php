<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use Illuminate\Http\Request;
use App\Http\Requests\StoreAssignmentRequest;
use App\Http\Requests\UpdateAssignmentRequest;

class AssignmentController extends Controller
{
    use \App\Traits\ApiResponses;
    use \Illuminate\Foundation\Auth\Access\AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, \App\Models\Course $course)
    {
        $this->authorize('viewAny', [\App\Models\Assignment::class, $course]);

        $query = $course->assignments();

        if ($request->user()->role === \App\Enums\Role::Student) {
            $query->where('status', \App\Enums\AssignmentStatus::Published);
        }

        return $this->success(
            \App\Http\Resources\AssignmentResource::collection($query->paginate(15))->response()->getData(true)
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAssignmentRequest $request, \App\Models\Course $course)
    {
        $this->authorize('create', [\App\Models\Assignment::class, $course]);

        $assignment = $course->assignments()->create($request->validated());

        return $this->success(new \App\Http\Resources\AssignmentResource($assignment), 'Assignment created successfully', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(\App\Models\Assignment $assignment)
    {
        $this->authorize('view', $assignment);

        return $this->success(new \App\Http\Resources\AssignmentResource($assignment));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAssignmentRequest $request, \App\Models\Assignment $assignment)
    {
        $this->authorize('update', $assignment);

        $assignment->update($request->validated());

        return $this->success(new \App\Http\Resources\AssignmentResource($assignment), 'Assignment updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(\App\Models\Assignment $assignment)
    {
        $this->authorize('delete', $assignment);

        $assignment->delete();

        return $this->success(null, 'Assignment deleted successfully');
    }
}
