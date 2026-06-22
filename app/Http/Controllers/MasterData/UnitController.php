<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UnitController extends Controller
{
    public function index(Request $request)
    {
        $sort      = $request->get('sort', 'id');
        $direction = $request->get('direction', 'asc') === 'desc' ? 'desc' : 'asc';
        $search    = (string) $request->get('search', '');
        $perPage   = (int) min($request->get('per_page', 25), 100);

        $allowed = ['id', 'code', 'name'];

        $query = Unit::with('baseUnit:id,name,code');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (in_array($sort, $allowed)) {
            $query->orderBy($sort, $direction);
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        $rows = collect($paginator->items())->map(fn ($u) => [
            'id'              => $u->id,
            'code'            => $u->code,
            'name'            => $u->name,
            'base_unit_id'    => $u->base_unit,
            'base_unit_name'  => $u->baseUnit?->name,
            'operator'        => $u->operator,
            'operation_value' => $u->operation_value,
        ]);

        return Inertia::render('MasterData/Units', [
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
            'filters' => (object) [],
        ]);
    }
}
