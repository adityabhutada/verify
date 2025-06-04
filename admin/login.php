<?php require '../config.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
<form class="card p-4 shadow-sm bg-white" method="POST" action="auth.php" style="min-width:300px">
    <div class="text-center mb-3">
        <img src="../assets/logo.png" alt="Find The Firm" class="logo mb-2">
        <h4>Admin Login</h4>
    </div>
    <input type="text" name="username" class="form-control mb-2" placeholder="Username" required>
    <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
    <button class="btn btn-primary w-100" type="submit">Login</button>
</form>
</body>
</html>