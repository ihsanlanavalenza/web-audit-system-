<!doctype html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Undangan WebAudit</title>
</head>

<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.5;">
    <h2>Undangan Akses WebAudit</h2>

    <p>Anda menerima undangan sebagai <strong>{{ strtoupper($invitation->role) }}</strong>.</p>

    @if ($invitation->expires_at)
        <p>Undangan berlaku sampai: <strong>{{ $invitation->expires_at->format('d M Y H:i') }}</strong></p>
    @endif

    <p>Silakan daftar melalui link berikut:</p>

    <p>
        <a href="{{ route('register', ['token' => $invitation->token]) }}"
            style="display: inline-block; background: #2563eb; color: #fff; text-decoration: none; padding: 10px 16px; border-radius: 6px;">
            Buka Undangan
        </a>
    </p>

    <p>Jika tombol tidak bisa diklik, salin URL ini ke browser:</p>
    <p>{{ route('register', ['token' => $invitation->token]) }}</p>

    <p>Jika Anda sudah memiliki akun dengan email ini, silakan login seperti biasa. Undangan akan aktif otomatis setelah
        login.</p>
</body>

</html>
