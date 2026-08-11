<?php

require_once "../config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $first_name = trim($_POST["first_name"]);
    $last_name = trim($_POST["last_name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $phone = trim($_POST["phone"]);
    $address = trim($_POST["address"]);

    if ($password !== $confirm_password) {

        $message = "Passwords do not match.";

    } else {

        // Check if email already exists
        $check = $pdo->prepare(
            "SELECT id FROM users WHERE email = ?"
        );

        $check->execute([$email]);

        if ($check->fetch()) {

            $message = "This email is already registered.";

        } else {

            // Securely hash the password
            $hashed_password = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Insert new resident
            $stmt = $pdo->prepare(
                "INSERT INTO users
                (first_name, last_name, email, password, role, phone, address, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
            );

            $stmt->execute([
                $first_name,
                $last_name,
                $email,
                $hashed_password,
                "resident",
                $phone,
                $address,
                "active"
            ]);

            $message = "Registration successful!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Resident Registration</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-7 col-lg-6">

            <div class="card shadow">

                <div class="card-body p-4">

                    <h2 class="text-center mb-4">
                        Create Resident Account
                    </h2>

                    <?php if ($message !== ""): ?>

                        <div class="alert alert-info">
                            <?= htmlspecialchars($message) ?>
                        </div>

                    <?php endif; ?>


                    <form method="POST">

                        <div class="row">

                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    First Name
                                </label>

                                <input
                                    type="text"
                                    name="first_name"
                                    class="form-control"
                                    required
                                >

                            </div>


                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Last Name
                                </label>

                                <input
                                    type="text"
                                    name="last_name"
                                    class="form-control"
                                    required
                                >

                            </div>

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Email Address
                            </label>

                            <input
                                type="email"
                                name="email"
                                class="form-control"
                                required
                            >

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Phone Number
                            </label>

                            <input
                                type="text"
                                name="phone"
                                class="form-control"
                            >

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Address
                            </label>

                            <textarea
                                name="address"
                                class="form-control"
                                rows="3"
                            ></textarea>

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Password
                            </label>

                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                required
                                minlength="8"
                            >

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Confirm Password
                            </label>

                            <input
                                type="password"
                                name="confirm_password"
                                class="form-control"
                                required
                                minlength="8"
                            >

                        </div>


                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >
                            Create Account
                        </button>

                    </form>


                    <div class="text-center mt-3">

                        <a href="../index.php">
                            Back to Home
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>