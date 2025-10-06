<?php

if (!function_exists('receiptPdf')) {
    function receiptPdf($order, array $pickup, array $orderSummary): string
    {
        $itemsHtml = '';
        foreach ($order->items as $item) {
            $title = is_array($item->product->title) ? ($item->product->title['az'] ?? reset($item->product->title)) : $item->product->title;
            $itemsHtml .= "
                <tr>
                    <td>{$title}</td>
                    <td>{$item->quantity}</td>
                    <td>{$item->unit_price}</td>
                    <td>{$item->total_price}</td>
                </tr>
            ";
        }

        return "
<!DOCTYPE html>
<html>
<head>
    <meta charset=\"UTF-8\">
    <title>Order Receipt</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h2 { margin-bottom: 10px; }
        .section { margin-bottom: 20px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td { border: 1px solid #ccc; padding: 6px; text-align: left; }
    </style>
</head>
<body>
    <h2>Order Receipt</h2>

    <div class=\"section\">
        <h3>Order Summary</h3>
        <p><strong>Order ID:</strong> {$orderSummary['order_id']}</p>
        <p><strong>Transaction ID:</strong> {$orderSummary['transaction_id']}</p>
        <p><strong>Order Time:</strong> {$orderSummary['order_time']}</p>
        <p><strong>Items Total:</strong> {$orderSummary['items_totals']}</p>
        <p><strong>Discounts:</strong> {$orderSummary['items_discounts']}</p>
        <p><strong>Shipping:</strong> {$orderSummary['shipping']}</p>
        <p><strong>Total:</strong> {$orderSummary['total']}</p>
    </div>

    <div class=\"section\">
        <h3>Pickup</h3>
        <p><strong>City:</strong> {$pickup['city']}</p>
        <p><strong>Town:</strong> {$pickup['town']}</p>
        <p><strong>Street:</strong> {$pickup['street']}</p>
        <p><strong>Apartment:</strong> {$pickup['apartment']}</p>
        <p><strong>Phone:</strong> {$pickup['phone']}</p>
    </div>

    <div class=\"section\">
        <h3>Items</h3>
        <table class=\"table\">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Unit price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                {$itemsHtml}
            </tbody>
        </table>
    </div>

</body>
</html>
        ";
    }
}
