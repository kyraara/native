<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Major;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MajorController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateMajor($request);
        $major = Major::create($this->attributes($validated));
        $major->services()->sync($validated['service_ids'] ?? []);

        return back()->with('success', "Jurusan {$major->name} berhasil ditambahkan.");
    }

    public function update(Request $request, Major $major): RedirectResponse
    {
        $validated = $this->validateMajor($request, $major);
        $major->update($this->attributes($validated, $major));
        $major->services()->sync($validated['service_ids'] ?? []);

        return back()->with('success', "Jurusan {$major->name} berhasil diperbarui.");
    }

    public function destroy(Major $major): RedirectResponse
    {
        $name = $major->name;
        $major->delete();

        return back()->with('success', "Jurusan {$name} berhasil dihapus.");
    }

    private function validateMajor(Request $request, ?Major $major = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('majors')->ignore($major)],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon_class' => ['nullable', 'string', 'max:80', 'regex:/^[a-zA-Z0-9 _-]+$/'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['nullable', 'boolean'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['integer', 'exists:services,id'],
        ]);
    }

    private function attributes(array $validated, ?Major $major = null): array
    {
        return [
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['name'], $major),
            'description' => $validated['description'] ?? null,
            'icon_class' => ($validated['icon_class'] ?? null) ?: 'fas fa-graduation-cap',
            'sort_order' => $validated['sort_order'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];
    }

    private function uniqueSlug(string $name, ?Major $major = null): string
    {
        $base = Str::slug($name) ?: 'jurusan';
        $slug = $base;
        $suffix = 2;

        while (Major::where('slug', $slug)->when($major, fn ($query) => $query->whereKeyNot($major->id))->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
