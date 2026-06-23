<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px;">
        <h1 style="color: #f5a623;">TbilisiStyle21</h1>
        <p>Hello {{ $name }},</p>
        <p>Your order has been confirmed!</p>
        <p><strong>Product:</strong> {{ $productTitle }}</p>
        <p><strong>Size:</strong> {{ $size }}</p>
        <p><strong>Order ID:</strong> {{ $orderId }}</p>
        <hr style="border: 1px solid #eee;">
        <p style="color: #888; font-size: 12px;">Thank you for shopping with TbilisiStyle21.</p>
    </div>
</body>
</html>
