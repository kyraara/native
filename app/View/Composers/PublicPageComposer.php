<?php

namespace App\View\Composers;

use App\Models\Service;
use App\Models\Setting;
use Illuminate\View\View;

class PublicPageComposer
{
    public function compose(View $view): void
    {
        $phone = preg_replace('/\D+/', '', (string) Setting::get('wa_number', '6281234567890'));
        if (str_starts_with($phone, '0')) {
            $phone = '62'.substr($phone, 1);
        }

        $view->with('publicContact', [
            'wa_number' => $phone,
            'email' => Setting::get('email', 'order@nativecuy.id'),
            'instagram' => ltrim((string) Setting::get('instagram', 'nativecuy.id'), '@'),
            'location' => Setting::get('location', 'Palembang, Sumatera Selatan'),
        ]);
        $view->with('footerServices', Service::active()->limit(5)->get(['id', 'name']));
    }
}
