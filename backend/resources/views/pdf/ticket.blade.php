<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 0; }
        body {
            margin: 0;
            padding: 0;
            background: #000;
            color: #fff;
            font-family: 'DejaVu Sans', Arial, sans-serif;
        }
        .page {
            padding: 22px;
        }
        .frame {
            border: 2px solid #f5a623;
            padding: 14px;
        }
        .inner {
            border: 1px solid rgba(245, 166, 35, 0.45);
            padding: 20px 24px;
        }
        .welcome {
            text-align: center;
            padding: 10px 0 4px;
        }
        .welcome .small {
            font-size: 15px;
            letter-spacing: 4px;
            color: #ffffff;
        }
        .welcome .brand {
            font-size: 34px;
            font-weight: bold;
            letter-spacing: 3px;
            color: #f5a623;
            padding-top: 6px;
        }
        .artwork {
            width: 100%;
            text-align: center;
            padding: 16px 0 0;
        }
        .artwork img {
            width: 100%;
        }
        .band {
            margin-top: 18px;
            border-top: 2px solid #f5a623;
            border-bottom: 2px solid #f5a623;
            text-align: center;
            padding: 12px 0;
        }
        .band .title {
            font-size: 26px;
            font-weight: bold;
            letter-spacing: 4px;
            color: #f5a623;
        }
        .event-name {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            color: #ffffff;
            padding: 16px 0 6px;
        }
        .details {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .details td {
            vertical-align: top;
        }
        .rows {
            width: 100%;
            border-collapse: collapse;
        }
        .rows td {
            padding: 9px 0;
            font-size: 14px;
        }
        .rows .label {
            color: #f5a623;
            font-weight: bold;
            width: 150px;
        }
        .rows .value {
            color: #ffffff;
        }
        .qr-cell {
            width: 190px;
            text-align: right;
        }
        .qr-card {
            background: #ffffff;
            border: 2px solid #f5a623;
            padding: 8px;
            width: 170px;
        }
        .qr-card img {
            width: 154px;
            height: 154px;
        }
        .footer {
            margin-top: 22px;
            border-top: 1px solid rgba(245, 166, 35, 0.45);
            text-align: center;
            padding: 12px 0 4px;
            font-size: 11px;
            letter-spacing: 2px;
            color: #cccccc;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="frame">
            <div class="inner">
                <div class="welcome">
                    <div class="small">WELCOME TO</div>
                    <div class="brand">TBILISI STYLE 21</div>
                </div>

                @if (!empty($artworkPath))
                    <div class="artwork">
                        <img src="{{ $artworkPath }}" alt="">
                    </div>
                @endif

                <div class="band">
                    <div class="title">EVENT TICKET</div>
                </div>

                <div class="event-name">{{ $eventName ?? 'Event Ticket' }}</div>

                <table class="details">
                    <tr>
                        <td>
                            <table class="rows">
                                <tr>
                                    <td class="label">Date:</td>
                                    <td class="value">{{ $eventDate ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Name:</td>
                                    <td class="value">{{ $name }} {{ $surname }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Personal Number:</td>
                                    <td class="value">{{ $personalNumber ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Amount Paid:</td>
                                    <td class="value">{{ $amount ?? '-' }} {{ $currency ?? 'GEL' }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Ticket ID:</td>
                                    <td class="value">{{ $ticketId }}</td>
                                </tr>
                            </table>
                        </td>
                        <td class="qr-cell">
                            @if (!empty($qrCode))
                                <div class="qr-card">
                                    <img src="{{ $qrCode }}" alt="QR Code">
                                </div>
                            @endif
                        </td>
                    </tr>
                </table>

                <div class="footer">
                    PLEASE PRESENT THIS TICKET AT THE ENTRANCE
                </div>
            </div>
        </div>
    </div>
</body>
</html>
