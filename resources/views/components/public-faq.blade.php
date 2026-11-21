<section id="faq" class="legacy-faq py-16 sm:py-20 bg-cream"><div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8"><div class="text-center mb-10" data-aos="fade-up"><h2 class="font-display text-3xl sm:text-4xl font-extrabold text-navy-dark">Masih <span class="font-script text-gold">Penasaran?</span></h2><p class="text-gray-500 mt-3">Ini yang sering ditanyakan sebelum mulai.</p></div><div class="legacy-faq-items">
    @foreach([
        ['Bagaimana cara mulai order?', 'Pilih layanan yang sesuai, gunakan filter jurusan bila perlu, isi brief serta kontak, lalu tinjau dan kirim order. Kamu akan mendapat kode tracking. Admin meninjau brief sebelum menyepakati harga dan deadline bersama kamu.'],
        ['Apakah harga di katalog sudah harga final?', 'Harga katalog adalah harga mulai. Budget di form merupakan perkiraan dari kamu. Harga final ditentukan setelah konsultasi dan akan terlihat pada halaman tracking setelah admin mengisinya.'],
        ['Apakah deadline bisa berubah setelah konsultasi?', 'Bisa, sesuai kesepakatan dengan admin. Tanggal di form adalah deadline yang kamu ajukan. Admin dapat memperbaruinya, dan halaman tracking akan menampilkan deadline terbaru.'],
        ['Bagaimana cara memantau pengerjaan?', 'Buka menu Cek tracking, lalu masukkan kode order dan 4 digit terakhir nomor WhatsApp pemesan. Verifikasi berlaku 30 menit pada perangkat yang sama. Status, harga final, deadline, dan pesan admin diperiksa otomatis setiap menit.'],
        ['Bagaimana dengan pembayaran dan revisi?', 'Metode pembayaran, tahapan pembayaran, serta cakupan revisi dibahas bersama admin saat konsultasi. Konfirmasikan detailnya melalui WhatsApp sebelum pengerjaan dimulai.'],
        ['Kode tracking tidak ketemu, harus bagaimana?', 'Hubungi admin melalui WhatsApp dengan menyebutkan nama dan judul order. Admin akan membantu menemukan order kamu.'],
    ] as [$question, $answer])
        <details><summary>{{ $question }}<span aria-hidden="true">+</span></summary><p>{{ $answer }}</p></details>
    @endforeach
</div></div></section>
