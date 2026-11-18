<?php
$page_title = "Admin Dashboard";
require_once '../includes/auth.php';
require_admin();
require_once '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Statistics queries
$total_items = $db->query("SELECT COUNT(*) FROM items")->fetchColumn();
$total_qty = $db->query("SELECT SUM(quantity) FROM items")->fetchColumn() ?: 0;
$low_stock_count = $db->query("SELECT COUNT(*) FROM items WHERE quantity <= reorder_level")->fetchColumn();

$today = date('Y-m-d');
$today_in = $db->prepare("SELECT SUM(quantity) FROM stock_movements WHERE movement_type = 'IN' AND movement_date = ?");
$today_in->execute([$today]);
$stock_in_today = $today_in->fetchColumn() ?: 0;

$today_out = $db->prepare("SELECT SUM(quantity) FROM stock_movements WHERE movement_type = 'OUT' AND movement_date = ?");
$today_out->execute([$today]);
$stock_out_today = $today_out->fetchColumn() ?: 0;

// Low stock items list
$low_stock_items = $db->query("SELECT * FROM items WHERE quantity <= reorder_level ORDER BY quantity ASC")->fetchAll();
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
    <div class="stat-card">
        <h3>Today's Stock IN</h3>
        <p class="stat-number"><?php echo $stock_in_today; ?></p>
    </div>
    <div class="stat-card">
        <h3>Today's Stock OUT</h3>
        <p class="stat-number"><?php echo $stock_out_today; ?></p>
    </div>
</div>

<div class="panel">
    <h3>Low Stock Items Alert</h3>
    <?php if (count($low_stock_items) > 0): ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Item Code</th>
                        <th>Item Name</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Reorder Level</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($low_stock_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['item_code']); ?></td>
                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                            <td><span class="badge badge-danger"><?php echo $item['quantity']; ?></span></td>
                            <td><?php echo $item['reorder_level']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-muted">No low-stock items right now.</p>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>