<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $sort      = $request->get('sort', 'id');
        $direction = $request->get('direction', 'asc') === 'desc' ? 'desc' : 'asc';
        $search    = (string) $request->get('search', '');
        $perPage   = (int) min($request->get('per_page', 25), 100);
        $filters   = $request->get('filter', []);

        $allowed = ['id', 'sma_user', 'employee_username', 'employee_fnmt', 'employee_position', 'status'];

        $query = Employee::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('employee_username', 'like', "%{$search}%")
                  ->orWhere('employee_fnmt', 'like', "%{$search}%")
                  ->orWhere('employee_lnmt', 'like', "%{$search}%")
                  ->orWhere('employee_fnme', 'like', "%{$search}%")
                  ->orWhere('employee_lnme', 'like', "%{$search}%");
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if (in_array($sort, $allowed)) {
            $query->orderBy($sort, $direction);
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        $rows = collect($paginator->items())->map(fn ($e) => [
            'id'                => $e->id,
            'sma_user'          => $e->sma_user,
            'employee_username' => $e->employee_username,
            'name_th'           => trim("{$e->employee_fnmt} {$e->employee_lnmt}"),
            'name_en'           => trim("{$e->employee_fnme} {$e->employee_lnme}"),
            'employee_position' => $e->employee_position,
            'employee_team'     => $e->employee_team,
            'employee_type'     => $e->employee_type,
            'status'            => $e->status,
        ]);

        return Inertia::render('MasterData/Employees', [
            'rows'       => $rows,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'from'         => $paginator->firstItem(),
                'to'           => $paginator->lastItem(),
            ],
            'sort'    => ['column' => $sort, 'direction' => $direction],
            'search'  => $search,
            'filters' => (object) $filters,
        ]);
    }
}
