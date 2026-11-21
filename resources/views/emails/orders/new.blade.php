<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Baru NativeCuy</title>
</head>
<body style="margin:0;background:#f3f4f6;font-family:Arial,sans-serif;color:#111827;">
    <div style="max-width:640px;margin:0 auto;padding:32px 16px;">
        <div style="background:#ffffff;border-radius:12px;padding:28px;border:1px solid #e5e7eb;">
            <h1 style="margin:0 0 8px;font-size:24px;">Order baru masuk</h1>
            <p style="margin:0 0 24px;color:#6b7280;">Kode tracking: <strong>{{ $order->tracking_token }}</strong></p>

            <table role="presentation" style="width:100%;border-collapse:collapse;">
                <tr><td style="padding:7px 0;color:#6b7280;">Layanan</td><td style="padding:7px 0;text-align:right;">{{ $order->service?->name ?? 'Tidak tersedia' }}</td></tr>
                <tr><td style="padding:7px 0;color:#6b7280;">Judul</td><td style="padding:7px 0;text-align:right;">{{ $order->title }}</td></tr>
                <tr><td style="padding:7px 0;color:#6b7280;">Pelanggan</td><td style="padding:7px 0;text-align:right;">{{ $order->client_name }}</td></tr>
                <tr><td style="padding:7px 0;color:#6b7280;">Email</td><td style="padding:7px 0;text-align:right;">{{ $order->client_email ?: '-' }}</td></tr>
                @foreach($order->detail_answers ?? [] as $answer)
                <tr><td style="padding:7px 0;color:#6b7280;">{{ $answer['label'] }}</td><td style="padding:7px 0;text-align:right;">{{ $answer['display'] }}</td></tr>
                @endforeach
                <tr><td style="padding:7px 0;color:#6b7280;">Estimasi mulai dari</td><td style="padding:7px 0;text-align:right;font-weight:700;">{{ $order->price_estimate !== null ? \App\Support\Money::rupiah($order->price_estimate) : '-' }}</td></tr>
                <tr><td style="padding:7px 0;color:#6b7280;">WhatsApp</td><td style="padding:7px 0;text-align:right;">{{ $order->client_phone }}</td></tr>
                <tr><td style="padding:7px 0;color:#6b7280;">Deadline</td><td style="padding:7px 0;text-align:right;">{{ $order->deadline->format('d-m-Y') }}</td></tr>
                <tr><td style="padding:7px 0;color:#6b7280;">Budget</td><td style="padding:7px 0;text-align:right;">{{ $order->budget ?: 'Belum ditentukan' }}</td></tr>
            </table>

            <div style="margin-top:24px;padding:16px;background:#f9fafb;border-radius:8px;">
                <strong>Deskripsi</strong>
                <p style="margin:8px 0 0;white-space:pre-line;">{{ $order->description }}</p>
            </div>

            <p style="margin:24px 0 0;">
                <a href="{{ route('admin.orders.show', $order) }}" style="display:inline-block;background:#4f46e5;color:#ffffff;text-decoration:none;padding:12px 18px;border-radius:8px;font-weight:bold;">Buka detail order</a>
            </p>
        </div>
    </div>
</body>
</html>
