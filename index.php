<?php require 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ID Verification - Find The Firm</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
    <style>
        body { background-color: #f8f9fa; }
        .logo { max-height: 60px; }
    </style>
</head>
<body>
<div class="container d-flex align-items-center justify-content-center min-vh-100">
    <div class="col-lg-8">
        <div class="text-center mb-4">
            <img src="assets/logo.png" alt="Find The Firm" class="logo img-fluid mb-2">
            <h2 class="mt-2">Verify Your Identity</h2>
        </div>

        <?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
            <div class="alert alert-danger">Please fill out all fields and accept the terms.</div>
        <?php elseif (isset($_GET['error']) && $_GET['error'] == 2): ?>
            <div class="alert alert-warning">Submission error. Please try again later.</div>
        <?php endif; ?>

        <form method="POST" action="submit.php" class="card p-4 shadow-lg border-0 rounded-4 bg-white">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="first_name" class="form-label">First Name</label>
                    <input name="first_name" id="first_name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label for="last_name" class="form-label">Last Name</label>
                    <input name="last_name" id="last_name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input name="email" id="email" type="email" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label for="phone" class="form-label">Phone (+1XXXXXXXXXX)</label>
                    <input name="phone" id="phone" class="form-control" required pattern="^\+1\d{10}$">
                </div>
                <div class="col-12">
                    <label for="street" class="form-label">Street Address</label>
                    <input name="street" id="street" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label for="city" class="form-label">City</label>
                    <input name="city" id="city" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label for="state" class="form-label">State (e.g., NY)</label>
                    <input name="state" id="state" class="form-control" maxlength="2" required>
                </div>
                <div class="col-md-4">
                    <label for="zip" class="form-label">ZIP Code</label>
                    <input name="zip" id="zip" class="form-control" maxlength="5" required pattern="\d{5}">
                </div>
                <div class="col-md-6">
                    <label for="dob" class="form-label">Date of Birth (YYYY-MM-DD)</label>
                    <input name="dob" id="dob" class="form-control dob-field" type="date" required>
                </div>
                <div class="col-12 mt-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="terms" value="1" id="termsCheck" required>
                        <label class="form-check-label" for="termsCheck">
                            I agree to the <a href="/verify/terms-and-privacy.html" target="_blank">terms and privacy policy</a>.
                        </label>
                    </div>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary w-100 shadow-sm" type="submit">Submit for Verification</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector("form");
    const phone = document.getElementById("phone");
    const state = document.getElementById("state");
    const zip = document.getElementById("zip");

    function showError(input, message) {
        let error = input.parentElement.querySelector(".text-danger");
        if (!error) {
            error = document.createElement("div");
            error.classList.add("text-danger", "small", "mt-1");
            input.parentElement.appendChild(error);
        }
        error.textContent = message;
    }

    function clearError(input) {
        const error = input.parentElement.querySelector(".text-danger");
        if (error) error.remove();
    }

    // Ensure consistent formatting prior to submission
    function formatBeforeSubmit() {
        const raw = phone.value.replace(/\D/g, "");
        if (raw.length === 10) {
            phone.value = "+1" + raw;
        } else if (raw.length === 11 && raw.startsWith("1")) {
            phone.value = "+" + raw;
        }
        state.value = state.value.toUpperCase().slice(0, 2);
        zip.value = zip.value.replace(/\D/g, "").slice(0, 5);
    }

    // Live phone formatting
    phone.addEventListener("input", () => {
        let raw = phone.value.replace(/\D/g, '');
        if (raw.length > 11) raw = raw.slice(0, 11);
        if (raw.length === 10) {
            phone.value = "+1" + raw;
        } else if (raw.length === 11 && raw.startsWith("1")) {
            phone.value = "+" + raw;
        }
    });

    // State input validation
    state.addEventListener("input", () => {
        state.value = state.value.toUpperCase().slice(0, 2);
    });

    // Zip code limitation
    zip.addEventListener("input", () => {
        zip.value = zip.value.replace(/\D/g, "").slice(0, 5);
    });

    // Validate before submit
    form.addEventListener("submit", function (e) {
        formatBeforeSubmit();
        let isValid = true;

        const phoneVal = phone.value.trim();
        if (!/^\+1\d{10}$/.test(phoneVal)) {
            showError(phone, "Phone must be in format +1XXXXXXXXXX");
            isValid = false;
        } else {
            clearError(phone);
        }

        const zipVal = zip.value.trim();
        if (!/^\d{5}$/.test(zipVal)) {
            showError(zip, "ZIP must be 5 digits");
            isValid = false;
        } else {
            clearError(zip);
        }

        const stateVal = state.value.trim();
        if (!/^[A-Z]{2}$/.test(stateVal)) {
            showError(state, "Enter 2-letter state code (e.g., NY)");
            isValid = false;
        } else {
            clearError(state);
        }

        if (!isValid) {
            e.preventDefault();
        }
    });
});
</script>
</body>
</html>
