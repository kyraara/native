<section class="legacy-stats relative py-14 sm:py-16 bg-cream overflow-hidden" aria-label="Statistik NativeCuy">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"><div class="grid grid-cols-2 lg:grid-cols-4 gap-8">
        @foreach([
            ['value'=>$stats['total_orders'],'label'=>'Order selesai','icon'=>'fas fa-check-circle','color'=>'text-navy','bg'=>'bg-navy/10'],
            ['value'=>$stats['happy_clients'],'label'=>'Klien puas','icon'=>'fas fa-heart','color'=>'text-red-500','bg'=>'bg-red-50'],
            ['value'=>$stats['subjects'],'label'=>'Mata kuliah','icon'=>'fas fa-book','color'=>'text-gold','bg'=>'bg-gold/10'],
            ['value'=>$stats['rating'],'label'=>'Rating layanan','icon'=>'fas fa-star','color'=>'text-gold','bg'=>'bg-gold/10'],
        ] as $stat)
        <div class="text-center" data-aos="fade-up"><div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl {{ $stat['bg'] }} mb-4"><i class="{{ $stat['icon'] }} text-xl {{ $stat['color'] }}" aria-hidden="true"></i></div><div class="text-3xl sm:text-4xl font-extrabold font-display text-navy-dark mb-1"><strong>{{ $stat['value'] }}</strong>@if($loop->last)<span class="text-gold text-xl" aria-label="bintang">★</span>@endif</div><p class="text-gray-500 text-sm font-medium">{{ $stat['label'] }}</p></div>
        @endforeach
    </div></div>
    <div class="absolute bottom-0 left-0 right-0 pointer-events-none" aria-hidden="true"><svg viewBox="0 0 1440 56" class="w-full block"><path d="M0 56 L0 40 L1440 0 L1440 56 Z" fill="#ffffff"/></svg></div>
</section>
