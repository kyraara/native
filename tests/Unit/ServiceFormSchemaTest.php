<?php

namespace Tests\Unit;

use App\Services\ServiceFormSchema;
use App\Support\ServiceFormTemplates;
use PHPUnit\Framework\TestCase;

class ServiceFormSchemaTest extends TestCase
{
    private ServiceFormSchema $schema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->schema = new ServiceFormSchema();
    }

    public function test_sinta_rank_sets_the_base_price_and_non_sinta_uses_its_own(): void
    {
        $fields = ServiceFormTemplates::get('jurnal');
        $common = ['kondisi_naskah' => 'draft', 'bidang_ilmu' => 'Manajemen', 'bahasa' => 'indonesia'];

        $s2 = $this->schema->estimate($fields, $common + ['indeksasi' => 'sinta', 'peringkat_sinta' => 's2'], 1000000);
        $s3 = $this->schema->estimate($fields, $common + ['indeksasi' => 'sinta', 'peringkat_sinta' => 's3'], 1000000);
        $non = $this->schema->estimate($fields, $common + ['indeksasi' => 'non_sinta', 'peringkat_sinta' => 's1'], 1000000);

        $this->assertSame(4000000, $s2['total']);
        $this->assertSame(2500000, $s3['total']);
        $this->assertSame(1000000, $non['total'], 'Hidden SINTA rank must not affect a Non-SINTA estimate.');
        $this->assertSame('SINTA 2', $s2['lines'][0]['detail']);
    }

    public function test_add_and_per_unit_pricing_are_summed(): void
    {
        $jurnal = $this->schema->estimate(ServiceFormTemplates::get('jurnal'), [
            'indeksasi' => 'sinta', 'peringkat_sinta' => 's3', 'kondisi_naskah' => 'belum_ada',
            'bantuan_tambahan' => ['turnitin', 'translate'],
        ], 0);
        $this->assertSame(2500000 + 1000000 + 250000 + 500000, $jurnal['total']);

        $ppt = $this->schema->estimate(ServiceFormTemplates::get('ppt'), ['keperluan' => 'seminar', 'jumlah_slide' => 15], 0);
        $this->assertSame(75000 + 15 * 5000, $ppt['total']);
        $this->assertSame('15 slide × Rp 5.000', $ppt['lines'][1]['detail']);
    }

    public function test_skripsi_per_bab_counts_chapters_but_packages_ignore_them(): void
    {
        $fields = ServiceFormTemplates::get('skripsi');

        $perBab = $this->schema->estimate($fields, ['paket' => 'per_bab', 'bab' => ['bab1', 'bab3'], 'jenjang' => 's1'], 150000);
        $sempro = $this->schema->estimate($fields, ['paket' => 'sempro', 'bab' => ['bab1', 'bab3'], 'jenjang' => 's2'], 150000);

        $this->assertSame(250000 + 350000, $perBab['total']);
        $this->assertSame(1200000 + 500000, $sempro['total']);
    }

    public function test_clean_drops_hidden_and_unknown_answers(): void
    {
        $clean = $this->schema->clean(ServiceFormTemplates::get('skripsi'), [
            'paket' => 'sempro', 'bab' => ['bab1'], 'jenjang' => 's1', 'hacker' => 'x', 'metode' => '',
        ]);

        $this->assertSame(['paket' => 'sempro', 'jenjang' => 's1'], $clean);
    }

    public function test_rules_only_require_visible_fields(): void
    {
        $fields = ServiceFormTemplates::get('jurnal');

        $this->assertArrayHasKey('details.peringkat_sinta', $this->schema->rules($fields, ['indeksasi' => 'sinta']));
        $this->assertArrayNotHasKey('details.peringkat_sinta', $this->schema->rules($fields, ['indeksasi' => 'non_sinta']));
    }

    public function test_starting_price_is_the_cheapest_valid_combination(): void
    {
        // Non-SINTA 1jt is cheaper than SINTA 6 (1,25jt); cheapest required "kondisi naskah" adds 0.
        $this->assertSame(1000000, $this->schema->startingPrice(ServiceFormTemplates::get('jurnal'), 999999999));
        // Makalah 150rb beats per bab 0 + cheapest chapter (Bab 5, 200rb).
        $this->assertSame(150000, $this->schema->startingPrice(ServiceFormTemplates::get('skripsi'), 0));
        // Tugas kelas 50rb + minimum 5 slides × 5rb.
        $this->assertSame(75000, $this->schema->startingPrice(ServiceFormTemplates::get('ppt'), 0));
        $this->assertSame(40000, $this->schema->startingPrice([], 40000));
    }

    public function test_bundled_templates_pass_schema_validation(): void
    {
        foreach (ServiceFormTemplates::all() as $key => $template) {
            $this->assertSame([], $this->schema->validateSchema($template['fields']), "Template {$key} is invalid.");
        }
    }

    public function test_schema_validation_rejects_broken_definitions(): void
    {
        $errors = implode("\n", $this->schema->validateSchema([
            ['key' => 'paket', 'label' => 'Paket', 'type' => 'radio', 'options' => []],
            ['key' => 'paket', 'label' => 'Duplikat', 'type' => 'text', 'pricing' => 'base'],
            ['key' => 'syarat', 'label' => 'Syarat', 'type' => 'text', 'show_if' => ['field' => 'nanti', 'values' => ['a']]],
            ['key' => 'nanti', 'label' => 'Nanti', 'type' => 'radio', 'options' => [['value' => 'a', 'label' => 'A', 'price' => -5]]],
        ]));

        $this->assertStringContainsString('minimal satu pilihan', $errors);
        $this->assertStringContainsString('sudah dipakai', $errors);
        $this->assertStringContainsString('harga dasar', $errors);
        $this->assertStringContainsString('di atasnya', $errors);
        $this->assertStringContainsString('negatif', $errors);
    }
}
