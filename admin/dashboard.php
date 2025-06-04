<?php require '../config.php'; if (!isset($_SESSION['admin'])) die('Access denied'); ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Verification Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .table-responsive { max-height: 75vh; overflow-y: auto; }
    </style>
</head>
<body class="p-4">
<div class="container bg-white p-4 rounded shadow-sm">
    <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Verification Dashboard</h3>
    <div class="d-flex gap-2">
        <a href="webhook-errors.php" class="btn btn-warning btn-sm">Webhook Failures</a>
        <a href="logout.php" class="btn btn-outline-secondary btn-sm">Logout</a>
    </div>
</div>

    <form class="row g-2 mb-3" method="get">
        <div class="col-md-3">
            <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" class="form-control" placeholder="Search by name or email">
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">All Statuses</option>
                <?php
                    $statuses = ["Pending", "Link Sent", "VERIFIED", "FAILED"];
                    foreach ($statuses as $s) {
                        $selected = ($_GET['status'] ?? '') === $s ? 'selected' : '';
                        echo "<option value='$s' $selected>$s</option>";
                    }
                ?>
            </select>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-primary" type="submit">Filter</button>
            <a href="dashboard.php" class="btn btn-outline-secondary">Reset</a>
        </div>
        <div class="col-md-3 text-end">
            <a href="export.php?<?= http_build_query($_GET) ?>" class="btn btn-success">Export CSV</a>
        </div>
    </form>

    <div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead class="table-light"><tr>
            <th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Plaid ID</th><th>Result</th><th>Date</th>
        </tr></thead>
        <tbody>
        <?php
        $db = new PDO("sqlite:" . DB_PATH);
        $where = [];
        $params = [];

        if (!empty($_GET['search'])) {
            $where[] = "(first_name || ' ' || last_name LIKE :search OR email LIKE :search)";
            $params[':search'] = "%" . $_GET['search'] . "%";
        }
        if (!empty($_GET['status'])) {
            $where[] = "status = :status";
            $params[':status'] = $_GET['status'];
        }
        $sql = "SELECT * FROM verifications";
        if ($where) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        foreach ($stmt as $row) {
            $result = $row['result_url'] ? "<a href='{$row['result_url']}' target='_blank'>View</a>" : "-";
            echo "<tr><td>{$row['id']}</td>
      <td>{$row['first_name']} {$row['last_name']}</td>
      <td>{$row['email']}</td>
      <td>{$row['status']}</td>
      <td>{$row['plaid_id']}</td>
      <td>$result</td>
      <td>{$row['created_at']}</td></tr>";
        }
        ?>
        </tbody>
    </table>
    </div>
</div>
</body>
</html>