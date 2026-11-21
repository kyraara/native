<section id="cara-kerja" class="cuy-process">
    <div class="public-container cuy-process-layout">
        <div class="cuy-process-intro">
            <h2>Mulai dari cerita.<br><span class="font-script">Sampai beres bareng.</span></h2>
            <p>Nggak perlu bingung mulai dari mana. Ceritakan kebutuhanmu, kita atur langkah berikutnya bersama.</p>
            <a class="public-button public-button-gold" href="{{ route('order.create') }}">Ceritakan kebutuhanmu <x-admin-icon name="arrow"/></a>
            <a class="cuy-process-tracking" href="{{ route('order.trackSearch') }}"><x-public-icon name="progress"/>Sudah order? Cek progresmu</a>
        </div>
        <ol class="cuy-process-steps" aria-label="Cara kerja NativeCuy">
            @foreach([
                ['icon' => 'brief', 'title' => 'Kamu cerita, kami pahami.', 'copy' => 'Pilih layanan, tulis kebutuhan, lalu lampirkan referensi kalau ada. Kamu langsung mendapat kode tracking.'],
                ['icon' => 'handshake', 'title' => 'Sepakati dulu, baru mulai.', 'copy' => 'Admin menghubungimu untuk membahas harga final, deadline, dan cakupan revisi. Semuanya jelas sebelum dikerjakan.'],
                ['icon' => 'progress', 'title' => 'Kami kerjakan. Kamu pantau.', 'copy' => 'Cek perkembangan dan pesan admin lewat tracking. Ada perubahan deadline? Diskusikan dengan admin, update-nya muncul di sana.'],
                ['icon' => 'delivery', 'title' => 'Cek hasilnya, beresin bareng.', 'copy' => 'Tinjau hasil bersama admin dan sampaikan masukanmu. Revisi mengikuti kesepakatan saat konsultasi.'],
            ] as $step)
                <li><span class="cuy-process-mark"><x-public-icon :name="$step['icon']"/><small>{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</small></span><div><h3>{{ $step['title'] }}</h3><p>{{ $step['copy'] }}</p></div></li>
            @endforeach
        </ol>
    </div>
    <div class="cuy-section-wave" aria-hidden="true"><svg viewBox="0 0 1440 60"><path d="M0 60V28C300 76 430 0 760 27S1200 70 1440 22V60Z" fill="#FDF8F0"/></svg></div>
</section>
