<?php
$page_title = "Item Details";
require_once '../includes/auth.php';
require_login();
$database = new Database();
$db = $database->getConnection();

$id = intval($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT * FROM items WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    redirect('/user/items.php');
}

$m_stmt = $db->prepare("SELECT sm.*, u.full_name FROM stock_movements sm JOIN users u ON sm.created_by = u.id WHERE sm.item_id = ? ORDER BY sm.movement_date DESC, sm.id DESC");
$m_stmt->execute([$id]);
$movements = $m_stmt->fetchAll();

require_once '../includes/header.php';
?>

<div class="panel">
    <div class="panel-header-flex">
        <h3>Item Details: <?php echo htmlspecialchars($item['item_code']); ?></h3>
        <a href="items.php" class="btn btn-secondary">Back to List</a>
    </div>

    <div class="details-grid">
        <p><strong>Item Code:</strong> <?php echo htmlspecialchars($item['item_code']); ?></p>
        <p><strong>Item Name:</strong> <?php echo htmlspecialchars($item['item_name']); ?></p>
        <p><strong>Category:</strong> <?php echo htmlspecialchars($item['category']); ?></p>
        <p><strong>Location:</strong> <?php echo htmlspecialchars($item['location']); ?></p>
        <p><strong>Quantity:</strong> <?php echo htmlspecialchars($item['quantity'] . ' ' . $item['unit']); ?></p>
        <p><strong>Reorder Level:</strong> <?php echo htmlspecialchars($item['reorder_level']); ?></p>
        <p><strong>Created Date:</strong> <?php echo htmlspecialchars($item['created_at']); ?></p>
        <p><strong>Updated Date:</strong> <?php echo htmlspecialchars($item['updated_at']); ?></p>
    </div>

    <h4 style="margin-top: 30px;">Stock History</h4>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>IN</th>
                    <th>OUT</th>
                    <th>Reference</th>
                    <th>User</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($movements) > 0): ?>
                    <?php foreach ($movements as $m): ?>
                        <tr>
                            <td><?php echo $m['movement_date']; ?></td>
                            <td><?php echo $m['movement_type'] === 'IN' ? $m['quantity'] : '-'; ?></td>
                            <td><?php echo $m['movement_type'] === 'OUT' ? $m['quantity'] : '-'; ?></td>
                            <td><?php echo htmlspecialchars($m['reference_note']); ?></td>
                            <td><?php echo htmlspecialchars($m['full_name']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">No stock movement history available.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>