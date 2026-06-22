<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $sort      = $request->get('sort', 'id');
        $direction = $request->get('direction', 'asc') === 'desc' ? 'desc' : 'asc';
        $search    = (string) $request->get('search', '');
        $perPage   = (int) min($request->get('per_page', 25), 100);
        $filters   = $request->get('filter', []);

        $allowed = ['id', 'code', 'name', 'price', 'cost', 'status'];

        $query = Product::with(['brandModel:id,name', 'category:id,name']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('second_name', 'like', "%{$search}%")
                  ->orWhere('barcode_digit', 'like', "%{$search}%");
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        if (in_array($sort, $allowed)) {
            $query->orderBy($sort, $direction);
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        $rows = collect($paginator->items())->map(fn ($p) => [
            'id'          => $p->id,
            'code'        => $p->code,
            'name'        => $p->name,
            'second_name' => $p->second_name,
            'brand_name'  => $p->brandModel?->name,
            'category_name' => $p->category?->name,
            'price'       => $p->price,
            'cost'        => $p->cost,
            'status'      => $p->status,
            'tester'      => $p->tester,
            'image'       => $p->image,
        ]);

        return Inertia::render('MasterData/Products', [
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
