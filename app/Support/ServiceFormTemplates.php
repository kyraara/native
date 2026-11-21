<?php

namespace App\Support;

/**
 * Ready-made field sets. Used by the seeder and by the admin "Mulai dari template" picker.
 * Prices are example values — admins are expected to adjust them in the catalog.
 */
class ServiceFormTemplates
{
    public static function all(): array
    {
        return [
            'jurnal' => ['name' => 'Publish Jurnal', 'fields' => self::jurnal()],
            'skripsi' => ['name' => 'Makalah & Skripsi', 'fields' => self::skripsi()],
            'tugas' => ['name' => 'Tugas Kuliah', 'fields' => self::tugas()],
            'laporan' => ['name' => 'Laporan', 'fields' => self::laporan()],
            'ppt' => ['name' => 'Presentasi PPT', 'fields' => self::ppt()],
            'web' => ['name' => 'Pembuatan Web', 'fields' => self::web()],
        ];
    }

    public static function get(string $key): ?array
    {
        return self::all()[$key]['fields'] ?? null;
    }

    /** Seeded service slug → template key. */
    public static function forSlug(string $slug): ?array
    {
        return match ($slug) {
            'publish-jurnal' => self::jurnal(),
            'makalah-skripsi' => self::skripsi(),
            'tugas-kuliah' => self::tugas(),
            'laporan' => self::laporan(),
            'presentasi-ppt' => self::ppt(),
            'pembuatan-web' => self::web(),
            default => null,
        };
    }

    private static function o(string $value, string $label, ?int $price = null): array
    {
        return ['value' => $value, 'label' => $label, 'price' => $price];
    }

    private static function jurnal(): array
    {
        return [
            ['key' => 'indeksasi', 'label' => 'Target indeksasi', 'type' => 'radio', 'required' => true, 'pricing' => 'base',
                'help' => 'Pilih SINTA lalu tentukan peringkatnya.',
                'options' => [self::o('sinta', 'SINTA'), self::o('non_sinta', 'Non-SINTA (Garuda / OJS)', 1000000), self::o('scopus', 'Scopus', 7500000)]],
            ['key' => 'peringkat_sinta', 'label' => 'Peringkat SINTA', 'type' => 'select', 'required' => true, 'pricing' => 'base',
                'show_if' => ['field' => 'indeksasi', 'values' => ['sinta']],
                'options' => [
                    self::o('s1', 'SINTA 1', 6000000), self::o('s2', 'SINTA 2', 4000000), self::o('s3', 'SINTA 3', 2500000),
                    self::o('s4', 'SINTA 4', 2000000), self::o('s5', 'SINTA 5', 1500000), self::o('s6', 'SINTA 6', 1250000),
                ]],
            ['key' => 'kondisi_naskah', 'label' => 'Kondisi naskah', 'type' => 'radio', 'required' => true, 'pricing' => 'add',
                'options' => [self::o('belum_ada', 'Belum ada naskah (perlu ditulis)', 1000000), self::o('draft', 'Sudah ada draft', 0), self::o('siap', 'Siap submit', 0)]],
            ['key' => 'bidang_ilmu', 'label' => 'Bidang ilmu', 'type' => 'text', 'required' => true, 'pricing' => 'none',
                'placeholder' => 'Contoh: Manajemen pemasaran'],
            ['key' => 'bahasa', 'label' => 'Bahasa naskah', 'type' => 'radio', 'required' => true, 'pricing' => 'none',
                'options' => [self::o('indonesia', 'Indonesia'), self::o('inggris', 'Inggris')]],
            ['key' => 'bantuan_tambahan', 'label' => 'Bantuan tambahan', 'type' => 'checkbox', 'required' => false, 'pricing' => 'add',
                'options' => [self::o('turnitin', 'Parafrase & cek Turnitin', 250000), self::o('translate', 'Translate ke bahasa Inggris', 500000), self::o('revisi', 'Pendampingan revisi reviewer', 300000)]],
            ['key' => 'target_jurnal', 'label' => 'Target jurnal', 'type' => 'text', 'required' => false, 'pricing' => 'none',
                'placeholder' => 'Nama atau link jurnal, jika sudah ada'],
        ];
    }

    private static function skripsi(): array
    {
        return [
            ['key' => 'paket', 'label' => 'Paket', 'type' => 'radio', 'required' => true, 'pricing' => 'base',
                'options' => [
                    self::o('makalah', 'Makalah', 150000), self::o('per_bab', 'Per bab', 0),
                    self::o('sempro', 'Paket Sempro (Bab 1–3)', 1200000), self::o('skripsi', 'Paket Skripsi Full (Bab 1–5)', 2500000),
                ]],
            ['key' => 'bab', 'label' => 'Bab yang dikerjakan', 'type' => 'checkbox', 'required' => true, 'pricing' => 'add',
                'show_if' => ['field' => 'paket', 'values' => ['per_bab']],
                'options' => [self::o('bab1', 'Bab 1', 250000), self::o('bab2', 'Bab 2', 300000), self::o('bab3', 'Bab 3', 350000), self::o('bab4', 'Bab 4', 450000), self::o('bab5', 'Bab 5', 200000)]],
            ['key' => 'jenjang', 'label' => 'Jenjang', 'type' => 'radio', 'required' => true, 'pricing' => 'add',
                'options' => [self::o('d3', 'D3', 0), self::o('s1', 'S1', 0), self::o('s2', 'S2', 500000)]],
            ['key' => 'metode', 'label' => 'Metode penelitian', 'type' => 'radio', 'required' => false, 'pricing' => 'none',
                'options' => [self::o('kuantitatif', 'Kuantitatif'), self::o('kualitatif', 'Kualitatif'), self::o('rnd', 'R&D'), self::o('belum_tahu', 'Belum tahu')]],
            ['key' => 'gaya_sitasi', 'label' => 'Gaya sitasi', 'type' => 'select', 'required' => false, 'pricing' => 'none',
                'options' => [self::o('apa', 'APA'), self::o('harvard', 'Harvard'), self::o('ieee', 'IEEE'), self::o('kampus', 'Ikuti pedoman kampus')]],
            ['key' => 'cek_plagiasi', 'label' => 'Layanan tambahan', 'type' => 'checkbox', 'required' => false, 'pricing' => 'add',
                'options' => [self::o('turnitin', 'Cek plagiasi Turnitin + laporan', 75000)]],
        ];
    }

    private static function tugas(): array
    {
        return [
            ['key' => 'jenis_tugas', 'label' => 'Jenis tugas', 'type' => 'radio', 'required' => true, 'pricing' => 'base',
                'options' => [self::o('essay', 'Essay / artikel', 50000), self::o('praktikum', 'Laporan praktikum', 60000), self::o('soal', 'Soal / latihan', 40000), self::o('studi_kasus', 'Studi kasus / analisis', 75000)]],
            ['key' => 'jumlah', 'label' => 'Jumlah halaman / soal', 'type' => 'number', 'required' => true, 'pricing' => 'per_unit',
                'unit_price' => 10000, 'unit_label' => 'halaman/soal', 'min' => 1, 'max' => 200],
            ['key' => 'mata_kuliah', 'label' => 'Mata kuliah', 'type' => 'text', 'required' => true, 'pricing' => 'none',
                'placeholder' => 'Contoh: Statistika Dasar'],
            ['key' => 'pengerjaan', 'label' => 'Jenis pengerjaan', 'type' => 'radio', 'required' => true, 'pricing' => 'none',
                'options' => [self::o('individu', 'Individu'), self::o('kelompok', 'Kelompok')]],
        ];
    }

    private static function laporan(): array
    {
        return [
            ['key' => 'jenis_laporan', 'label' => 'Jenis laporan', 'type' => 'radio', 'required' => true, 'pricing' => 'base',
                'options' => [self::o('pkl', 'PKL / Magang', 150000), self::o('kkn', 'KKN', 150000), self::o('praktikum', 'Praktikum', 75000), self::o('penelitian', 'Penelitian', 200000), self::o('kerja', 'Laporan kerja', 150000)]],
            ['key' => 'jumlah_halaman', 'label' => 'Perkiraan jumlah halaman', 'type' => 'number', 'required' => true, 'pricing' => 'per_unit',
                'unit_price' => 7500, 'unit_label' => 'halaman', 'min' => 5, 'max' => 300],
            ['key' => 'template_kampus', 'label' => 'Template dari kampus', 'type' => 'radio', 'required' => false, 'pricing' => 'none',
                'help' => 'Jika ada, unggah template di bagian lampiran.',
                'options' => [self::o('ada', 'Ada'), self::o('tidak', 'Tidak ada')]],
        ];
    }

    private static function ppt(): array
    {
        return [
            ['key' => 'keperluan', 'label' => 'Keperluan presentasi', 'type' => 'radio', 'required' => true, 'pricing' => 'base',
                'options' => [self::o('kelas', 'Tugas kelas', 50000), self::o('seminar', 'Seminar / sempro', 75000), self::o('sidang', 'Sidang', 100000), self::o('bisnis', 'Bisnis / pitching', 150000)]],
            ['key' => 'jumlah_slide', 'label' => 'Jumlah slide', 'type' => 'number', 'required' => true, 'pricing' => 'per_unit',
                'unit_price' => 5000, 'unit_label' => 'slide', 'min' => 5, 'max' => 100],
            ['key' => 'gaya_desain', 'label' => 'Gaya desain', 'type' => 'radio', 'required' => false, 'pricing' => 'none',
                'options' => [self::o('formal', 'Formal'), self::o('kreatif', 'Kreatif'), self::o('minimalis', 'Minimalis'), self::o('template_kampus', 'Ikuti template kampus')]],
            ['key' => 'tambahan', 'label' => 'Tambahan', 'type' => 'checkbox', 'required' => false, 'pricing' => 'add',
                'options' => [self::o('speaker_notes', 'Speaker notes', 50000), self::o('animasi', 'Animasi & transisi', 50000)]],
        ];
    }

    private static function web(): array
    {
        return [
            ['key' => 'jenis_web', 'label' => 'Jenis website', 'type' => 'radio', 'required' => true, 'pricing' => 'base',
                'options' => [self::o('landing', 'Landing page', 300000), self::o('portofolio', 'Portofolio', 400000), self::o('sistem_informasi', 'Sistem informasi (CRUD)', 1000000), self::o('ecommerce', 'E-commerce / toko online', 1500000)]],
            ['key' => 'tech_stack', 'label' => 'Teknologi', 'type' => 'select', 'required' => true, 'pricing' => 'none',
                'options' => [self::o('laravel', 'Laravel'), self::o('codeigniter', 'CodeIgniter'), self::o('php_native', 'PHP native'), self::o('react', 'React / Next.js'), self::o('bebas', 'Bebas (rekomendasi kami)')]],
            ['key' => 'database', 'label' => 'Database', 'type' => 'radio', 'required' => false, 'pricing' => 'none',
                'options' => [self::o('mysql', 'MySQL'), self::o('postgresql', 'PostgreSQL'), self::o('tanpa', 'Tanpa database')]],
            ['key' => 'tambahan', 'label' => 'Tambahan', 'type' => 'checkbox', 'required' => false, 'pricing' => 'add',
                'options' => [self::o('hosting', 'Hosting & domain 1 tahun', 350000), self::o('dokumentasi', 'Dokumentasi / laporan teknis', 150000), self::o('revisi', 'Revisi ekstra', 100000)]],
        ];
    }
}
