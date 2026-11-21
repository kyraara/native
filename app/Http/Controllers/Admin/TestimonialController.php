<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TestimonialController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['visibility' => ['nullable', Rule::in(['published', 'hidden'])]]);
        $visibility = $filters['visibility'] ?? null;
        $testimonials = Testimonial::with('service')
            ->when($visibility, fn ($query) => $query->where('is_featured', $visibility === 'published'))
            ->latest()->orderByDesc('id')->paginate(12)->withQueryString();
        $counts = ['total' => Testimonial::count(), 'published' => Testimonial::featured()->count()];

        return view('admin.settings.testimonials.index', compact('testimonials', 'visibility', 'counts'));
    }

    public function create(): View
    {
        return $this->form(new Testimonial(['rating' => 5, 'is_featured' => false]));
    }

    public function edit(Testimonial $testimonial): View
    {
        return $this->form($testimonial);
    }

    private function form(Testimonial $testimonial): View
    {
        $services = Service::orderBy('name')->get(['id', 'name', 'is_active']);
        return view('admin.settings.testimonials.form', compact('testimonial', 'services'));
    }

    public function store(Request $request): RedirectResponse
    {
        Testimonial::create($this->validated($request));
        return redirect()->route('admin.testimonials.index')->with('success', 'Testimoni berhasil ditambahkan.');
    }

    public function update(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $testimonial->update($this->validated($request));
        return redirect()->route('admin.testimonials.index')->with('success', 'Perubahan testimoni berhasil disimpan.');
    }

    public function visibility(Request $request, Testimonial $testimonial): RedirectResponse
    {
        $data = $request->validate(['is_featured' => ['required', 'boolean']]);
        $testimonial->update($data);
        return back()->with('success', $testimonial->is_featured ? 'Testimoni ditampilkan di website.' : 'Testimoni disembunyikan dari website.');
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $testimonial->delete();
        return redirect()->route('admin.testimonials.index')->with('success', 'Testimoni berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'client_name' => ['required', 'string', 'max:100'],
            'service_id' => ['nullable', 'integer', Rule::exists(Service::class, 'id')],
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['required', 'string', 'max:1500'],
            'is_featured' => ['required', 'boolean'],
        ]);
        $data['avatar_initials'] = collect(preg_split('/\s+/u', trim($data['client_name'])))->take(2)
            ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->implode('');
        return $data;
    }
}
