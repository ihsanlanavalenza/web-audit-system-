<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reminder Data Audit</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 32px 20px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 32px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 24px;
        }

        .logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #2563eb, #60a5fa);
            color: white;
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 12px;
        }

        h1 {
            color: #1e293b;
            font-size: 20px;
            margin: 0 0 8px;
        }

        .subtitle {
            color: #64748b;
            font-size: 14px;
            margin: 0;
        }

        .alert {
            background: #fef2f2;
            border-left: 4px solid #ef4444;
            border-radius: 8px;
            padding: 16px;
            margin: 20px 0;
        }

        .alert-title {
            color: #dc2626;
            font-weight: 600;
            font-size: 14px;
            margin: 0 0 4px;
        }

        .alert-text {
            color: #7f1d1d;
            font-size: 13px;
            margin: 0;
        }

        .details {
            background: #f8fafc;
            border-radius: 8px;
            padding: 16px;
            margin: 20px 0;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #64748b;
            font-size: 13px;
            font-weight: 500;
        }

        .detail-value {
            color: #1e293b;
            font-size: 13px;
            font-weight: 600;
            text-align: right;
        }

        .footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .footer p {
            color: #94a3b8;
            font-size: 12px;
            margin: 4px 0;
        }
    </style>
</head>

<body>
    @php
        $followupDays = (int) ($followupLevel ?? 1);
        $followupLabel = match ($followupDays) {
            7 => 'Follow-up 7 Hari',
            3 => 'Follow-up 3 Hari',
            default => 'Follow-up 1 Hari',
        };
        $followupStage = match ($followupDays) {
            7 => 'ketiga',
            3 => 'kedua',
            default => 'pertama',
        };
    @endphp
    <div class="container">
        <div class="card">
            <div class="header">
                <div class="logo">WA</div>
                <h1>Reminder: Data Audit Belum Diterima</h1>
                <p class="subtitle">Sistem WebAudit — Client Assistance Schedule</p>
            </div>

            <div class="alert">
                <p class="alert-title">{{ $followupLabel }} - Terlambat {{ $daysOverdue }} Hari</p>
                <p class="alert-text">
                    Ini adalah pengingat {{ $followupStage }}. Data request berikut masih belum diterima setelah
                    melewati batas waktu. Mohon segera upload dokumen yang diminta.
                </p>
            </div>

            <div class="details">
                <div class="detail-row">
                    <span class="detail-label">Section</span>
                    <span class="detail-value">{{ $dataRequest->section }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Account / Process</span>
                    <span class="detail-value">{{ $dataRequest->account_process ?? '-' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Description</span>
                    <span class="detail-value">{{ $dataRequest->description ?? '-' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Request Date</span>
                    <span class="detail-value">{{ $dataRequest->request_date?->format('d/m/Y') ?? '-' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Expected Received</span>
                    <span class="detail-value">{{ $dataRequest->expected_received?->format('d/m/Y') ?? '-' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">KAP</span>
                    <span class="detail-value">{{ $kapName }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Client</span>
                    <span class="detail-value">{{ $clientName }}</span>
                </div>
            </div>

            <p style="color: #475569; font-size: 14px; line-height: 1.6;">
                Mohon segera login ke sistem WebAudit dan upload dokumen yang diperlukan melalui halaman <strong>Client
                    Assistance Schedule</strong>.
            </p>

            <div class="footer">
                <p>Email ini dikirim otomatis oleh sistem WebAudit.</p>
                <p>{{ $kapName }}</p>
            </div>
        </div>
    </div>
</body>

</html>
