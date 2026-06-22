<?php

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     */
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Props shared with every Inertia response.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();

        return [
            ...parent::share($request),

            'auth' => [
                'user' => fn () => $user ? [
                    'id'           => $user->id,
                    'sma_id'       => $user->sma_id,
                    'display_name' => $user->display_name,
                    'is_root'      => (bool) $user->root,
                    'employee_record' => $user->employeeRecord ? [
                        'id'                => $user->employeeRecord->id,
                        'sma_user'          => $user->employeeRecord->sma_user,
                        'employee_username' => $user->employeeRecord->employee_username,
                        'employee_position' => $user->employeeRecord->employee_position,
                        'employee_fnme'     => $user->employeeRecord->employee_fnme,
                        'employee_lnme'     => $user->employeeRecord->employee_lnme,
                        'employee_photo'    => $user->employeeRecord->employee_photo,
                    ] : null,
                ] : null,

                'roles'       => fn () => $user ? $user->getRoleNames()->all() : [],
                'permissions' => fn () => $user ? $user->getAllPermissions()->pluck('name')->all() : [],
            ],

            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error'   => fn () => $request->session()->get('error'),
            ],

            'app' => [
                'name' => config('app.name'),
            ],
        ];
    }
}
