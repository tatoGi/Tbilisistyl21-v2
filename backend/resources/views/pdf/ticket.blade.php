<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #000;
            color: #fff;
            font-family: Arial, sans-serif;
        }
        .ticket {
            width: 600px;
            margin: 20px auto;
            border: 3px solid #f5a623;
            padding: 30px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #f5a623;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #f5a623;
            font-size: 28px;
            margin: 0;
        }
        .header h2 {
            color: #fff;
            font-size: 18px;
            margin: 10px 0 0;
        }
        .info {
            margin: 15px 0;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #333;
        }
        .info-label {
            color: #f5a623;
            font-weight: bold;
        }
        .qr-section {
            text-align: center;
            margin: 30px 0;
        }
        .qr-section img {
            width: 250px;
            height: 250px;
        }
        .footer {
            text-align: center;
            color: #888;
            font-size: 12px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="header">
            <h1>TbilisiStyle21</h1>
            <h2>{{ $eventName ?? 'Event Ticket' }}</h2>
        </div>

        <div class="info">
            <div class="info-row">
                <span class="info-label">Name:</span>
                <span>{{ $name }} {{ $surname }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Date:</span>
                <span>{{ $eventDate ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Location:</span>
                <span>{{ $location ?? '-' }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Amount:</span>
                <span>{{ $amount ?? '-' }} GEL</span>
            </div>
            <div class="info-row">
                <span class="info-label">Ticket ID:</span>
                <span>{{ $ticketId }}</span>
            </div>
        </div>

        <div class="qr-section">
            @if (!empty($qrCode))
                <img src="{{ $qrCode }}" alt="QR Code">
            @endif
        </div>

        <div class="footer">
            <p>Present this ticket at the entrance. Do not share your QR code.</p>
        </div>
    </div>
</body>
</html>
