<?php
/*
 * Shared helpers for the Harvest & Home site:
 * sessions, security (CSRF, escaping, access control), formatting,
 * product queries with promotion prices, and the session cart.
 */
require_once __DIR__ . '/../config/db.php';

// ---------- Session ----------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('HHSESSID');
    session_start();
}

// ---------- Output and requests ----------
function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function post(string $key, string $default = ''): string
{
    return isset($_POST[$key]) ? trim((string)$_POST[$key]) : $default;
}

// ---------- Flash messages ----------
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function show_flash(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $out = '';
    foreach ($_SESSION['flash'] as $f) {
        $out .= '<div class="alert alert-' . h($f['type']) . '" role="alert">' . h($f['message']) . '</div>';
    }
    unset($_SESSION['flash']);
    return $out;
}

// ---------- CSRF protection ----------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function check_csrf(): void
{
    $sent = $_POST['csrf'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(400);
        exit('Your session has expired. Please go back and try again.');
    }
}

// ---------- Authentication and access control ----------
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']);
}

function is_admin(): bool
{
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
}

function login_user(array $user): void
{
    session_regenerate_id(true); // stop session fixation
    $_SESSION['user'] = [
        'user_id'   => (int)$user['user_id'],
        'full_name' => $user['full_name'],
        'email'     => $user['email'],
        'role'      => $user['role'],
    ];
}

function logout_user(): void
{
    $cart = $_SESSION['cart'] ?? [];
    $_SESSION = [];
    session_regenerate_id(true);
    $_SESSION['cart'] = $cart; // keep the cart, like most shops do
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('info', 'Please log in to continue.');
        redirect('login.php?next=' . urlencode(basename($_SERVER['PHP_SELF'])));
    }
}

// The main admin is the original administrator account (lowest user ID with the admin role).
// Only the main admin may change other administrator accounts.
function main_admin_id(): int
{
    return (int)db()->query("SELECT MIN(user_id) FROM users WHERE role = 'admin'")->fetchColumn();
}

function is_main_admin(): bool
{
    return is_admin() && (int)current_user()['user_id'] === main_admin_id();
}

function require_admin(): void
{
    if (!is_admin()) {
        redirect('login.php');
    }
    // Re-check the role in the database, so a demoted admin loses access straight away
    $stmt = db()->prepare('SELECT role FROM users WHERE user_id = ?');
    $stmt->execute([current_user()['user_id']]);
    $role = $stmt->fetchColumn();
    if ($role !== 'admin') {
        $_SESSION['user']['role'] = $role ?: 'customer';
        flash('error', 'You no longer have administrator access.');
        redirect('login.php');
    }
}

// ---------- Page assets and icons ----------
/*
 * Stylesheets for every page: Google Fonts, Bootstrap 5.3, Bootstrap Icons
 * and the site theme. $root is '' for the shop and '../' for the admin site.
 * The integrity hashes make the browser reject a CDN file that was changed.
 */
function head_assets(string $root = ''): string
{
    $css = __DIR__ . '/../assets/css/style.css';
    $version = is_file($css) ? filemtime($css) : 1;
    return '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n"
        . '    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n"
        . '    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,600;9..144,700&display=swap">' . "\n"
        . '    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">' . "\n"
        . '    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">' . "\n"
        . '    <link rel="stylesheet" href="' . $root . 'assets/css/style.css?v=' . $version . '">' . "\n";
}

// Bootstrap Icons tag, hidden from screen readers because the text beside it says the same
function icon(string $name): string
{
    return '<i class="bi bi-' . $name . '" aria-hidden="true"></i>';
}

function category_icon(string $category): string
{
    $icons = [
        'Fresh Produce'      => 'flower1',
        'Harvest Boxes'      => 'box2-heart',
        'Homemade Bakery'    => 'cake2',
        'Pantry & Preserves' => 'cup-hot',
        'Seasonal Specials'  => 'stars',
    ];
    return $icons[$category] ?? 'basket2';
}

// ---------- Formatting ----------
function money($amount): string
{
    return 'R' . number_format((float)$amount, 2, '.', ' ');
}

function order_no($id): string
{
    return 'HH' . str_pad((string)$id, 6, '0', STR_PAD_LEFT);
}

function status_label(string $status, string $method = ''): string
{
    $labels = [
        'processing'         => 'Processing – Delivery',
        'delivered'          => 'Delivered – Delivery',
        'collection_pending' => 'Collection Pending',
        'collected'          => 'Collected',
        'cancelled'          => 'Cancelled',
    ];
    return $labels[$status] ?? ucfirst($status);
}

function status_pill(string $status): string
{
    $classes = [
        'processing' => 'p1', 'delivered' => 'p2', 'collection_pending' => 'p3',
        'collected'  => 'p4', 'cancelled' => 'p5',
    ];
    return '<span class="pill ' . ($classes[$status] ?? '') . '">' . h(status_label($status)) . '</span>';
}

// Statuses an admin may choose for an order, based on how it is fulfilled
function allowed_statuses(string $method): array
{
    return $method === 'delivery'
        ? ['processing', 'delivered', 'cancelled']
        : ['collection_pending', 'collected', 'cancelled'];
}

// ---------- Products ----------
/*
 * Base product query. Joins the category name and the lowest active
 * promotion price (if any) so every page shows the same price.
 */
function product_sql(): string
{
    return "SELECT p.*, c.category_name, pr.promo_price
            FROM products p
            JOIN categories c ON c.category_id = p.category_id
            LEFT JOIN (
                SELECT pi.product_id, MIN(pi.promo_price) AS promo_price
                FROM promotion_items pi
                JOIN promotions pm ON pm.promotion_id = pi.promotion_id
                WHERE pm.promotion_status = 'active'
                GROUP BY pi.product_id
            ) pr ON pr.product_id = p.product_id";
}

// Price the customer actually pays right now
function sell_price(array $p): float
{
    if ($p['promo_price'] !== null && (float)$p['promo_price'] < (float)$p['price']) {
        return (float)$p['promo_price'];
    }
    return (float)$p['price'];
}

function on_special(array $p): bool
{
    return sell_price($p) < (float)$p['price'];
}

function get_product(int $id, bool $activeOnly = true): ?array
{
    $sql = product_sql() . ' WHERE p.product_id = ?' . ($activeOnly ? " AND p.product_status = 'active'" : '');
    $stmt = db()->prepare($sql);
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_categories(): array
{
    return db()->query('SELECT * FROM categories ORDER BY category_id')->fetchAll();
}

function product_image(array $p, string $root = ''): string
{
    if (!empty($p['product_image'])) {
        return '<img src="' . h($root . $p['product_image']) . '" alt="' . h($p['product_name']) . '" loading="lazy">';
    }
    return '<span class="ph-empty">' . icon(category_icon($p['category_name'])) . '</span>';
}

// ---------- Session cart ( product_id => quantity ) ----------
function cart(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_count(): int
{
    return array_sum(cart());
}

/*
 * Loads the cart products from the database and works out totals.
 * Quantities are capped at the stock that is available.
 */
function cart_details(): array
{
    $items = [];
    $subtotal = 0.0;   // at normal prices
    $discount = 0.0;   // saved through promotions
    $ids = array_keys(cart());
    if ($ids) {
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $stmt = db()->prepare(product_sql() . " WHERE p.product_id IN ($marks) AND p.product_status = 'active'");
        $stmt->execute($ids);
        foreach ($stmt->fetchAll() as $p) {
            $qty = min((int)$_SESSION['cart'][$p['product_id']], (int)$p['stock_quantity']);
            if ($qty < 1) {
                unset($_SESSION['cart'][$p['product_id']]);
                continue;
            }
            $_SESSION['cart'][$p['product_id']] = $qty;
            $price = sell_price($p);
            $items[] = ['product' => $p, 'qty' => $qty, 'price' => $price, 'line' => $price * $qty];
            $subtotal += (float)$p['price'] * $qty;
            $discount += ((float)$p['price'] - $price) * $qty;
        }
        // drop products that no longer exist or were deactivated
        $found = array_map(fn($i) => (int)$i['product']['product_id'], $items);
        foreach ($ids as $id) {
            if (!in_array((int)$id, $found, true)) {
                unset($_SESSION['cart'][$id]);
            }
        }
    }
    return ['items' => $items, 'subtotal' => $subtotal, 'discount' => $discount, 'goods' => $subtotal - $discount];
}

// ---------- Product card used on the home and shop pages ----------
function product_card(array $p): string
{
    $id = (int)$p['product_id'];
    $html  = '<article class="card prod">';
    $html .= '<a class="ph" href="product.php?id=' . $id . '" tabindex="-1" aria-hidden="true">' . product_image($p) . '</a>';
    if (on_special($p)) {
        $html .= '<span class="tag">' . icon('stars') . 'Special</span>';
    }
    $html .= '<span class="kind">' . h($p['category_name']) . '</span>';
    $html .= '<h3><a href="product.php?id=' . $id . '">' . h($p['product_name']) . '</a></h3>';
    $html .= '<p class="price">' . (on_special($p) ? '<span class="was">' . money($p['price']) . '</span>' : '') . money(sell_price($p)) . '</p>';
    if ((int)$p['stock_quantity'] < 1) {
        $html .= '<p class="stock-low">' . icon('x-circle') . 'Out of stock</p>';
    } else {
        if ((int)$p['stock_quantity'] <= 5) {
            $html .= '<p class="stock-low">' . icon('exclamation-circle') . 'Only ' . (int)$p['stock_quantity'] . ' left</p>';
        }
        $html .= '<form method="post" action="cart.php">' . csrf_field()
               . '<input type="hidden" name="action" value="add"><input type="hidden" name="product_id" value="' . $id . '">'
               . '<button class="btn sm" type="submit">' . icon('bag-plus') . 'Add to Cart</button></form>';
    }
    return $html . '</article>';
}

// ---------- My Account side menu ----------
function account_menu(string $current): string
{
    $links = [
        'account.php' => ['Account Overview', 'grid'],
        'details.php' => ['Personal Details', 'person'],
        'address.php' => ['Address Book', 'geo-alt'],
        'orders.php'  => ['Order History', 'receipt'],
        'logout.php'  => ['Logout', 'box-arrow-right'],
    ];
    $html = '<nav class="card side" aria-label="My account">';
    foreach ($links as $url => [$label, $ic]) {
        $html .= '<a href="' . $url . '" class="' . ($current === $url ? 'on' : '') . '">' . icon($ic) . $label . '</a>';
    }
    return $html . '</nav>';
}

// Orders table shared by the account overview and the order history
function orders_table(array $orders, string $viewUrl = 'order.php'): string
{
    if (!$orders) {
        return '<div class="card empty">' . icon('receipt') . '<p>You have not placed any orders yet.</p>'
             . '<a class="btn" href="shop.php">' . icon('basket2') . 'Start Shopping</a></div>';
    }
    $html = '<div class="tw"><table><thead><tr><th>Order #</th><th>Date</th><th>Total</th><th>Status</th><th>Action</th></tr></thead><tbody>';
    foreach ($orders as $o) {
        $html .= '<tr><td><b>#' . order_no($o['order_id']) . '</b></td>'
               . '<td>' . date('j M Y', strtotime($o['order_date'])) . '</td>'
               . '<td>' . money($o['total_amount']) . '</td>'
               . '<td>' . status_pill($o['order_status']) . '</td>'
               . '<td><a class="act" href="' . $viewUrl . '?id=' . (int)$o['order_id'] . '">' . icon('eye') . 'View</a></td></tr>';
    }
    return $html . '</tbody></table></div>';
}
