<?php
/*
 * Simulated online payment.
 * Card details are validated but NEVER stored. On success the order,
 * its items and the payment record are saved in one transaction and
 * stock is reduced.
 */
require_once 'includes/functions.php';
require_login();

$checkout = $_SESSION['checkout'] ?? null;
$cart = cart_details();
if (!$checkout || !$cart['items'] || is_admin()) {
    redirect('cart.php');
}
$fee = $checkout['method'] === 'delivery' ? DELIVERY_FEE : 0.0;
$total = round($cart['goods'] + $fee, 2);
$errors = [];

function luhn_ok(string $number): bool
{
    if (!preg_match('/^\d{16}$/', $number)) return false;
    $sum = 0;
    for ($i = 0; $i < 16; $i++) {
        $d = (int)$number[15 - $i];
        if ($i % 2 === 1) { $d *= 2; if ($d > 9) $d -= 9; }
        $sum += $d;
    }
    return $sum % 10 === 0;
}

if (is_post()) {
    check_csrf();
    $name = post('card_name');
    $number = preg_replace('/\s+/', '', post('card_number'));
    $expiry = post('expiry');
    $cvv = post('cvv');

    if (mb_strlen($name) < 2) $errors[] = 'Enter the name on the card.';
    if (!luhn_ok($number)) $errors[] = 'Enter a valid 16-digit card number.';
    if (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $expiry, $m)) {
        $errors[] = 'Enter the expiry date as MM/YY.';
    } elseif (mktime(0, 0, 0, (int)$m[1] + 1, 1, 2000 + (int)$m[2]) <= time()) {
        $errors[] = 'This card has expired.';
    }
    if (!preg_match('/^\d{3}$/', $cvv)) $errors[] = 'Enter the 3-digit CVV.';

    if (!$errors) {
        $pdo = db();
        try {
            $pdo->beginTransaction();

            // Lock the product rows and check stock again before buying
            $lock = $pdo->prepare('SELECT stock_quantity FROM products WHERE product_id = ? AND product_status = \'active\' FOR UPDATE');
            foreach ($cart['items'] as $item) {
                $lock->execute([$item['product']['product_id']]);
                $stock = $lock->fetchColumn();
                if ($stock === false || (int)$stock < $item['qty']) {
                    throw new RuntimeException('Sorry, ' . $item['product']['product_name'] . ' no longer has enough stock. Please check your cart.');
                }
            }

            $status = $checkout['method'] === 'delivery' ? 'processing' : 'collection_pending';
            $order = $pdo->prepare('INSERT INTO orders (user_id, order_date, total_amount, fulfilment_method, delivery_address, order_status, delivery_fee)
                                    VALUES (?, NOW(), ?, ?, ?, ?, ?)');
            $order->execute([current_user()['user_id'], $total, $checkout['method'], $checkout['address'], $status, $fee]);
            $orderId = (int)$pdo->lastInsertId();

            $line = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
            $stockUpdate = $pdo->prepare('UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?');
            foreach ($cart['items'] as $item) {
                $line->execute([$orderId, $item['product']['product_id'], $item['qty'], $item['price']]);
                $stockUpdate->execute([$item['qty'], $item['product']['product_id']]);
            }

            $pay = $pdo->prepare("INSERT INTO payments (order_id, payment_method, payment_status) VALUES (?, 'online_payment', 'successful')");
            $pay->execute([$orderId]);

            $pdo->commit();

            unset($_SESSION['cart'], $_SESSION['checkout']);
            $_SESSION['last_order'] = $orderId;
            redirect('confirm.php?id=' . $orderId);
        } catch (RuntimeException $e) {
            $pdo->rollBack();
            flash('error', $e->getMessage());
            redirect('cart.php');
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log('Order failed: ' . $e->getMessage());
            $errors[] = 'Something went wrong while placing your order. You have not been charged. Please try again.';
        }
    }
}

$pageTitle = 'Payment';
$active = 'shop.php';
require 'includes/header.php';
?>
<div class="ctr">
    <h1>Online Payment</h1>
    <p class="lead-text"><?= icon('shield-lock') ?> Secure checkout</p>
    <div class="card">
        <?php foreach ($errors as $e): ?><div class="alert alert-error"><?= h($e) ?></div><?php endforeach; ?>
        <div class="pay-amount">
            <div>
                <span>Amount to pay</span>
                <p class="hint mb-0"><?= $checkout['method'] === 'delivery' ? icon('truck') . ' Home delivery to ' . h($checkout['address']) : icon('shop-window') . ' Collection from our store' ?></p>
            </div>
            <b><?= money($total) ?></b>
        </div>
        <form id="payment-form" method="post" action="payment.php" data-validate autocomplete="off">
            <?= csrf_field() ?>
            <label for="card_name">Name on card</label>
            <input id="card_name" name="card_name" required maxlength="80" autocomplete="off">
            <label for="card_number">Card number</label>
            <input id="card_number" name="card_number" inputmode="numeric" placeholder="1234 5678 9012 3456" data-rule="card" required>
            <div class="f2">
                <div>
                    <label for="expiry">Expiry</label>
                    <input id="expiry" name="expiry" placeholder="MM/YY" inputmode="numeric" data-rule="expiry" required>
                </div>
                <div>
                    <label for="cvv">CVV</label>
                    <input id="cvv" name="cvv" type="password" inputmode="numeric" maxlength="3" placeholder="&bull;&bull;&bull;" data-rule="cvv" required>
                </div>
            </div>
            <button class="btn block lgx" type="submit"><?= icon('lock-fill') ?>Pay <?= money($total) ?></button>
            <a class="btn o block" href="checkout.php"><?= icon('arrow-left') ?>Back to Checkout</a>
        </form>
        <p class="secure"><?= icon('shield-check') ?><span>Simulated payment, no real transaction. Card details are not stored. Test card: 4111 1111 1111 1111, any future expiry, any CVV.</span></p>
    </div>
</div>
<div class="overlay" id="processing" role="status" aria-live="polite">
    <div class="spin"></div>
    <h1>Processing payment&hellip;</h1>
    <p class="mut">Please do not close this page.</p>
</div>
<?php require 'includes/footer.php'; ?>
