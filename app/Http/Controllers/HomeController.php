<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Major;
use App\Models\Setting;
use App\Models\Testimonial;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $services = Service::active()->with(['majors' => fn ($query) => $query->active()])->get();
        $majors = Major::active()->whereHas('services', fn ($query) => $query->where('is_active', true))->get();
        $testimonials = Testimonial::with('service')->featured()->latest()->orderByDesc('id')->limit(6)->get();

        $stats = [
            'total_orders'  => Setting::get('total_orders', '500+'),
            'happy_clients' => Setting::get('happy_clients', '350+'),
            'subjects'      => Setting::get('subjects', '50+'),
            'rating'        => Setting::get('rating', '4.9'),
        ];

        return view('pages.home', compact('services', 'majors', 'testimonials', 'stats'));
    }
}
