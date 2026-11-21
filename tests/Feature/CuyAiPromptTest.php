<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\Setting;
use App\Support\ServiceFormTemplates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CuyAiPromptTest extends TestCase
{
    use RefreshDatabase;

    public function test_cuy_ai_knows_catalog_prices_options_and_order_links(): void
    {
        $service = $this->service();
        $prompt = $this->askAndCapturePrompt('SINTA 2 berapa?');

        $this->assertStringContainsString("LAYANAN: Publish Jurnal — mulai Rp 1.000.000 · link order: /order?service={$service->id}", $prompt);
        $this->assertStringContainsString('SINTA 2 = Rp 4.000.000', $prompt);
        $this->assertStringContainsString('Peringkat SINTA (wajib, muncul jika Target indeksasi = SINTA, menentukan harga dasar)', $prompt);
        $this->assertStringContainsString('Translate ke bahasa Inggris +Rp 500.000', $prompt);
        $this->assertStringContainsString('BUKAN harga final', $prompt);
        $this->assertStringNotContainsString('Layanan Arsip', $prompt, 'Inactive services stay out of the prompt.');
    }

    public function test_prompt_refreshes_immediately_when_prices_or_contacts_change(): void
    {
        $service = $this->service();
        $this->askAndCapturePrompt('halo');

        $fields = $service->form_fields;
        $fields[1]['options'][1]['price'] = 4200000; // SINTA 2
        $service->update(['form_fields' => $fields]);
        Setting::set('wa_number', '6289999999999');

        $prompt = $this->askAndCapturePrompt('SINTA 2 berapa?');
        $this->assertStringContainsString('SINTA 2 = Rp 4.200.000', $prompt);
        $this->assertStringContainsString('wa.me/6289999999999', $prompt);
    }

    public function test_replies_are_tidied_for_the_chat_widget(): void
    {
        $this->assertSame("Harga\n• **SINTA 2:** Rp 4.000.000\n• Bab 1\n\nOke", app(\App\Services\AiChatService::class)->tidy("## Harga\n* **SINTA 2:** Rp 4.000.000\n- Bab 1\n\n\n\nOke"));
    }

    private function askAndCapturePrompt(string $question): string
    {
        config(['services.gemini.key' => 'test-key', 'services.gemini.model' => 'gemini-test']);
        $captured = null;
        Http::fake(function (Request $request) use (&$captured) {
            $captured = $request['system_instruction']['parts'][0]['text'];

            return Http::response(['candidates' => [['content' => ['parts' => [['text' => 'Estimasi mulai dari Rp 4.000.000 🦉']]]]]]);
        });

        $this->postJson(route('chat.send'), ['message' => $question])->assertOk()->assertJsonPath('reply', 'Estimasi mulai dari Rp 4.000.000 🦉');

        return (string) $captured;
    }

    private function service(): Service
    {
        Service::create(['name' => 'Layanan Arsip', 'slug' => 'arsip', 'description' => 'Lama.', 'icon_class' => 'fas fa-book', 'base_price' => 1, 'is_active' => false, 'sort_order' => 9]);

        return Service::create([
            'name' => 'Publish Jurnal', 'slug' => 'publish-jurnal', 'description' => 'Publikasi jurnal.', 'icon_class' => 'fas fa-book',
            'base_price' => 500000, 'is_active' => true, 'sort_order' => 1, 'form_fields' => ServiceFormTemplates::get('jurnal'),
        ]);
    }
}
