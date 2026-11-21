@extends('layouts.admin')
@section('title', 'Testimoni')
@section('page-title', 'Pengaturan')
@section('page-subtitle', 'Kelola cerita pelanggan yang tampil di halaman publik.')
@section('content')
@include('admin.settings.nav')

<div class="tst-toolbar">
    <nav class="catalog-tabs" aria-label="Filter testimoni">
        @foreach(['' => 'Semua', 'published' => 'Ditampilkan', 'hidden' => 'Disembunyikan'] as $value => $label)
            <a href="{{ route('admin.testimonials.index', $value ? ['visibility' => $value] : []) }}" @if(($visibility ?? '') === $value) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>
    <div class="ui-row">
        <span class="ui-muted" style="font-size:13px">{{ $counts['total'] }} testimoni · {{ $counts['published'] }} ditampilkan</span>
        <a class="ui-btn ui-btn-gold" href="{{ route('admin.testimonials.create') }}"><x-admin-icon name="plus"/>Tambah testimoni</a>
    </div>
</div>
<p class="ui-help" style="margin-bottom:14px">Halaman publik menampilkan hingga 6 testimoni terbaru yang diaktifkan.</p>

@if($testimonials->isEmpty())
    <section class="ui-card"><div class="ui-empty"><strong>{{ $visibility ? 'Belum ada testimoni pada filter ini.' : 'Cerita pelanggan dimulai di sini.' }}</strong>Tambahkan testimoni pelanggan, lalu pilih kapan ingin menampilkannya di website.<div style="margin-top:14px"><a class="ui-btn ui-btn-primary" href="{{ route('admin.testimonials.create') }}">Tambah testimoni</a></div></div></section>
@else
    <div class="tst-grid">
        @foreach($testimonials as $testimonial)
            <article @class(['tst-card', 'is-hidden' => ! $testimonial->is_featured])>
                <div class="tst-head">
                    <span class="tst-avatar">{{ $testimonial->avatar_initials ?: mb_strtoupper(mb_substr($testimonial->client_name, 0, 2)) }}</span>
                    <div><h2>{{ $testimonial->client_name }}</h2><p>{{ $testimonial->service?->name ?: 'Tanpa layanan tertentu' }}</p></div>
                    @if($testimonial->is_featured)<span class="ui-status ui-status-done">Ditampilkan</span>@else<span class="ui-status">Disembunyikan</span>@endif
                </div>
                <div class="tst-stars" aria-label="{{ $testimonial->rating }} dari 5 bintang">@for($star = 1; $star <= 5; $star++)<x-admin-icon name="star" :class="$star <= $testimonial->rating ? 'is-filled' : ''"/>@endfor<span>{{ $testimonial->rating }}/5</span></div>
                <p class="tst-comment">{{ \Illuminate\Support\Str::limit($testimonial->comment, 220) }}</p>
                <div class="tst-actions">
                    <a class="ui-btn ui-btn-ghost ui-btn-sm" href="{{ route('admin.testimonials.edit', $testimonial) }}"><x-admin-icon name="edit"/>Edit</a>
                    <form method="POST" action="{{ route('admin.testimonials.visibility', $testimonial) }}">@csrf @method('PATCH')<input type="hidden" name="is_featured" value="{{ $testimonial->is_featured ? 0 : 1 }}"><button class="ui-btn ui-btn-ghost ui-btn-sm" type="submit">{{ $testimonial->is_featured ? 'Sembunyikan' : 'Tampilkan' }}</button></form>
                    <details class="tst-delete"><summary class="ui-btn ui-btn-danger ui-btn-sm"><x-admin-icon name="trash"/>Hapus</summary><div><p>Hapus testimoni {{ $testimonial->client_name }}? Tindakan ini tidak dapat dibatalkan.</p><form method="POST" action="{{ route('admin.testimonials.destroy', $testimonial) }}">@csrf @method('DELETE')<button class="ui-btn ui-btn-danger ui-btn-sm" type="submit">Ya, hapus testimoni</button></form></div></details>
                </div>
            </article>
        @endforeach
    </div>
@endif
@if($testimonials->hasPages())<div style="margin-top:18px">{{ $testimonials->onEachSide(1)->links('admin.partials.pagination') }}</div>@endif
@endsection
