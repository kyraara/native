@extends('layouts.admin')

@section('title', "Order #{$order->tracking_token}")
@section('page-title', $order->title)
@section('toolbar-title', 'Detail order')
@section('page-subtitle', "#{$order->tracking_token} · {$order->client_name}")

@section('content')
@php
    $isOverdue = $order->deadline->lt(today()) && ! in_array($order->status, ['done', 'cancelled'], true);
    $waPhone = \App\Support\Phone::normalize($order->client_phone);
    // Same link as the automatic WhatsApp messages: follows APP_URL in .env.
    $trackUrl = route('order.track', $order->tracking_token);
    $priceLine = $order->price_final !== null
        ? '💰 *Harga final:* '.\App\Support\Money::rupiah($order->price_final)
        : ($order->price_estimate !== null ? '💰 *Estimasi mulai dari:* '.\App\Support\Money::rupiah($order->price_estimate).' (harga final disepakati saat konsultasi)' : null);

    $waLines = array_values(array_filter([
        "Halo *{$order->client_name}*! 👋",
        '',
        'Berikut update order kamu di *NativeCuy* ya 😊',
        '',
        "📋 *Kode tracking:* {$order->tracking_token}",
        '📚 *Layanan:* '.($order->service?->name ?? '-'),
        ...collect($order->detail_answers)->map(fn ($answer) => "   • {$answer['label']}: {$answer['display']}")->all(),
        '📅 *Deadline:* '.$order->deadline->locale('id')->isoFormat('D MMMM YYYY'),
        '📌 *Status:* '.$order->status_label,
        $priceLine,
        ...($order->admin_notes ? ['', '💬 *Pesan dari Admin:*', $order->admin_notes] : []),
        '',
        '🔗 Pantau progress di sini (masukkan 4 digit terakhir nomor WhatsApp ini saat diminta):',
        $trackUrl,
        '',
        'Kalau ada pertanyaan, balas pesan ini aja ya! 🦉',
        '— *NativeCuy*',
    ], fn ($line) => $line !== null));
    $waUrl = "https://wa.me/{$waPhone}?text=".urlencode(implode("\n", $waLines));
    $estimate = $order->price_estimate !== null ? (float) $order->price_estimate : null;
    // Whole amounts without ".00" so the number input reads 4000000, not 4000000,00.
    $priceValue = $order->price_final === null ? '' : (fmod((float) $order->price_final, 1.0) === 0.0 ? (string) (int) $order->price_final : (string) $order->price_final);
    $statusConfig = ['status' => old('status', $order->status), 'from' => $order->status, 'price' => (string) old('price_final', $priceValue), 'estimate' => $estimate];
@endphp

<div class="svc-topline">
    <a href="{{ route('admin.orders.index') }}" class="ui-link"><x-admin-icon name="arrow" class="svc-back-icon"/> Kembali ke daftar order</a>
    <div class="ui-row">
        <x-admin.status-badge :status="$order->status"/>
        <span class="ui-muted" style="font-size:12px">Masuk {{ $order->created_at->locale('id')->translatedFormat('d M Y, H:i') }} · {{ $order->source_label }}</span>
    </div>
</div>

@if(session('order_created'))
    <div class="ui-alert ui-alert-success" style="margin-bottom:18px">
        <x-admin-icon name="check"/>
        <div class="ui-spacer">Order tersimpan dengan kode <strong class="ui-mono">{{ $order->tracking_token }}</strong>.
            {{ session('client_notified') ? 'Kode tracking sedang dikirim otomatis ke WhatsApp klien.' : 'Kirim kode ini ke klien agar bisa memantau progres.' }}</div>
        <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="ui-btn ui-btn-primary ui-btn-sm"><x-admin-icon name="chat"/>Kirim via WhatsApp</a>
    </div>
@endif

<div class="ui-grid-main">
    <div class="ui-stack">
        {{-- Service details & estimate --}}
        <section class="ui-card">
            <div class="ui-card-head">
                <div><h2>{{ $order->service?->name ?? 'Layanan dihapus' }}</h2><p>Pilihan klien dan estimasi saat order dibuat / terakhir diedit.</p></div>
                <a href="{{ route('admin.orders.edit', $order) }}" class="ui-btn ui-btn-ghost ui-btn-sm"><x-admin-icon name="edit"/>Edit order</a>
            </div>
            <div class="ui-card-body">
                @if($order->detail_answers)
                    <dl class="ui-kv">
                        @foreach($order->detail_answers as $answer)
                            <div><dt>{{ $answer['label'] }}</dt><dd>{{ $answer['display'] }}</dd></div>
                        @endforeach
                    </dl>
                @else
                    <p class="ui-help">Order ini tidak memiliki field khusus layanan.</p>
                @endif
            </div>
            @if($estimate !== null)
                <div class="svc-estimate">
                    @if(count($order->estimate_lines) > 1)
                        <div class="svc-estimate-lines">
                            @foreach($order->estimate_lines as $i => $line)
                                <div><span>{{ $line['detail'] ?: $line['label'] }}</span><b>{{ $i ? '+' : '' }}@rupiah($line['amount'])</b></div>
                            @endforeach
                        </div>
                    @endif
                    <div class="svc-estimate-total"><span>Estimasi mulai dari</span><strong>@rupiah($estimate)</strong></div>
                </div>
            @endif
        </section>

        {{-- Brief --}}
        <section class="ui-card">
            <div class="ui-card-head">
                <div><h2>Brief</h2></div>
                @if($order->attachment_path)
                    <a href="{{ route('admin.orders.attachment', $order) }}" class="ui-btn ui-btn-ghost ui-btn-sm"><x-admin-icon name="download"/>Unduh lampiran</a>
                @endif
            </div>
            <div class="ui-card-body">
                <dl class="ui-kv">
                    <div><dt>Judul</dt><dd>{{ $order->title }}</dd></div>
                    <div><dt>Deadline</dt><dd @class(['orders-deadline', 'is-overdue' => $isOverdue])>{{ $order->deadline->locale('id')->translatedFormat('d F Y') }}@if($isOverdue) · lewat deadline @endif</dd></div>
                    <div><dt>Budget klien</dt><dd>{{ $order->budget ?: 'Tidak disebutkan' }}</dd></div>
                    <div><dt>Detail &amp; instruksi</dt><dd style="white-space:pre-line;font-weight:400">{{ $order->description }}</dd></div>
                </dl>
            </div>
        </section>

        {{-- Client --}}
        <section class="ui-card">
            <div class="ui-card-head">
                <div><h2>Klien</h2></div>
                <a href="{{ $waUrl }}" target="_blank" rel="noopener" class="ui-btn ui-btn-primary ui-btn-sm"><x-admin-icon name="chat"/>Kirim update via WhatsApp</a>
            </div>
            <div class="ui-card-body">
                <dl class="ui-kv">
                    <div><dt>Nama</dt><dd>{{ $order->client_name }}</dd></div>
                    <div><dt>WhatsApp</dt><dd><a class="ui-link" style="font-size:13px" href="https://wa.me/{{ $waPhone }}" target="_blank" rel="noopener">{{ $order->client_phone }}</a></dd></div>
                    <div><dt>Email</dt><dd>@if($order->client_email)<a class="ui-link" style="font-size:13px" href="mailto:{{ $order->client_email }}">{{ $order->client_email }}</a>@else<span class="ui-muted">Tidak ada</span>@endif</dd></div>
                    <div><dt>Sumber order</dt><dd>{{ $order->source_label }}</dd></div>
                </dl>
            </div>
        </section>

        {{-- History --}}
        <section class="ui-card">
            <div class="ui-card-head"><div><h2>Riwayat order</h2><p>Setiap perubahan tercatat otomatis.</p></div><x-admin-icon name="history" style="color:var(--muted)"/></div>
            <div class="ui-card-body">
                @forelse($order->activities as $activity)
                    <div class="ord-activity">
                        <span class="ord-activity-dot ord-activity-{{ $activity->action }}" aria-hidden="true"></span>
                        <div>
                            <p class="ord-activity-text">{{ $activity->description }}</p>
                            <p class="ui-help">{{ $activity->user?->name ?? ($activity->action === 'created' ? 'Klien' : 'Sistem') }} · <time datetime="{{ $activity->created_at->toIso8601String() }}">{{ $activity->created_at->locale('id')->translatedFormat('d M Y, H:i') }}</time></p>
                            @if($activity->changes)
                                <ul class="ord-changes">
                                    @foreach($activity->changes as $change)
                                        <li><span>{{ $change['field'] }}</span><s>{{ $change['from'] }}</s><b>{{ $change['to'] }}</b></li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="ui-help">Belum ada riwayat. Perubahan berikutnya akan tercatat di sini.</p>
                @endforelse
            </div>
        </section>
    </div>

    <aside class="ui-stack svc-aside">
        {{-- Status & agreed terms --}}
        <section class="ui-card">
            <div class="ui-card-head"><div><h2>Kesepakatan &amp; status</h2><p>Perbarui setelah konsultasi dengan klien.</p></div></div>
            <form method="POST" action="{{ route('admin.orders.updateStatus', $order) }}" class="ui-card-body"
                  x-data="{ ...@js($statusConfig), get needsPrice() { return ['processing','review','done'].includes(this.status) && !['processing','review','done'].includes(this.from) && String(this.price).trim() === ''; } }">
                @csrf
                @method('PATCH')
                <div class="ui-field">
                    <label class="ui-label" for="order-status">Status</label>
                    <select id="order-status" name="status" class="ui-select" x-model="status">
                        @foreach($statuses as $key => $meta)
                            <option value="{{ $key }}">{{ $meta['label'] }}</option>
                        @endforeach
                    </select>
                    @error('status')<p class="ui-error">{{ $message }}</p>@enderror
                </div>
                <div class="ui-field">
                    <label class="ui-label" for="order-price">Harga final (Rp)</label>
                    <input id="order-price" type="number" name="price_final" x-model="price" min="0" max="99999999.99" step="0.01" placeholder="Belum disepakati" class="ui-input" @error('price_final') aria-invalid="true" @enderror>
                    @if($estimate !== null)
                        <button type="button" class="ui-link" style="justify-self:start" @click="price = String(Math.round(estimate))">Pakai estimasi @rupiah($estimate)</button>
                    @endif
                    <p class="ui-error" x-show="needsPrice" x-cloak>Isi harga final dulu sebelum order mulai dikerjakan.</p>
                    @error('price_final')<p class="ui-error">{{ $message }}</p>@enderror
                    <p class="ui-help">Masuk laporan penghasilan saat order Selesai.@if($order->price_finalized_at) Disepakati {{ $order->price_finalized_at->locale('id')->translatedFormat('d M Y') }}.@endif</p>
                </div>
                <div class="ui-field">
                    <label class="ui-label" for="order-deadline">Deadline kesepakatan</label>
                    <input id="order-deadline" type="date" name="deadline" required value="{{ old('deadline', $order->deadline->format('Y-m-d')) }}" class="ui-input">
                    @error('deadline')<p class="ui-error">{{ $message }}</p>@enderror
                </div>
                <div class="ui-field">
                    <label class="ui-label" for="order-notes">Pesan untuk klien <small>tampil di tracking</small></label>
                    <textarea id="order-notes" name="admin_notes" rows="3" maxlength="2000" class="ui-textarea" style="min-height:80px" placeholder="Update, instruksi tambahan, dll.">{{ old('admin_notes', $order->admin_notes) }}</textarea>
                    @error('admin_notes')<p class="ui-error">{{ $message }}</p>@enderror
                </div>
                @if(\App\Support\NotificationSettings::whatsAppReady())
                    <input type="hidden" name="notify_client" value="0">
                    <label class="ui-check" style="margin-top:14px"><input type="checkbox" name="notify_client" value="1" checked> Kirim WhatsApp ke klien jika status atau harga final berubah</label>
                @endif
                <button type="submit" class="ui-btn ui-btn-primary ui-btn-block" style="margin-top:16px" :disabled="needsPrice"><x-admin-icon name="check"/>Simpan kesepakatan</button>
            </form>
        </section>

        {{-- Tracking link --}}
        <section class="ui-card ui-card-navy" x-data="{ copied: false, copy() { navigator.clipboard.writeText(@js(route('order.track', $order->tracking_token))).then(() => { this.copied = true; Alpine.store('adminToast').fire('Link tracking disalin!', 'success'); setTimeout(() => this.copied = false, 2500); }); } }">
            <div class="ui-card-body">
                <span class="svc-eyebrow">KODE TRACKING</span>
                <p class="ord-token ui-mono">{{ $order->tracking_token }}</p>
                <button type="button" class="ui-btn ui-btn-gold ui-btn-sm ui-btn-block" @click="copy()"><x-admin-icon name="copy"/><span x-text="copied ? 'Tersalin' : 'Salin link tracking'"></span></button>
                <p class="ui-muted" style="font-size:11px;margin-top:8px">Klien memasukkan 4 digit terakhir nomor WhatsApp untuk membuka tracking.</p>
            </div>
        </section>

        {{-- Delete --}}
        <section class="ui-card" x-data="{ confirming: false }">
            <div class="ui-card-body">
                <button type="button" class="ui-btn ui-btn-danger ui-btn-block" x-show="!confirming" @click="confirming = true"><x-admin-icon name="trash"/>Hapus order</button>
                <div x-show="confirming" x-cloak>
                    <p style="font-size:13px;font-weight:600">Hapus order #{{ $order->tracking_token }}?</p>
                    <p class="ui-help" style="margin:4px 0 12px">Order, lampiran, dan riwayatnya dihapus permanen.</p>
                    <div class="ui-row">
                        <button type="button" class="ui-btn ui-btn-ghost ui-btn-sm" @click="confirming = false">Batal</button>
                        <form method="POST" action="{{ route('admin.orders.destroy', $order) }}">@csrf @method('DELETE')<button type="submit" class="ui-btn ui-btn-danger ui-btn-sm"><x-admin-icon name="trash"/>Ya, hapus</button></form>
                    </div>
                </div>
            </div>
        </section>
    </aside>
</div>
@endsection
