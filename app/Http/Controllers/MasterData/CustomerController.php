<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $sort      = $request->get('sort', 'id');
        $direction = $request->get('direction', 'asc') === 'desc' ? 'desc' : 'asc';
        $search    = (string) $request->get('search', '');
        $perPage   = (int) min($request->get('per_page', 25), 100);
        $filters   = $request->get('filter', []);

        $allowed = ['id', 'name', 'phone', 'vip', 'award_points', 'sum_total', 'last_buy'];

        $query = Client::customers();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('phone_second', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (isset($filters['vip']) && $filters['vip'] !== '') {
            $query->where('vip', $filters['vip']);
        }

        if (isset($filters['active']) && $filters['active'] !== '') {
            if ($filters['active'] === '1') {
                $query->where('fade_delete', '!=', 1);
            } else {
                $query->where('fade_delete', 1);
            }
        }

        if (in_array($sort, $allowed)) {
            $query->orderBy($sort, $direction);
        }

        $paginator = $query->paginate($perPage)->withQueryString();

        $rows = collect($paginator->items())->map(fn ($c) => [
            'id'          => $c->id,
            'name'        => $c->name,
            'phone'       => $c->phone,
            'vip'         => $c->vip,
            'vvip'        => $c->vvip,
            'award_points'=> $c->award_points,
            'sum_total'   => $c->sum_total,
            'last_buy'    => $c->last_buy,
            'fade_delete' => $c->fade_delete,
            'brand_name'  => $c->brand_name,
        ]);

        return Inertia::render('MasterData/Customers', [
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
