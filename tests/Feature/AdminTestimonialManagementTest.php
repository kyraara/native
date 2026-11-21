<?php

namespace Tests\Feature;

use App\Models\Service;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTestimonialManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_testimonial_routes_require_an_allowlisted_admin(): void
    {
        $testimonial = Testimonial::create($this->payload());
        $this->get(route('admin.testimonials.index'))->assertRedirect(route('login'));
        $this->post(route('admin.testimonials.store'), $this->payload())->assertRedirect(route('login'));
        config(['admin.emails' => ['admin@example.test']]);
        $this->actingAs(User::factory()->create(['email' => 'customer@example.test']));
        $this->get(route('admin.testimonials.index'))->assertForbidden();
        $this->get(route('admin.testimonials.create'))->assertForbidden();
        $this->get(route('admin.testimonials.edit', $testimonial))->assertForbidden();
        $this->post(route('admin.testimonials.store'), $this->payload())->assertForbidden();
        $this->put(route('admin.testimonials.update', $testimonial), $this->payload())->assertForbidden();
        $this->patch(route('admin.testimonials.visibility', $testimonial), ['is_featured' => true])->assertForbidden();
        $this->delete(route('admin.testimonials.destroy', $testimonial))->assertForbidden();
        $this->assertDatabaseCount('testimonials', 1);
    }

    public function test_admin_can_create_publish_and_hide_a_testimonial(): void
    {
        $this->actingAs($this->admin());
        $this->get(route('admin.settings.index'))->assertOk()->assertSee(route('admin.testimonials.index'), false);
        $this->get(route('admin.testimonials.create'))->assertOk();
        $this->post(route('admin.testimonials.store'), $this->payload())->assertSessionHasNoErrors()->assertRedirect(route('admin.testimonials.index'));
        $testimonial = Testimonial::firstOrFail();
        $this->assertSame('NP', $testimonial->avatar_initials);
        $this->assertFalse($testimonial->is_featured);
        $this->get('/')->assertDontSee('Ulasan unik untuk halaman publik.');

        $this->patch(route('admin.testimonials.visibility', $testimonial), ['is_featured' => 1])->assertSessionHasNoErrors();
        $this->get('/')->assertSee('Ulasan unik untuk halaman publik.')->assertSee('Nadya Putri');
        $this->patch(route('admin.testimonials.visibility', $testimonial), ['is_featured' => 0])->assertSessionHasNoErrors();
        $this->get('/')->assertDontSee('Ulasan unik untuk halaman publik.');
    }

    public function test_edit_saves_service_rating_name_and_escaped_customer_copy(): void
    {
        $service = Service::create(['name'=>'Laporan PKL','slug'=>'laporan-pkl','description'=>'Laporan praktik kerja lapangan.','icon_class'=>'fas fa-book','base_price'=>150000,'is_active'=>false]);
        $testimonial = Testimonial::create($this->payload());
        $this->actingAs($this->admin())->get(route('admin.testimonials.edit', $testimonial))->assertOk()->assertSee('Laporan PKL (nonaktif)');
        $this->put(route('admin.testimonials.update', $testimonial), $this->payload([
            'client_name'=>'Rani Utami','service_id'=>$service->id,'rating'=>4,'is_featured'=>1,
            'comment'=>'Ulasan <script>alert("xss")</script> tetap berupa teks.',
        ]))->assertSessionHasNoErrors()->assertRedirect(route('admin.testimonials.index'));
        $testimonial->refresh();
        $this->assertSame('RU', $testimonial->avatar_initials);
        $this->assertSame(4, $testimonial->rating);
        $this->assertSame($service->id, $testimonial->service_id);
        $this->get('/')->assertOk()->assertSee('Ulasan <script>alert("xss")</script> tetap berupa teks.')
            ->assertDontSee('<script>alert("xss")</script>', false);
    }

    public function test_invalid_reviews_do_not_change_saved_data(): void
    {
        $testimonial = Testimonial::create($this->payload());
        $this->actingAs($this->admin());
        foreach ([['rating'=>0],['rating'=>6],['rating'=>2.5],['comment'=>''],['comment'=>str_repeat('a',1501)],['client_name'=>str_repeat('n',101)],['service_id'=>99999],['is_featured'=>'invalid']] as $invalid) {
            $this->putJson(route('admin.testimonials.update', $testimonial), $this->payload($invalid))
                ->assertUnprocessable()->assertJsonValidationErrors(array_keys($invalid));
        }
        $this->assertSame('Ulasan unik untuk halaman publik.', $testimonial->fresh()->comment);
        $this->assertFalse($testimonial->fresh()->is_featured);
        $this->patchJson(route('admin.testimonials.visibility', $testimonial), [])->assertUnprocessable()->assertJsonValidationErrors('is_featured');
    }

    public function test_admin_can_filter_and_delete_testimonials(): void
    {
        $published = Testimonial::create($this->payload(['client_name'=>'Reviewer Publik','is_featured'=>1]));
        $hidden = Testimonial::create($this->payload(['client_name'=>'Reviewer Tersembunyi']));
        $this->actingAs($this->admin())->get(route('admin.testimonials.index',['visibility'=>'published']))->assertOk()
            ->assertSee('Reviewer Publik')->assertDontSee('Reviewer Tersembunyi');
        $this->get(route('admin.testimonials.index',['visibility'=>'hidden']))->assertOk()
            ->assertSee('Reviewer Tersembunyi')->assertDontSee('Reviewer Publik');
        $this->delete(route('admin.testimonials.destroy', $published))->assertRedirect(route('admin.testimonials.index'));
        $this->assertDatabaseMissing('testimonials',['id'=>$published->id]);
        $this->assertDatabaseHas('testimonials',['id'=>$hidden->id]);
        $this->get('/')->assertDontSee('Reviewer Publik');
    }

    public function test_home_only_includes_six_latest_published_reviews(): void
    {
        foreach (range(1,8) as $i) Testimonial::create($this->payload(['client_name'=>'Pelanggan '.$i,'is_featured'=>1]));
        Testimonial::create($this->payload(['client_name'=>'Ulasan privat','is_featured'=>0]));
        $this->get('/')->assertOk()->assertViewHas('testimonials', fn ($items) => $items->count() === 6 && $items->first()->client_name === 'Pelanggan 8')
            ->assertSee('Pelanggan 3')->assertDontSee('Pelanggan 1')->assertDontSee('Pelanggan 2')->assertDontSee('Ulasan privat');
    }

    public function test_nullable_service_and_pagination_work(): void
    {
        $this->actingAs($this->admin());
        $this->post(route('admin.testimonials.store'), $this->payload(['service_id'=>'']))->assertSessionHasNoErrors();
        $this->assertNull(Testimonial::firstOrFail()->service_id);
        foreach (range(1,12) as $i) Testimonial::create($this->payload(['client_name'=>'Pelanggan '.$i]));
        $this->get(route('admin.testimonials.index'))->assertOk()->assertViewHas('testimonials', fn ($items) => $items->total() === 13 && $items->count() === 12);
        $this->get(route('admin.testimonials.index',['page'=>2]))->assertOk()->assertViewHas('testimonials', fn ($items) => $items->count() === 1);
    }

    private function admin(): User
    {
        config(['admin.emails'=>['admin@example.test']]);
        return User::factory()->create(['email'=>'admin@example.test']);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace(['client_name'=>'Nadya Putri','rating'=>5,'comment'=>'Ulasan unik untuk halaman publik.','is_featured'=>0],$overrides);
    }
}
