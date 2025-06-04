<?php
require '../config.php';
if (!isset($_SESSION['admin'])) die('Access denied');

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="verifications.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Status', 'Result URL', 'Date']);

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
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['id'], $row['first_name'], $row['last_name'], $row['email'],
        $row['phone'], $row['status'], $row['result_url'], $row['created_at']
    ]);
}
fclose($output);
exit;