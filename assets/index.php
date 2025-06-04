<?php require 'config.php'; ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>ID Verification - Find The Firm</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container py-5">
    <div class="text-center mb-4">
        <img src="assets/logo.png" alt="Find The Firm" class="logo img-fluid mb-2">
        <h2 class="mt-2">Verify Your Identity</h2>
    </div>

    <?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
        <div class="alert alert-danger">Please fill out all fields and accept the terms.</div>
    <?php elseif (isset($_GET['error']) && $_GET['error'] == 2): ?>
        <div class="alert alert-warning">Submission error. Please try again later.</div>
    <?php endif; ?>

    <form method="POST" action="submit.php" class="card p-4 shadow-sm bg-white">
        <div class="row g-3">
            <div class="col-md-6"><input name="first_name" class="form-control" placeholder="First Name" required></div>
            <div class="col-md-6"><input name="last_name" class="form-control" placeholder="Last Name" required></div>
            <div class="col-md-6"><input name="email" class="form-control" type="email" placeholder="Email Address" required></div>
            <div class="col-md-6"><input name="phone" class="form-control" placeholder="Phone Number (+1...)" required></div>
            <div class="col-12"><input name="street" class="form-control" placeholder="Street Address" required></div>
            <div class="col-md-4"><input name="city" class="form-control" placeholder="City" required></div>
            <div class="col-md-4"><input name="state" class="form-control" placeholder="State (e.g., NY)" required></div>
            <div class="col-md-4"><input name="zip" class="form-control" placeholder="Zip Code" required></div>
            <div class="col-md-6">
                <input name="dob" class="form-control" type="date" required placeholder="Date of Birth">
            </div>
            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="terms" value="1" id="termsCheck" required>
                    <label class="form-check-label" for="termsCheck">
                        I agree to the <a href="#">terms and privacy policy</a>.
                    </label>
                </div>
            </div>
            <div class="col-12"><button class="btn btn-primary w-100" type="submit">Submit for Verification</button></div>
        </div>
    </form>
</div>
</body>
</html>