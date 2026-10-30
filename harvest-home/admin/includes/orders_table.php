<?php
function render_admin_orders(array $orders): void
{
    if (!$orders) {
        echo '<div class="card empty">' . icon('receipt') . '<p>No orders found.</p></div>';
        return;
    }
    echo '<div class="tw"><table><thead><tr><th>Order #</th><th>Date</th><th>Customer</th><th>Type</th><th>Total</th><th>Status</th><th>Action</th></tr></thead><tbody>';
    foreach ($orders as $o) {
        $delivery = $o['fulfilment_method'] === 'delivery';
        echo '<tr><td><b>#' . order_no($o['order_id']) . '</b></td>'
           . '<td>' . date('j M Y', strtotime($o['order_date'])) . '</td>'
           . '<td>' . h($o['full_name']) . '</td>'
           . '<td>' . icon($delivery ? 'truck' : 'shop-window') . ' ' . ($delivery ? 'Delivery' : 'Collection') . '</td>'
           . '<td>' . money($o['total_amount']) . '</td>'
           . '<td>' . status_pill($o['order_status']) . '</td>'
           . '<td><a class="act" href="order.php?id=' . (int)$o['order_id'] . '">' . icon('eye') . 'View</a></td></tr>';
    }
    echo '</tbody></table></div>';
}
