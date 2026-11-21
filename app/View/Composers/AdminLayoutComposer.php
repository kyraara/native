<?php

namespace App\View\Composers;

use App\Services\AdminOrderNotifications;
use Illuminate\View\View;

class AdminLayoutComposer
{
    public function compose(View $view): void
    {
        $view->with('orderNotifications', app(AdminOrderNotifications::class)->snapshot());
    }
}
