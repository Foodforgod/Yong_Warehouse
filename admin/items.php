<?php
$page_title = "Item Management";
require_once '../includes/auth.php'; // This includes init.php and database.php automatically
require_admin();

$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Handle Add / Edit Item
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $item_code = strtoupper(trim($_POST['item_code']));
    $item_name = trim($_POST['item_name']);
    $category = trim($_POST['category']);
    $location = trim($_POST['location']);
    $quantity = intval($_POST['quantity']);
    $unit = trim($_POST['unit']);
    $reorder_level = intval($_POST['reorder_level']);

    if (empty($item_code) || empty($item_name) || empty($category) || empty($unit)) {
        $error = "All required fields must be filled.";
    } elseif ($quantity < 0 || $reorder_level < 0) {
        $error = "Quantity and Reorder Level cannot be negative.";
    } else {
        if ($action === 'add') {
            try {
                $stmt = $db->prepare("INSERT INTO items (item_code, item_name, category, location, quantity, unit, reorder_level) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$item_code, $item_name, $category, $location, $quantity, $unit, $reorder_level]);
                $message = "Item added successfully.";
            } catch (PDOException $e) {
                $error = "Error: Item code already exists.";
            }
        } elseif ($action === 'edit') {
            $id = intval($_POST['id']);
            try {
                $stmt = $db->prepare("UPDATE items SET item_code = ?, item_name = ?, category = ?, location = ?, quantity = ?, unit = ?, reorder_level = ? WHERE id = ?");
                $stmt->execute([$item_code, $item_name, $category, $location, $quantity, $unit, $reorder_level, $id]);
                $message = "Item updated successfully.";
            } catch (PDOException $e) {
                $error = "Error: Item code conflict.";
            }
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    try {
        $stmt = $db->prepare("DELETE FROM items WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Item deleted successfully.";
    } catch (PDOException $e) {
        $error = "Cannot delete item as it is already associated with stock movement records.";
    }
}

// Search & Filter
$search = trim($_GET['search'] ?? '');
$cat_filter = trim($_GET['category'] ?? '');

$query = "SELECT * FROM items WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (item_code LIKE ? OR item_name LIKE ? OR location LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($cat_filter)) {
    $query .= " AND category = ?";
    $params[] = $cat_filter;
}

$query .= " ORDER BY id DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$items = $stmt->fetchAll();

$categories = get_categories($db);

// View Item Details Mode
$view_item = null;
$item_movements = [];
if (isset($_GET['view'])) {
    $view_id = intval($_GET['view']);
    $v_stmt = $db->prepare("SELECT * FROM items WHERE id = ?");
    $v_stmt->execute([$view_id]);
    $view_item = $v_stmt->fetch();

    if ($view_item) {
        $m_stmt = $db->prepare("SELECT sm.*, u.full_name FROM stock_movements sm JOIN users u ON sm.created_by = u.id WHERE sm.item_id = ? ORDER BY sm.movement_date DESC, sm.id DESC LIMIT 20");
        $m_stmt->execute([$view_id]);
        $item_movements = $m_stmt->fetchAll();
    }
}

require_once '../includes/header.php';
?>

<?php if (!empty($message)): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php if (isset($_GET['view']) && $view_item): ?>
    <div class="panel">
        <div class="panel-header-flex">
            <h3>Item Details: <?php echo htmlspecialchars($view_item['item_code']); ?></h3>
            <a href="items.php" class="btn btn-secondary">Back to List</a>
        </div>
        <div class="details-grid">
            <p><strong>Item Name:</strong> <?php echo htmlspecialchars($view_item['item_name']); ?></p>
            <p><strong>Category:</strong> <?php echo htmlspecialchars($view_item['category']); ?></p>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($view_item['location']); ?></p>
            <p><strong>Quantity:</strong> <?php echo htmlspecialchars($view_item['quantity']) . ' ' . htmlspecialchars($view_item['unit']); ?></p>
            <p><strong>Reorder Level:</strong> <?php echo htmlspecialchars($view_item['reorder_level']); ?></p>
            <p><strong>Last Updated:</strong> <?php echo htmlspecialchars($view_item['updated_at']); ?></p>
        </div>

        <h4 style="margin-top: 25px;">Latest 20 Stock Movements</h4>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Reference</th>
                        <th>User</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($item_movements) > 0): ?>
                        <?php foreach ($item_movements as $m): ?>
                            <tr>
                                <td><?php echo $m['movement_date']; ?></td>
                                <td><span class="badge badge-<?php echo $m['movement_type'] == 'IN' ? 'success' : 'danger'; ?>"><?php echo $m['movement_type']; ?></span></td>
                                <td><?php echo $m['quantity']; ?></td>
                                <td><?php echo htmlspecialchars($m['reference_note']); ?></td>
                                <td><?php echo htmlspecialchars($m['full_name']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5">No movement records found for this item.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>

<div class="panel">
    <h3>Item Management</h3>
    
    <!-- Search & Filter Form -->
    <form method="GET" class="filter-form">
        <input type="text" name="search" placeholder="Search code, name, location..." value="<?php echo htmlspecialchars($search); ?>" class="form-control">
        <select name="category" class="form-control">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $cat_filter === $cat['category'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['category']); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="items.php" class="btn btn-secondary">Reset</a>
        <button type="button" class="btn btn-success" onclick="openItemModal('add')">Add New Item</button>
    </form>

    <!-- Items Table -->
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Location</th>
                    <th>Qty</th>
                    <th>Unit</th>
                    <th>Reorder</th>
                    <th>Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($items) > 0): ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo $item['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($item['item_code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                            <td><?php echo htmlspecialchars($item['location']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><?php echo htmlspecialchars($item['unit']); ?></td>
                            <td><?php echo $item['reorder_level']; ?></td>
                            <td><?php echo $item['updated_at']; ?></td>
                            <td>
                                <a href="items.php?view=<?php echo $item['id']; ?>" class="btn-sm btn-info">View</a>
                                <button class="btn-sm btn-warning" onclick='openItemModal("edit", <?php echo json_encode($item); ?>)'>Edit</button>
                                <a href="items.php?delete=<?php echo $item['id']; ?>" class="btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this item?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="10">No items found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Form for Add/Edit Item -->
<div id="itemModal" class="modal" style="display:none;">
    <div class="modal-content">
        <span class="close" onclick="closeItemModal()">&times;</span>
        <h3 id="modalTitle">Add New Item</h3>
        <form method="POST" action="items.php">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="itemId">
            
            <div class="form-group">
                <label>Item Code *</label>
                <input type="text" name="item_code" id="itemCode" class="form-control" required style="text-transform: uppercase;">
            </div>
            <div class="form-group">
                <label>Item Name *</label>
                <input type="text" name="item_name" id="itemName" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Category *</label>
                <input type="text" name="category" id="itemCategory" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" id="itemLocation" class="form-control">
            </div>
            <div class="form-group">
                <label>Quantity *</label>
                <input type="number" name="quantity" id="itemQuantity" class="form-control" min="0" required value="0">
            </div>
            <div class="form-group">
                <label>Unit *</label>
                <input type="text" name="unit" id="itemUnit" class="form-control" required placeholder="pcs, boxes, kg">
            </div>
            <div class="form-group">
                <label>Reorder Level *</label>
                <input type="number" name="reorder_level" id="itemReorder" class="form-control" min="0" required value="10">
            </div>
            <button type="submit" class="btn btn-primary">Save Item</button>
        </form>
    </div>
</div>

<script>
function openItemModal(mode, data = null) {
    document.getElementById('itemModal').style.display = 'block';
    if (mode === 'add') {
        document.getElementById('modalTitle').innerText = 'Add New Item';
        document.getElementById('formAction').value = 'add';
        document.getElementById('itemId').value = '';
        document.getElementById('itemCode').value = '';
        document.getElementById('itemName').value = '';
        document.getElementById('itemCategory').value = '';
        document.getElementById('itemLocation').value = '';
        document.getElementById('itemQuantity').value = '0';
        document.getElementById('itemUnit').value = '';
        document.getElementById('itemReorder').value = '10';
    } else if (mode === 'edit' && data) {
        document.getElementById('modalTitle').innerText = 'Edit Item';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('itemId').value = data.id;
        document.getElementById('itemCode').value = data.item_code;
        document.getElementById('itemName').value = data.item_name;
        document.getElementById('itemCategory').value = data.category;
        document.getElementById('itemLocation').value = data.location;
        document.getElementById('itemQuantity').value = data.quantity;
        document.getElementById('itemUnit').value = data.unit;
        document.getElementById('itemReorder').value = data.reorder_level;
    }
}
function closeItemModal() {
    document.getElementById('itemModal').style.display = 'none';
}
</script>

<?php endif; ?>
<?php require_once '../includes/footer.php'; ?>