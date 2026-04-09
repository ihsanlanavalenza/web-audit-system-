<!doctype html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Invitation WebAudit</title>
</head>

<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6;">
    @php
        $kapName = $invitation->kapProfile?->nama_kap ?? '-';
        $clientName = $invitation->client?->nama_client ?? '-';
        $roleLabel = strtoupper($invitation->role);
    @endphp

    @if ($recipientType === 'accepted_user')
        <h2>Akses Anda Telah Aktif</h2>
        <p>Halo <strong>{{ $acceptedUser->name }}</strong>, undangan Anda sudah berhasil diterima.</p>
        <p>Role aktif saat ini: <strong>{{ $roleLabel }}</strong>.</p>
    @elseif($recipientType === 'inviter')
        <h2>Undangan Berhasil Diterima</h2>
        <p>User <strong>{{ $acceptedUser->name }}</strong> ({{ $acceptedUser->email }}) telah menerima undangan WebAudit
            Anda.</p>
        <p>Role yang diaktifkan: <strong>{{ $roleLabel }}</strong>.</p>
    @else
        <h2>Notifikasi Update Akses User</h2>
        <p>User <strong>{{ $acceptedUser->name }}</strong> ({{ $acceptedUser->email }}) telah menerima undangan
            WebAudit.</p>
        <p>Role user diperbarui menjadi <strong>{{ $roleLabel }}</strong>.</p>
    @endif

    <ul>
        <li>KAP: <strong>{{ $kapName }}</strong></li>
        <li>Klien: <strong>{{ $clientName }}</strong></li>
        <li>Waktu diterima: <strong>{{ $invitation->accepted_at?->format('d M Y H:i') }}</strong></li>
    </ul>

    <p>Silakan login untuk melanjutkan aktivitas:</p>
    <p>
        <a href="{{ route('login') }}"
            style="display:inline-block;background:#2563eb;color:#fff;text-decoration:none;padding:10px 16px;border-radius:6px;">
            Login ke WebAudit
        </a>
    </p>
</body>

</html>
