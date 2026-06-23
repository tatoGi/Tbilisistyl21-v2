<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px;">
        <h1 style="color: #f5a623;">TbilisiStyle21</h1>
        <p>Hello {{ $name }},</p>
        <p>Thank you for your purchase! Your ticket is attached as a PDF.</p>
        @if($eventName)
            <p><strong>Event:</strong> {{ $eventName }}</p>
        @endif
        <p><strong>Ticket ID:</strong> {{ $ticketId }}</p>
        <hr style="border: 1px solid #eee;">
        <p style="color: #888; font-size: 12px;">Please present your ticket at the entrance. Do not share your QR code with anyone.</p>
    </div>
</body>
</html>
