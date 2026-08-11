<?php

session_start();

require_once "../config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    $stmt = $pdo->prepare(
        "SELECT id, first_name, last_name, email, password, role, status
         FROM users
         WHERE email = ?
         LIMIT 1"
    );

    $stmt->execute([$email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {

        $message = "Invalid email or password.";

    } elseif (!password_verify($password, $user["password"])) {

        $message = "Invalid email or password.";

    } elseif ($user["status"] !== "active") {

        $message = "Your account is not active.";

    } else {

        session_regenerate_id(true);

        $_SESSION["user_id"] = $user["id"];
        $_SESSION["first_name"] = $user["first_name"];
        $_SESSION["last_name"] = $user["last_name"];
        $_SESSION["email"] = $user["email"];
        $_SESSION["role"] = $user["role"];

        if ($user["role"] === "admin") {

            header("Location: ../admin/dashboard.php");
            exit;

        } else {

            header("Location: ../resident/dashboard.php");
            exit;
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

    <title>Login - Tanza Public Service Portal</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-6 col-lg-5">

            <div class="card shadow">

                <div class="card-body p-4">

                    <h2 class="text-center mb-4">
                        Login
                    </h2>

                    <?php if ($message !== ""): ?>

                        <div class="alert alert-danger">
                            <?= htmlspecialchars($message) ?>
                        </div>

                    <?php endif; ?>

                    <form method="POST">

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
                                Password
                            </label>

                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                required
                            >

                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary w-100"
                        >
                            Login
                        </button>

                    </form>

                    <div class="text-center mt-3">

                        <p class="mb-1">
                            Don't have an account?
                        </p>

                        <a href="register.php">
                            Create an account
                        </a>

                    </div>

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