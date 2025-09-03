<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;


class SettingController extends Controller
{
    public function index()
    {

        return Inertia::render('Settings/index');
    }

    public function getCategories(Request $request)
    {
        if ($request->ajax()) {
            $query = Category::query();

            // Filtering
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            }

            // Sorting
            if ($request->filled('sort')) {
                $query->orderBy($request->sort, $request->get('order', 'asc'));
            }

            // Pagination
            $data = $query->paginate($request->get('per_page', 10));

            return response()->json($data);
        }
    }
}
