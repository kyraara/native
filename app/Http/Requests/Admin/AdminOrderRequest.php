<?php

namespace App\Http\Requests\Admin;

use App\Models\Order;
use App\Models\Service;
use App\Services\ServiceFormSchema;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Admin create/edit of an order. Admins may pick inactive services and any deadline. */
class AdminOrderRequest extends FormRequest
{
    private ?Service $resolvedService = null;
    private bool $serviceResolved = false;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $email = is_string($this->input('client_email')) ? mb_strtolower(trim($this->input('client_email'))) : '';

        $this->merge([
            'client_phone' => Phone::normalize($this->input('client_phone')),
            'client_email' => $email === '' ? null : $email,
        ]);
    }

    public function isCreating(): bool
    {
        return $this->route('order') === null;
    }

    public function selectedService(): ?Service
    {
        if (! $this->serviceResolved) {
            $id = $this->input('service_id');
            $this->resolvedService = is_scalar($id) && ctype_digit((string) $id) ? Service::find($id) : null;
            $this->serviceResolved = true;
        }

        return $this->resolvedService;
    }

    public function details(): array
    {
        return is_array($this->input('details')) ? $this->input('details') : [];
    }

    public function rules(): array
    {
        $service = $this->selectedService();
        $detailRules = $service ? app(ServiceFormSchema::class)->rules($service->form_fields ?? [], $this->details()) : [];

        $rules = $detailRules + [
            'service_id' => ['required', 'integer', Rule::exists(Service::class, 'id')],
            'details' => ['nullable', 'array'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'deadline' => ['required', 'date_format:Y-m-d'],
            'budget' => ['nullable', 'string', 'max:50'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,jpg,jpeg,png'],
            'client_name' => ['required', 'string', 'max:100'],
            'client_email' => ['nullable', 'email:rfc', 'max:100'],
            'client_phone' => ['required', 'regex:/^62[0-9]{8,13}$/'],
            'source' => ['required', Rule::in(array_keys(Order::SOURCES))],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ];

        if ($this->isCreating()) {
            $rules['status'] = ['required', Rule::in(array_keys(Order::STATUSES))];
            $rules['price_final'] = ['nullable', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'];
            $rules['notify_client'] = ['nullable', 'boolean'];
        } else {
            $rules['reset_price_final'] = ['nullable', 'boolean'];
        }

        return $rules;
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->isCreating() && Order::needsFinalPriceFor('pending', (string) $this->input('status'), $this->input('price_final'))) {
                $validator->errors()->add('price_final', 'Isi harga final untuk order yang langsung dikerjakan, atau pilih status Menunggu Konfirmasi.');
            }
        }];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'in' => 'Pilihan :attribute tidak tersedia.',
            'integer' => ':attribute harus berupa angka bulat.',
            'min.numeric' => ':attribute minimal :min.',
            'max.numeric' => ':attribute maksimal :max.',
            'min.array' => 'Pilih minimal satu :attribute.',
            'max.string' => ':attribute terlalu panjang (maksimal :max karakter).',
            'deadline.date_format' => 'Masukkan tanggal deadline yang valid.',
            'client_email.email' => 'Masukkan alamat email yang valid.',
            'client_phone.regex' => 'Masukkan nomor WhatsApp aktif dengan awalan 08 atau 62.',
            'price_final.decimal' => 'Harga final maksimal 2 angka di belakang koma.',
            'attachment.max' => 'Ukuran lampiran maksimal 10 MB.',
            'attachment.mimes' => 'Gunakan lampiran PDF, DOC, DOCX, JPG, JPEG, atau PNG.',
        ];
    }

    public function attributes(): array
    {
        $service = $this->selectedService();
        $detailAttributes = $service ? app(ServiceFormSchema::class)->attributes($service->form_fields ?? []) : [];

        return $detailAttributes + [
            'service_id' => 'Layanan', 'title' => 'Judul', 'description' => 'Detail kebutuhan', 'deadline' => 'Deadline',
            'client_name' => 'Nama klien', 'client_email' => 'Email', 'client_phone' => 'Nomor WhatsApp',
            'source' => 'Sumber order', 'status' => 'Status', 'price_final' => 'Harga final', 'admin_notes' => 'Pesan untuk klien',
        ];
    }
}
