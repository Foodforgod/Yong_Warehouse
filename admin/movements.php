<?php
$page_title = "Stock Movements";
require_once '../includes/auth.php';
require_admin();
$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Handle Stock IN / OUT Transaction with Database Transactions and SELECT ... FOR UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = intval($_POST['item_id']);
    $movement_type = $_POST['movement_type']; // IN or OUT
    $quantity = intval($_POST['quantity']);
    $movement_date = $_POST['movement_date'];
    $reference_note = trim($_POST['reference_note']);
    $user_id = $_SESSION['user_id'];

    if ($item_id <= 0 || $quantity <= 0 || empty($movement_date) || !in_array($movement_type, ['IN', 'OUT'])) {
        $error = "Please fill in all required fields with valid values.";
    } else {
        try {
            $db->beginTransaction();

            // Lock row for update
            $stmt = $db->prepare("SELECT quantity FROM items WHERE id = ? FOR UPDATE");
            $stmt->execute([$item_id]);
            $item = $stmt->fetch();

            if (!$item) {
                throw new Exception("Item not found.");
            }

            $current_qty = intval($item['quantity']);

            if ($movement_type === 'OUT' && $quantity > $current_qty) {
                throw new Exception("Error: Insufficient stock available. Current stock is {$current_qty}.");
            }

            // Calculate new quantity
            $new_qty = ($movement_type === 'IN') ? ($current_qty + $quantity) : ($current_qty - $quantity);

            // Update item quantity
            $update_stmt = $db->prepare("UPDATE items SET quantity = ? WHERE id = ?");
            $update_stmt->execute([$new_qty, $item_id]);

            // Insert stock movement record
            $ins_stmt = $db->prepare("INSERT INTO stock_movements (item_id, movement_type, quantity, movement_date, reference_note, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $ins_stmt->execute([$item_id, $movement_type, $quantity, $movement_date, $reference_note, $user_id]);

            $db->commit();
            $message = "Stock movement recorded successfully.";
        } catch (Exception $e) {
            $db->rollBack();
            $error = $e->getMessage();
        }
    }
}

// Fetch all items for dropdown
$items = $db->query("SELECT id, item_code, item_name, quantity, unit FROM items ORDER BY item_code ASC")->fetchAll();

// Filters for Stock History Table
$filter_item = intval($_GET['item_id'] ?? 0);
$filter_type = trim($_GET['movement_type'] ?? '');
$filter_start = trim($_GET['start_date'] ?? '');
$filter_end = trim($_GET['end_date'] ?? '');

$sql = "SELECT sm.*, i.item_code, i.item_name, u.full_name 
        FROM stock_movements sm 
        JOIN items i ON sm.item_id = i.id 
        JOIN users u ON sm.created_by = u.id 
        WHERE 1=1";
$params = [];

if ($filter_item > 0) {
    $sql .= " AND sm.item_id = ?";
    $params[] = $filter_item;
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
$history = $stmt->fetchAll();

require_once '../includes/header.php';
?>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<!-- Stock Movement Form Panel -->
<div class="panel">
    <h3>Record Stock Movement</h3>
    <form method="POST" action="movements.php" class="movement-form">
        <div class="form-row">
            <div class="form-group">
                <label>Item *</label>
                <select name="item_id" class="form-control" required>
                    <option value="">-- Select Item --</option>
                    <?php foreach ($items as $i): ?>
                        <option value="<?php echo $i['id']; ?>">
                            <?php echo htmlspecialchars($i['item_code'] . ' - ' . $i['item_name'] . ' (Stock: ' . $i['quantity'] . ' ' . $i['unit'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Movement Type *</label>
                <select name="movement_type" class="form-control" required>
                    <option value="IN">Stock IN</option>
                    <option value="OUT">Stock OUT</option>
                </select>
            </div>
            <div class="form-group">
                <label>Quantity *</label>
                <input type="number" name="quantity" class="form-control" min="1" required>
            </div>
            <div class="form-group">
                <label>Date *</label>
                <input type="date" name="movement_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label>Reference / Note</label>
            <input type="text" name="reference_note" class="form-control" placeholder="PO Number, Supplier, or Reason...">
        </div>
        <button type="submit" class="btn btn-primary">Submit Transaction</button>
    </form>
</div>

<!-- Stock History Panel -->
<div class="panel">
    <h3>Stock History</h3>
    <form method="GET" class="filter-form">
        <select name="item_id" class="form-control">
            <option value="">All Items</option>
            <?php foreach ($items as $i): ?>
                <option value="<?php echo $i['id']; ?>" <?php echo $filter_item == $i['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($i['item_code'] . ' - ' . $i['item_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="movement_type" class="form-control">
            <option value="">All Types</option>
            <option value="IN" <?php echo $filter_type === 'IN' ? 'selected' : ''; ?>>IN</option>
            <option value="OUT" <?php echo $filter_type === 'OUT' ? 'selected' : ''; ?>>OUT</option>
        </select>
        <input type="date" name="start_date" value="<?php echo htmlspecialchars($filter_start); ?>" class="form-control" placeholder="Start Date">
        <input type="date" name="end_date" value="<?php echo htmlspecialchars($filter_end); ?>" class="form-control" placeholder="End Date">
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="movements.php" class="btn btn-secondary">Reset</a>
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Item Code</th>
                    <th>Item Name</th>
                    <th>IN</th>
                    <th>OUT</th>
                    <th>Reference</th>
                    <th>User</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($history) > 0): ?>
                    <?php foreach ($history as $h): ?>
                        <tr>
                            <td><?php echo $h['movement_date']; ?></td>
                            <td><?php echo htmlspecialchars($h['item_code']); ?></td>
                            <td><?php echo htmlspecialchars($h['item_name']); ?></td>
                            <td><?php echo $h['movement_type'] === 'IN' ? $h['quantity'] : '-'; ?></td>
                            <td><?php echo $h['movement_type'] === 'OUT' ? $h['quantity'] : '-'; ?></td>
                            <td><?php echo htmlspecialchars($h['reference_note']); ?></td>
                            <td><?php echo htmlspecialchars($h['full_name']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7">No stock history records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>