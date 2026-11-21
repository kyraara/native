<?php

namespace App\Http\Requests;

use App\Models\Service;
use App\Models\Major;
use App\Services\ServiceFormSchema;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOrderRequest extends FormRequest
{
    private ?Service $resolvedService = null;
    private bool $serviceResolved = false;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $phone = Phone::normalize($this->input('client_phone'));

        $this->merge([
            'client_email' => mb_strtolower(trim(is_string($this->input('client_email')) ? $this->input('client_email') : '')),
            'client_phone' => $phone,
        ]);
    }

    /** The active service picked in the form, if any (memoised). */
    public function selectedService(): ?Service
    {
        if (! $this->serviceResolved) {
            $id = $this->input('service_id');
            $this->resolvedService = is_scalar($id) && ctype_digit((string) $id) ? Service::active()->find($id) : null;
            $this->serviceResolved = true;
        }

        return $this->resolvedService;
    }

    /** Raw answers to the service-specific fields. */
    public function details(): array
    {
        $details = $this->input('details');

        return is_array($details) ? $details : [];
    }

    public function rules(): array
    {
        $service = $this->selectedService();
        $detailRules = $service
            ? app(ServiceFormSchema::class)->rules($service->form_fields ?? [], $this->details())
            : [];

        return $detailRules + [
            'details' => ['nullable', 'array'],
            'submission_token' => ['required', 'string', 'size:36'],
            'website' => ['prohibited'],
            'major_id' => ['nullable', 'integer', Rule::exists(Major::class, 'id')->where('is_active', true)],
            'service_id' => [
                'required',
                Rule::exists(Service::class, 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'deadline' => ['required', 'date_format:Y-m-d', 'after:'.now('Asia/Jakarta')->toDateString()],
            'budget' => ['nullable', 'string', 'max:50'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
            'client_name' => ['required', 'string', 'max:100'],
            'client_email' => ['required', 'email:rfc', 'max:100'],
            'client_phone' => ['required', 'regex:/^62[0-9]{8,13}$/'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || !$this->filled('major_id')) {
                return;
            }

            $available = Service::active()->whereKey($this->input('service_id'))
                ->whereHas('majors', fn ($query) => $query->whereKey($this->input('major_id'))->where('is_active', true))
                ->exists();

            if (!$available) {
                $validator->errors()->add('service_id', 'Layanan tidak tersedia untuk jurusan yang dipilih.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'string' => ':attribute harus berupa teks.',
            'max.string' => ':attribute terlalu panjang (maksimal :max karakter).',
            'service_id.exists' => 'Layanan ini tidak lagi tersedia. Pilih layanan lainnya.',
            'major_id.exists' => 'Jurusan ini tidak lagi tersedia. Pilih jurusan lainnya.',
            'deadline.after' => 'Pilih deadline mulai besok atau setelahnya.',
            'deadline.date_format' => 'Masukkan tanggal deadline yang valid.',
            'client_email.email' => 'Masukkan alamat email yang valid.',
            'client_phone.regex' => 'Masukkan nomor WhatsApp aktif dengan awalan 08 atau 62.',
            'attachment.max' => 'Ukuran lampiran maksimal 10 MB.',
            'attachment.mimes' => 'Gunakan lampiran PDF, DOC, DOCX, JPG, JPEG, atau PNG.',
            'in' => 'Pilihan :attribute tidak tersedia.',
            'integer' => ':attribute harus berupa angka bulat.',
            'min.numeric' => ':attribute minimal :min.',
            'max.numeric' => ':attribute maksimal :max.',
            'min.array' => 'Pilih minimal satu :attribute.',
            'distinct' => ':attribute berisi pilihan ganda.',
            'attachment.uploaded' => 'Lampiran gagal diunggah. Gunakan berkas maksimal 10 MB.',
            'submission_token.*' => 'Sesi formulir tidak valid. Muat ulang halaman sebelum mencoba kembali.',
            'website.prohibited' => 'Pengiriman belum dapat diterima. Kosongkan kolom website.',
        ];
    }

    public function attributes(): array
    {
        $service = $this->selectedService();
        $detailAttributes = $service ? app(ServiceFormSchema::class)->attributes($service->form_fields ?? []) : [];

        return $detailAttributes + ['service_id' => 'Layanan', 'major_id' => 'Jurusan', 'title' => 'Judul', 'description' => 'Detail kebutuhan', 'deadline' => 'Deadline', 'client_name' => 'Nama', 'client_email' => 'Email', 'client_phone' => 'Nomor WhatsApp'];
    }
}
