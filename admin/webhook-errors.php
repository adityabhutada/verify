<?php require '../config.php'; if (!isset($_SESSION['admin'])) die('Access denied'); ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Webhook Error Log</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
<div class="table-responsive">
    <div class="container p-4 mt-3 bg-white shadow-sm rounded">
    <h3 class="mb-4">Webhook Failures</h3>
<table class="table table-bordered table-striped align-middle">
    <thead class="table-light">
        <tr>
            <th>Timestamp</th>
            <th>Event Time</th>
            <th>Event ID (Plaid ID)</th>
            <th>Status</th>
            <th>Message</th>
            <th>Info</th>
            <th>Details</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $db = new PDO("sqlite:" . DB_PATH);
        $stmt = $db->query("SELECT * FROM webhook_logs ORDER BY received_at DESC");

        foreach ($stmt as $row) {
            $status = strtolower($row['lead_status']);
            $statusBadge = "<span class='badge bg-secondary'>{$row['lead_status']}</span>";
            if ($status === 'failed') $statusBadge = "<span class='badge bg-danger text-uppercase'>{$row['lead_status']}</span>";
            elseif ($status === 'success') $statusBadge = "<span class='badge bg-success text-uppercase'>{$row['lead_status']}</span>";

            $info = json_encode(json_decode($row['info'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $details = json_encode(json_decode($row['details'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            echo "<tr>
                <td>" . htmlspecialchars($row['received_at']) . "</td>
                <td>" . htmlspecialchars($row["event_time"] ?? "") . "</td>
                <td><code>" . htmlspecialchars($row['event_id']) . "</code></td>
                <td>$statusBadge</td>
                <td>" . nl2br(htmlspecialchars($row['message'])) . "</td>
                <td><pre style='white-space: pre-wrap; font-size: 0.85rem;'>" . htmlspecialchars($info) . "</pre></td>
                <td><pre style='white-space: pre-wrap; font-size: 0.85rem;'>" . htmlspecialchars($details) . "</pre></td>
            </tr>";
        }
        ?>
    </tbody>
</table>
</div>
</div>
</body>
</html>
