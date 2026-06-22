<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $sort      = $request->get('sort', 'id');
        $direction = $request->get('direction', 'asc') === 'desc' ? 'desc' : 'asc';
        $search    = (string) $request->get('search', '');
        $perPage   = (int) min($request->get('per_page', 25), 100);

        $allowed = ['id', 'code', 'name', 'phone', 'email', 'address'];

        $query = Warehouse::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if (in_array($sort, $allowed)) {
            $query->orderBy($sort, $direction);
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        return Inertia::render('MasterData/Warehouses', [
            'rows'       => $paginator->items(),
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
