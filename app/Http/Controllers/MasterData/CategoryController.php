<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $sort      = $request->get('sort', 'id');
        $direction = $request->get('direction', 'asc') === 'desc' ? 'desc' : 'asc';
        $search    = (string) $request->get('search', '');
        $perPage   = (int) min($request->get('per_page', 25), 100);
        $filters   = $request->get('filter', []);

        $allowed = ['id', 'code', 'name'];

        $query = ProductCategory::with('parent:id,name');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if (isset($filters['level']) && $filters['level'] !== '') {
            if ($filters['level'] === 'root') {
                $query->where(function ($q) {
                    $q->whereNull('parent_id')->orWhere('parent_id', 0);
                });
            } elseif ($filters['level'] === 'sub') {
                $query->where('parent_id', '>', 0);
            }
        }

        if (in_array($sort, $allowed)) {
            $query->orderBy($sort, $direction);
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        $rows = collect($paginator->items())->map(fn ($c) => [
            'id'          => $c->id,
            'code'        => $c->code,
            'name'        => $c->name,
            'parent_id'   => $c->parent_id,
            'parent_name' => $c->parent?->name,
        ]);

        return Inertia::render('MasterData/Categories', [
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
