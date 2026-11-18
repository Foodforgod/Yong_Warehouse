<?php
$page_title = "User Dashboard";
require_once '../includes/auth.php';
require_login();
require_once '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

$total_items = $db->query("SELECT COUNT(*) FROM items")->fetchColumn();
$total_qty = $db->query("SELECT SUM(quantity) FROM items")->fetchColumn() ?: 0;
$low_stock_count = $db->query("SELECT COUNT(*) FROM items WHERE quantity <= reorder_level")->fetchColumn();

$history_stmt = $db->query("SELECT sm.*, i.item_code, i.item_name, u.full_name 
                            FROM stock_movements sm 
                            JOIN items i ON sm.item_id = i.id 
                            JOIN users u ON sm.created_by = u.id 
                            ORDER BY sm.movement_date DESC, sm.id DESC LIMIT 10");
$recent_movements = $history_stmt->fetchAll();
?>

<div class="dashboard-stats">
    <div class="stat-card">
        <h3>Total Items</h3>
        <p class="stat-number"><?php echo $total_items; ?></p>
    </div>
    <div class="stat-card">
        <h3>Total Stock Quantity</h3>
        <p class="stat-number"><?php echo $total_qty; ?></p>
    </div>
    <div class="stat-card warning">
        <h3>Low Stock Items</h3>
        <p class="stat-number"><?php echo $low_stock_count; ?></p>
    </div>
</div>

<div class="panel">
    <h3>Recent Stock History</h3>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Item</th>
                    <th>Type</th>
                    <th>Qty</th>
                    <th>Reference</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($recent_movements) > 0): ?>
                    <?php foreach ($recent_movements as $m): ?>
                        <tr>
                            <td><?php echo $m['movement_date']; ?></td>
                            <td><?php echo htmlspecialchars($m['item_code'] . ' - ' . $m['item_name']); ?></td>
                            <td><span class="badge badge-<?php echo $m['movement_type'] == 'IN' ? 'success' : 'danger'; ?>"><?php echo $m['movement_type']; ?></span></td>
                            <td><?php echo $m['quantity']; ?></td>
                            <td><?php echo htmlspecialchars($m['reference_note']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">No recent stock movements found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>