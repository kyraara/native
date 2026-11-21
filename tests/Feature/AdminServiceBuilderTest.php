<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\User;
use App\Support\ServiceFormTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminServiceBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_saves_fields_and_prices_and_catalog_price_follows_cheapest_option(): void
    {
        $fields = [
            ['key' => 'indeksasi', 'label' => 'Indeksasi', 'type' => 'radio', 'required' => true, 'pricing' => 'base',
                'options' => [['value' => 'sinta', 'label' => 'SINTA', 'price' => null], ['value' => 'non', 'label' => 'Non-SINTA', 'price' => 900000]]],
            ['key' => 'peringkat', 'label' => 'Peringkat', 'type' => 'select', 'required' => true, 'pricing' => 'base',
                'show_if' => ['field' => 'indeksasi', 'values' => ['sinta']],
                'options' => [['value' => 's2', 'label' => 'SINTA 2', 'price' => 4000000], ['value' => 's3', 'label' => 'SINTA 3', 'price' => 2500000]]],
        ];

        $this->actingAs($this->admin())->post(route('admin.services.store'), $this->payload([
            'name' => 'Publish Jurnal', 'form_fields' => json_encode($fields),
        ]))->assertSessionHasNoErrors()->assertRedirect();

        $service = Service::where('slug', 'publish-jurnal')->sole();
        $this->assertCount(2, $service->form_fields);
        $this->assertSame(['field' => 'indeksasi', 'values' => ['sinta']], $service->form_fields[1]['show_if']);
        $this->assertSame('900000.00', $service->starting_price);
        $this->assertFalse($service->is_active, 'New services stay inactive unless switched on.');

        // SINTA 3 becomes the cheapest once Non-SINTA is more expensive.
        $fields[0]['options'][1]['price'] = 3000000;
        $this->put(route('admin.services.update', $service), $this->payload(['name' => 'Publish Jurnal', 'form_fields' => json_encode($fields)]))
            ->assertSessionHasNoErrors();
        $this->assertSame('2500000.00', $service->fresh()->starting_price);
    }

    public function test_broken_field_definitions_are_rejected_with_readable_errors(): void
    {
        $this->actingAs($this->admin())->post(route('admin.services.store'), $this->payload([
            'form_fields' => json_encode([
                ['key' => 'paket', 'label' => 'Paket', 'type' => 'radio', 'options' => []],
                ['key' => 'jumlah', 'label' => 'Jumlah', 'type' => 'text', 'pricing' => 'per_unit'],
            ]),
        ]))->assertSessionHasErrors('form_fields');

        $this->post(route('admin.services.store'), $this->payload(['form_fields' => '{"not":"a list"}']))
            ->assertSessionHasErrors('form_fields');

        $this->assertDatabaseEmpty('services');
    }

    public function test_update_without_builder_payload_keeps_existing_fields(): void
    {
        $service = $this->service(ServiceFormTemplates::get('ppt'));

        $this->actingAs($this->admin())->put(route('admin.services.update', $service), $this->payload(['name' => 'PPT']))
            ->assertSessionHasNoErrors();

        $this->assertCount(4, $service->fresh()->form_fields);
    }

    public function test_clearing_all_fields_falls_back_to_the_generic_form(): void
    {
        $service = $this->service(ServiceFormTemplates::get('ppt'));

        $this->actingAs($this->admin())->put(route('admin.services.update', $service), $this->payload(['name' => 'PPT', 'base_price' => 45000, 'form_fields' => '[]']))
            ->assertSessionHasNoErrors();

        $this->assertNull($service->fresh()->form_fields);
        $this->assertSame('45000.00', $service->fresh()->starting_price);
    }

    public function test_duplicate_copies_fields_as_an_inactive_service(): void
    {
        $service = $this->service(ServiceFormTemplates::get('skripsi'), ['is_active' => true]);

        $this->actingAs($this->admin())->post(route('admin.services.duplicate', $service))->assertRedirect();

        $copy = Service::where('name', 'PPT (salinan)')->sole();
        $this->assertFalse($copy->is_active);
        $this->assertSame($service->form_fields, $copy->form_fields);
        $this->assertSame($service->starting_price, $copy->starting_price);
        $this->assertNotSame($service->slug, $copy->slug);
    }

    public function test_create_page_prefills_from_template_or_another_service(): void
    {
        $source = $this->service(ServiceFormTemplates::get('web'));
        $this->actingAs($this->admin());

        $this->get(route('admin.services.create', ['template' => 'jurnal']))->assertOk()
            ->assertViewHas('fields', fn ($fields) => $fields[0]['key'] === 'indeksasi');
        $this->get(route('admin.services.create', ['from' => $source->id]))->assertOk()
            ->assertViewHas('fields', fn ($fields) => $fields[0]['key'] === 'jenis_web')
            ->assertSee('Disalin dari PPT');
        $this->get(route('admin.services.edit', $source))->assertOk()->assertSee('Field order');
        $this->get(route('admin.catalog.index'))->assertOk()->assertSee('Edit field');
    }

    private function payload(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Layanan Baru', 'description' => 'Deskripsi layanan.', 'icon_class' => 'fas fa-book',
            'base_price' => 100000, 'sort_order' => 1,
        ], $overrides);
    }

    private function service(?array $fields, array $attributes = []): Service
    {
        return Service::create(array_replace([
            'name' => 'PPT', 'slug' => 'ppt', 'description' => 'Presentasi.', 'icon_class' => 'fas fa-book',
            'base_price' => 50000, 'is_active' => false, 'sort_order' => 1, 'form_fields' => $fields,
        ], $attributes));
    }

    private function admin(): User
    {
        config(['admin.emails' => ['admin@example.com']]);

        return User::factory()->create(['email' => 'admin@example.com']);
    }
}
