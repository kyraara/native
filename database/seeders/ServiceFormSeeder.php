<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Support\ServiceFormTemplates;
use Illuminate\Database\Seeder;

/**
 * Gives the standard services their order fields and example prices.
 * Only fills services that have no fields yet, so admin edits are never overwritten.
 */
class ServiceFormSeeder extends Seeder
{
    public function run(): void
    {
        Service::query()->get()->each(function (Service $service): void {
            if (! empty($service->form_fields)) {
                return;
            }

            $fields = ServiceFormTemplates::forSlug($service->slug);

            if ($fields !== null) {
                $service->form_fields = $fields;
            }

            // Saving also refreshes starting_price for services without fields.
            $service->save();
        });
    }
}
