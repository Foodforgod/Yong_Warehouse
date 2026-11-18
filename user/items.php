<?php
$page_title = "Warehouse Items";
require_once '../includes/auth.php';
require_login();
$database = new Database();
$db = $database->getConnection();

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
require_once '../includes/header.php';
?>

<div class="panel">
    <h3>Warehouse Inventory</h3>
    <form method="GET" class="filter-form">
        <input type="text" name="search" placeholder="Search code, name, location..." value="<?php echo htmlspecialchars($search); ?>" class="form-control">
        <select name="category" class="form-control">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $cat_filter === $cat['category'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['category']); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
        <a href="items.php" class="btn btn-secondary">Reset</a>
    </form>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Item Code</th>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>Location</th>
                    <th>Quantity</th>
                    <th>Unit</th>
                    <th>Reorder Level</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($items) > 0): ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($item['item_code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['category']); ?></td>
                            <td><?php echo htmlspecialchars($item['location']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><?php echo htmlspecialchars($item['unit']); ?></td>
                            <td><?php echo $item['reorder_level']; ?></td>
                            <td>
                                <a href="item-details.php?id=<?php echo $item['id']; ?>" class="btn-sm btn-info">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8">No items found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>