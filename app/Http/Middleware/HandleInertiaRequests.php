<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use App\Models\Department;
use App\Models\Location;
use App\Models\ItemCategory;
use App\Models\User;
use App\Http\Resources\DepartmentResource;
use App\Http\Resources\LocationResource;
use App\Http\Resources\ItemCategoryResource;
use App\Http\Resources\UserResource;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->user_id ?? $request->user()->id,
                    'name' => $request->user()->full_name ?? $request->user()->name,
                    'username' => $request->user()->username,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role,
                    'department' => $request->user()->department,
                    'is_active' => (bool) $request->user()->is_active,
                ] : null,
            ],
            'masters' => function () {
                return [
                    'departments' => DepartmentResource::collection(Department::where('is_active', true)->get()),
                    'locations' => LocationResource::collection(Location::where('is_active', true)->get()),
                    'categories' => ItemCategoryResource::collection(ItemCategory::where('is_active', true)->get()),
                    // engineers list is small, used for dropdown; return id/name
                    'engineers' => UserResource::collection(User::where('role', 'engineer')->where('is_active', true)->limit(50)->get()),
                ];
            },
        ];
    }
}
