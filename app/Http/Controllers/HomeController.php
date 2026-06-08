<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Setting;
use App\Models\Testimonial;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $services = Service::active()->get();
        $testimonials = Testimonial::with('service')->featured()->latest()->get();

        $stats = [
            'total_orders'  => Setting::get('total_orders', '500+'),
            'happy_clients' => Setting::get('happy_clients', '350+'),
            'subjects'      => Setting::get('subjects', '50+'),
            'rating'        => Setting::get('rating', '4.9'),
        ];

        $contact = [
            'wa_number' => Setting::get('wa_number', '6281234567890'),
            'email'     => Setting::get('email', 'order@nativecuy.id'),
            'instagram' => Setting::get('instagram', '@nativecuy.id'),
        ];

        return view('pages.home', compact('services', 'testimonials', 'stats', 'contact'));
    }
}
