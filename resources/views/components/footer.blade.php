<footer class="public-footer">
    <div class="public-container">
        <div class="public-footer-grid">
            <div class="public-footer-about">
                <a href="{{ route('home') }}" class="public-brand" aria-label="NativeCuy, beranda"><img src="{{ asset('images/nativecuy_icon_64x64.png') }}" alt="" width="40" height="40"><strong>Native<span>Cuy.</span></strong></a>
                <p>Teman urusan akademik & digital. Mulai dari cerita kebutuhanmu, sampai hasil yang kita sepakati bersama.</p>
                <div class="public-footer-socials">
                    <a href="https://wa.me/{{ $publicContact['wa_number'] }}" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp NativeCuy"><i class="fab fa-whatsapp" aria-hidden="true"></i></a>
                    <a href="https://instagram.com/{{ $publicContact['instagram'] }}" target="_blank" rel="noopener noreferrer" aria-label="Instagram NativeCuy"><i class="fab fa-instagram" aria-hidden="true"></i></a>
                    <a href="mailto:{{ $publicContact['email'] }}" aria-label="Email NativeCuy"><x-public-icon name="mail"/></a>
                </div>
            </div>
            <div><h2>Layanan kami</h2><ul>@forelse($footerServices as $service)<li><a href="{{ route('order.create', ['service' => $service->id]) }}">{{ $service->name }}</a></li>@empty<li><a href="https://wa.me/{{ $publicContact['wa_number'] }}" target="_blank" rel="noopener">Konsultasi kebutuhan</a></li>@endforelse</ul></div>
            <div><h2>Jelajahi</h2><ul><li><a href="{{ route('home') }}#cara-kerja">Cara kerja</a></li><li><a href="{{ route('home') }}#testimoni">Testimoni</a></li><li><a href="{{ route('home') }}#faq">Pertanyaan umum</a></li><li><a href="{{ route('order.trackSearch') }}">Cek tracking</a></li><li><a href="{{ route('order.create') }}">Buat order</a></li></ul></div>
            <div class="public-footer-contact"><h2>Yuk, ngobrol dulu</h2><ul><li><i class="fab fa-whatsapp" aria-hidden="true"></i><a href="https://wa.me/{{ $publicContact['wa_number'] }}" target="_blank" rel="noopener noreferrer">+{{ $publicContact['wa_number'] }}</a></li><li><x-public-icon name="mail"/><a href="mailto:{{ $publicContact['email'] }}">{{ $publicContact['email'] }}</a></li><li><x-public-icon name="pin"/><span>{{ $publicContact['location'] }}</span></li></ul></div>
        </div>
        <div class="public-footer-bottom"><span>© {{ now()->year }} NativeCuy. All rights reserved.</span><span>Brief jelas. Progres terpantau. Beres bareng.</span></div>
    </div>
</footer>
