<?php
$page_title = "Stock Movement Reports";
require_once '../includes/auth.php';
require_admin();
$database = new Database();
$db = $database->getConnection();

$filter_item = intval($_GET['item_id'] ?? 0);
$filter_cat = trim($_GET['category'] ?? '');
$filter_type = trim($_GET['movement_type'] ?? '');
$filter_start = trim($_GET['start_date'] ?? '');
$filter_end = trim($_GET['end_date'] ?? '');

$sql = "SELECT sm.*, i.item_code, i.item_name, i.category, u.full_name 
        FROM stock_movements sm 
        JOIN items i ON sm.item_id = i.id 
        JOIN users u ON sm.created_by = u.id 
        WHERE 1=1";
$params = [];

if ($filter_item > 0) {
    $sql .= " AND sm.item_id = ?";
    $params[] = $filter_item;
}
if (!empty($filter_cat)) {
    $sql .= " AND i.category = ?";
    $params[] = $filter_cat;
}
if (!empty($filter_type) && in_array($filter_type, ['IN', 'OUT'])) {
    $sql .= " AND sm.movement_type = ?";
    $params[] = $filter_type;
}
if (!empty($filter_start)) {
    $sql .= " AND sm.movement_date >= ?";
    $params[] = $filter_start;
}
if (!empty($filter_end)) {
    $sql .= " AND sm.movement_date <= ?";
    $params[] = $filter_end;
}

$sql .= " ORDER BY sm.movement_date DESC, sm.id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$report_data = $stmt->fetchAll();

// Calculate Totals
$total_in = 0;
$total_out = 0;
foreach ($report_data as $row) {
    if ($row['movement_type'] === 'IN') {
        $total_in += $row['quantity'];
    } else {
        $total_out += $row['quantity'];
    }
}
$net_movement = $total_in - $total_out;

$items = $db->query("SELECT id, item_code, item_name FROM items ORDER BY item_code ASC")->fetchAll();
$categories = get_categories($db);

require_once '../includes/header.php';
?>

<div class="no-print">
    <div class="panel">
        <h3>Stock Movement Report Filters</h3>
        <form method="GET" class="filter-form">
            <select name="item_id" class="form-control">
                <option value="">All Items</option>
                <?php foreach ($items as $i): ?>
                    <option value="<?php echo $i['id']; ?>" <?php echo $filter_item == $i['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($i['item_code'] . ' - ' . $i['item_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="category" class="form-control">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $filter_cat === $cat['category'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['category']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="movement_type" class="form-control">
                <option value="">All Types</option>
                <option value="IN" <?php echo $filter_type === 'IN' ? 'selected' : ''; ?>>IN</option>
                <option value="OUT" <?php echo $filter_type === 'OUT' ? 'selected' : ''; ?>>OUT</option>
            </select>
            <input type="date" name="start_date" value="<?php echo htmlspecialchars($filter_start); ?>" class="form-control">
            <input type="date" name="end_date" value="<?php echo htmlspecialchars($filter_end); ?>" class="form-control">
            <button type="submit" class="btn btn-primary">Generate Report</button>
            <button type="button" onclick="window.print()" class="btn btn-success">Print Report</button>
        </form>
    </div>
</div>

<div class="panel">
    <div class="report-header-print">
        <h2>Stock Movement Report</h2>
        <p>Generated on: <?php echo date('Y-m-d H:i:s'); ?></p>
    </div>

    <div class="dashboard-stats">
        <div class="stat-card">
            <h3>Total Stock IN</h3>
            <p class="stat-number text-success"><?php echo $total_in; ?></p>
        </div>
        <div class="stat-card">
            <h3>Total Stock OUT</h3>
            <p class="stat-number text-danger"><?php echo $total_out; ?></p>
        </div>
        <div class="stat-card">
            <h3>Net Movement</h3>
            <p class="stat-number"><?php echo $net_movement; ?></p>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Item Code</th>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>IN</th>
                    <th>OUT</th>
                    <th>Reference</th>
                    <th>User</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($report_data) > 0): ?>
                    <?php foreach ($report_data as $row): ?>
                        <tr>
                            <td><?php echo $row['movement_date']; ?></td>
                            <td><?php echo htmlspecialchars($row['item_code']); ?></td>
                            <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['category']); ?></td>
                            <td><?php echo $row['movement_type'] === 'IN' ? $row['quantity'] : '-'; ?></td>
                            <td><?php echo $row['movement_type'] === 'OUT' ? $row['quantity'] : '-'; ?></td>
                            <td><?php echo htmlspecialchars($row['reference_note']); ?></td>
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8">No records found matching criteria.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>