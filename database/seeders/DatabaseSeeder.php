<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@nativecuy.id'],
            ['name' => 'Admin NativeCuy', 'password' => Hash::make('nativecuy2024')]
        );

        $services = [
            ['name' => 'Tugas Kuliah',    'slug' => 'tugas-kuliah',    'description' => 'Essay, laporan praktikum, tugas individu & kelompok semua jurusan.',                'icon_class' => 'fas fa-graduation-cap', 'base_price' => 50000,  'sort_order' => 1],
            ['name' => 'Makalah & Skripsi','slug' => 'makalah-skripsi', 'description' => 'Makalah ilmiah, karya tulis ilmiah, hingga skripsi dengan referensi valid.',        'icon_class' => 'fas fa-scroll',         'base_price' => 150000, 'sort_order' => 2],
            ['name' => 'Laporan',          'slug' => 'laporan',          'description' => 'Laporan PKL, KKN, praktikum, penelitian, dan laporan kerja profesional.',           'icon_class' => 'fas fa-file-alt',       'base_price' => 75000,  'sort_order' => 3],
            ['name' => 'Presentasi PPT',   'slug' => 'presentasi-ppt',   'description' => 'Slide presentasi profesional dengan desain menarik dan konten terstruktur.',        'icon_class' => 'fas fa-chalkboard',     'base_price' => 60000,  'sort_order' => 4],
            ['name' => 'Pembuatan Web',    'slug' => 'pembuatan-web',    'description' => 'Website tugas kuliah, portofolio, landing page, hingga sistem informasi lengkap.', 'icon_class' => 'fas fa-laptop-code',    'base_price' => 300000, 'sort_order' => 5],
            ['name' => 'Lainnya',          'slug' => 'lainnya',          'description' => 'Mind mapping, infografis, terjemahan, resume, proposal bisnis, dan lainnya.',       'icon_class' => 'fas fa-ellipsis-h',     'base_price' => 40000,  'sort_order' => 6],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(['slug' => $service['slug']], $service);
        }

        $tugas = Service::where('slug', 'tugas-kuliah')->first();
        $makalah = Service::where('slug', 'makalah-skripsi')->first();
        $web = Service::where('slug', 'pembuatan-web')->first();
        $laporan = Service::where('slug', 'laporan')->first();
        $ppt = Service::where('slug', 'presentasi-ppt')->first();

        $testimonials = [
            ['client_name' => 'Rizky A.',   'avatar_initials' => 'RA', 'service_id' => $tugas?->id,   'rating' => 5, 'comment' => 'Gila cepet banget selesainya, deadline H-1 langsung beres. Nilai A pula! Recommended banget deh.', 'is_featured' => true],
            ['client_name' => 'Sinta D.',   'avatar_initials' => 'SD', 'service_id' => $makalah?->id, 'rating' => 5, 'comment' => 'Skripsi bab 1-3 kelar dalam 5 hari. Dosen approve langsung tanpa revisi berarti. Thank you NativeCuy!', 'is_featured' => true],
            ['client_name' => 'Budi P.',    'avatar_initials' => 'BP', 'service_id' => $web?->id,     'rating' => 5, 'comment' => 'Website e-commerce untuk tugas akhir jadi mantap. Fitur lengkap, desain bagus. Dapat nilai terbaik!', 'is_featured' => true],
            ['client_name' => 'Mega R.',    'avatar_initials' => 'MR', 'service_id' => $laporan?->id, 'rating' => 5, 'comment' => 'Laporan KKN 80 halaman selesai 3 hari. Formatnya rapi, referensi lengkap. Puas banget!', 'is_featured' => true],
            ['client_name' => 'Dimas K.',   'avatar_initials' => 'DK', 'service_id' => $ppt?->id,     'rating' => 5, 'comment' => 'PPT seminar hasil keren abis. Desainnya professional, bukan template biasa. Dipuji dosen!', 'is_featured' => true],
            ['client_name' => 'Ayu F.',     'avatar_initials' => 'AF', 'service_id' => $tugas?->id,   'rating' => 4, 'comment' => 'Revisi cepat dan komunikatif. Hasil akhir memuaskan. Pasti balik lagi semester depan!', 'is_featured' => false],
            ['client_name' => 'Fajar M.',   'avatar_initials' => 'FM', 'service_id' => $makalah?->id, 'rating' => 5, 'comment' => 'Makalah seminar nasional accepted. Abstrak dan isinya bagus banget. Worth it!', 'is_featured' => true],
            ['client_name' => 'Nadia L.',   'avatar_initials' => 'NL', 'service_id' => $laporan?->id, 'rating' => 5, 'comment' => 'Laporan PKL selesai tepat waktu, formatnya sesuai kampus. Admin ramah dan fast respon!', 'is_featured' => false],
        ];

        foreach ($testimonials as $testimonial) {
            Testimonial::create($testimonial);
        }

        $settings = [
            ['key' => 'wa_number',      'value' => '6281234567890'],
            ['key' => 'email',          'value' => 'order@nativecuy.id'],
            ['key' => 'instagram',      'value' => '@nativecuy.id'],
            ['key' => 'total_orders',   'value' => '500'],
            ['key' => 'happy_clients',  'value' => '350'],
            ['key' => 'subjects',       'value' => '50'],
            ['key' => 'rating',         'value' => '4.9'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], ['value' => $setting['value']]);
        }
    }
}
