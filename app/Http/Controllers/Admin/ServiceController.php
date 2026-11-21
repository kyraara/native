<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Major;
use App\Models\Service;
use App\Services\ServiceFormSchema;
use App\Support\ServiceFormTemplates;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function __construct(private readonly ServiceFormSchema $schema)
    {
    }

    /** New service, optionally pre-filled from a template (?template=jurnal) or another service (?from=3). */
    public function create(Request $request): View
    {
        $service = new Service(['is_active' => false, 'base_price' => 0, 'sort_order' => Service::max('sort_order') + 1, 'icon_class' => 'fas fa-book-open']);

        $template = is_string($request->query('template')) ? ServiceFormTemplates::get($request->query('template')) : null;
        $source = ctype_digit((string) $request->query('from')) ? Service::with('majors')->find($request->query('from')) : null;

        if ($source) {
            $service->fill($source->only(['description', 'icon_class', 'base_price']));
            $service->form_fields = $source->form_fields;
            $service->setRelation('majors', $source->majors);
        } elseif ($template) {
            $service->form_fields = $template;
        }

        return $this->form($service, $source);
    }

    public function edit(Service $service): View
    {
        return $this->form($service->load('majors'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateService($request);
        $service = Service::create($this->attributes($validated));
        $service->majors()->sync($validated['major_ids'] ?? []);

        $note = $service->is_active ? '' : ' Layanan masih nonaktif — aktifkan setelah pratinjau sesuai.';

        return redirect()->route('admin.services.edit', $service)
            ->with('success', "Layanan {$service->name} berhasil ditambahkan.{$note}");
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $validated = $this->validateService($request, $service);
        $service->update($this->attributes($validated, $service));
        $service->majors()->sync($validated['major_ids'] ?? []);

        return back()->with('success', "Layanan {$service->name} berhasil diperbarui.");
    }

    /** Copy a service with its fields and prices. The copy starts inactive. */
    public function duplicate(Service $service): RedirectResponse
    {
        $copy = $service->replicate(['slug', 'starting_price']);
        $copy->name = $this->uniqueName($service->name.' (salinan)');
        $copy->slug = $this->uniqueSlug($copy->name);
        $copy->is_active = false;
        $copy->save();
        $copy->majors()->sync($service->majors()->pluck('majors.id'));

        return redirect()->route('admin.services.edit', $copy)
            ->with('success', "Salinan {$service->name} dibuat dalam keadaan nonaktif. Sesuaikan lalu aktifkan.");
    }

    public function destroy(Service $service): RedirectResponse
    {
        if ($service->orders()->exists()) {
            return back()->with('error', 'Layanan yang sudah memiliki order tidak dapat dihapus. Nonaktifkan layanan sebagai gantinya.');
        }

        $name = $service->name;
        $service->delete();

        return redirect()->route('admin.catalog.index', ['tab' => 'services'])->with('success', "Layanan {$name} berhasil dihapus.");
    }

    private function form(Service $service, ?Service $source = null): View
    {
        $majors = Major::orderBy('sort_order')->orderBy('name')->get();
        $templates = collect(ServiceFormTemplates::all())
            ->map(fn (array $template, string $key) => ['key' => $key, 'name' => $template['name'], 'fields' => $this->schema->normalize($template['fields'])])
            ->values();
        $otherServices = Service::query()->when($service->exists, fn ($query) => $query->whereKeyNot($service->id))
            ->orderBy('sort_order')->orderBy('name')->get()
            ->map(fn (Service $item) => ['id' => $item->id, 'name' => $item->name, 'fields' => $item->formSchema()])
            ->values();

        return view('admin.services.form', [
            'service' => $service,
            'source' => $source,
            'fields' => $this->schema->normalize($service->form_fields),
            'majors' => $majors,
            'templates' => $templates,
            'otherServices' => $otherServices,
            'ordersCount' => $service->exists ? $service->orders()->count() : 0,
        ]);
    }

    private function validateService(Request $request, ?Service $service = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('services')->ignore($service)],
            'description' => ['required', 'string', 'max:2000'],
            'icon_class' => ['nullable', 'string', 'max:80', 'regex:/^[a-zA-Z0-9 _-]+$/'],
            'base_price' => ['required', 'numeric', 'min:0', 'max:9999999999'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'major_ids' => ['nullable', 'array'],
            'major_ids.*' => ['integer', 'exists:majors,id'],
            'form_fields' => ['nullable', 'string', 'max:200000'],
        ], [
            'name.unique' => 'Nama layanan sudah dipakai.',
        ]);

        // Field definitions arrive as JSON from the builder. Absent = keep what the service has.
        if ($request->has('form_fields')) {
            $decoded = json_decode((string) ($validated['form_fields'] ?? '[]'), true);

            if (! is_array($decoded) || ! array_is_list($decoded) || count($decoded) > 40) {
                throw ValidationException::withMessages(['form_fields' => 'Data field form tidak valid. Muat ulang halaman lalu coba lagi.']);
            }

            $fields = $this->schema->normalize(array_filter($decoded, 'is_array'));
            $errors = $this->schema->validateSchema($fields);

            if ($errors !== []) {
                throw ValidationException::withMessages(['form_fields' => $errors]);
            }

            $validated['form_fields'] = $fields === [] ? null : $fields;
        } else {
            unset($validated['form_fields']);
        }

        return $validated;
    }

    private function attributes(array $validated, ?Service $service = null): array
    {
        $attributes = [
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['name'], $service),
            'description' => $validated['description'],
            'icon_class' => ($validated['icon_class'] ?? null) ?: 'fas fa-book-open',
            'base_price' => $validated['base_price'],
            'sort_order' => $validated['sort_order'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];

        if (array_key_exists('form_fields', $validated)) {
            $attributes['form_fields'] = $validated['form_fields'];
        }

        return $attributes;
    }

    private function uniqueName(string $name): string
    {
        $candidate = Str::limit($name, 110, '');
        $suffix = 2;

        while (Service::where('name', $candidate)->exists()) {
            $candidate = Str::limit($name, 105, '')." {$suffix}";
            $suffix++;
        }

        return $candidate;
    }

    private function uniqueSlug(string $name, ?Service $service = null): string
    {
        $base = Str::slug($name) ?: 'layanan';
        $slug = $base;
        $suffix = 2;

        while (Service::where('slug', $slug)->when($service, fn ($query) => $query->whereKeyNot($service->id))->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
