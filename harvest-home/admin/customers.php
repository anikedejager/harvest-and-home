<?php
/* User management (RBAC): lists every account with its role. Admins edit users and roles in user_form.php. */
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$q = trim($_GET['q'] ?? '');
$role = in_array($_GET['role'] ?? '', ['customer', 'admin'], true) ? $_GET['role'] : '';
$sql = "SELECT u.user_id, u.full_name, u.email, u.phone, u.role, u.created_at,
               COUNT(o.order_id) AS orders,
               COALESCE(SUM(CASE WHEN o.order_status <> 'cancelled' THEN o.total_amount END), 0) AS spent
        FROM users u LEFT JOIN orders o ON o.user_id = u.user_id
        WHERE 1 = 1";
$params = [];
if ($role) {
    $sql .= ' AND u.role = ?';
    $params[] = $role;
}
if ($q !== '') {
    $sql .= ' AND (u.full_name LIKE ? OR u.email LIKE ?)';
    array_push($params, '%' . $q . '%', '%' . $q . '%');
}
$sql .= ' GROUP BY u.user_id ORDER BY u.role, u.full_name';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
$mainId = main_admin_id();
$canEditAdmins = is_main_admin();
$me = (int)current_user()['user_id'];

// Keeps the search text when switching the role filter
function users_link(string $role): string
{
    return 'customers.php' . (($query = http_build_query(array_filter(['role' => $role, 'q' => $_GET['q'] ?? '']))) ? '?' . $query : '');
}

$pageTitle = 'Users';
$adminActive = 'customers.php';
require 'includes/header.php';
?>
<div class="toolbar">
    <div>
        <h1>Users</h1>
        <p class="mut mb-0"><?= count($users) ?> user<?= count($users) === 1 ? '' : 's' ?>. Roles control who can open the admin website.</p>
    </div>
    <a class="btn" href="user_form.php"><?= icon('person-plus') ?>Add User</a>
</div>
<div class="chips">
    <a class="chip <?= !$role ? 'on' : '' ?>" href="<?= h(users_link('')) ?>"><?= icon('people') ?>All</a>
    <a class="chip <?= $role === 'customer' ? 'on' : '' ?>" href="<?= h(users_link('customer')) ?>"><?= icon('person') ?>Customers</a>
    <a class="chip <?= $role === 'admin' ? 'on' : '' ?>" href="<?= h(users_link('admin')) ?>"><?= icon('shield-lock') ?>Admins</a>
</div>
<form class="search" method="get" action="customers.php" role="search">
    <?php if ($role): ?><input type="hidden" name="role" value="<?= h($role) ?>"><?php endif; ?>
    <div class="field"><?= icon('search') ?><input type="search" name="q" value="<?= h($q) ?>" placeholder="Search by name or email" aria-label="Search users"></div>
    <button class="btn" type="submit"><?= icon('search') ?>Search</button>
</form>
<div class="tw">
    <table class="users-table">
        <thead><tr><th>User</th><th>Phone</th><th>Role</th><th class="hide-md">Joined</th><th>Orders</th><th>Spent</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): $admin = $u['role'] === 'admin'; ?>
            <tr>
                <td class="wrap">
                    <span class="prod-cell">
                        <span class="mini"><?= icon($admin ? 'shield-lock' : 'person') ?></span>
                        <span><?= h($u['full_name']) ?><br><a class="user-email" href="mailto:<?= h($u['email']) ?>"><?= h($u['email']) ?></a></span>
                    </span>
                </td>
                <td><?= h($u['phone']) ?></td>
                <td><span class="pill <?= $admin ? 'p4' : 'p2' ?>"><?= (int)$u['user_id'] === $mainId ? 'Main admin' : ($admin ? 'Admin' : 'Customer') ?></span></td>
                <td class="hide-md"><?= date('j M Y', strtotime($u['created_at'])) ?></td>
                <td><?= (int)$u['orders'] ?></td>
                <td><?= money($u['spent']) ?></td>
                <td class="actions">
                    <?php if ($admin && (int)$u['user_id'] !== $me && !$canEditAdmins): ?>
                        <span class="act mut" title="Only the main administrator can change administrator accounts"><?= icon('lock') ?>Protected</span>
                    <?php else: ?>
                        <a class="act" href="user_form.php?id=<?= (int)$u['user_id'] ?>"><?= icon('pencil-square') ?>Edit</a>
                    <?php endif; ?>
                    <?php if ($u['orders']): ?><a class="act" href="orders.php?customer=<?= (int)$u['user_id'] ?>"><?= icon('receipt') ?>Orders</a><?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$users): ?><tr><td colspan="7" class="mut">No users found.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
<?php require 'includes/footer.php'; ?>
