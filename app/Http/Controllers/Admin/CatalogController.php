<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Major;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(Request $request): View
    {
        $tab = $request->query('tab') === 'majors' ? 'majors' : 'services';

        $majors = Major::with(['services' => fn ($query) => $query->orderBy('sort_order')->orderBy('name')])
            ->withCount('services')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $services = Service::with('majors')
            ->withCount(['majors', 'orders'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.catalog.index', compact('majors', 'services', 'tab'));
    }
}
