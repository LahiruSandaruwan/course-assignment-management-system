<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentSearchController extends Controller
{
    use ApiResponses;

    /**
     * Search students by name, email, or exact ID for enrollment pickers.
     * Restricted to Admin/Instructor — this is a directory search across
     * all students, more sensitive than a single course's roster.
     */
    public function index(Request $request): JsonResponse
    {
        if (! in_array($request->user()->role, [Role::Admin, Role::Instructor], true)) {
            abort(403, 'Only instructors and admins can search students.');
        }

        $query = trim((string) $request->query('q', ''));

        if ($query === '') {
            return $this->success([]);
        }

        $students = User::query()
            ->where('role', Role::Student->value)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");

                if (ctype_digit($query)) {
                    $q->orWhere('id', (int) $query);
                }
            })
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'email', 'role']);

        return $this->success(UserResource::collection($students));
    }
}
