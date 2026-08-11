<?php

session_start();

require_once "../config/database.php";

// Make sure the user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

// Make sure only residents can access this page
if ($_SESSION["role"] !== "resident") {
    header("Location: ../index.php");
    exit;
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

    <title>Resident Dashboard</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">


<!-- Navigation -->

<nav class="navbar navbar-dark bg-primary">

    <div class="container">

        <a
            class="navbar-brand"
            href="dashboard.php"
        >
            Tanza Public Service Portal
        </a>

        <div class="text-white">

            Welcome,
            <?= htmlspecialchars($_SESSION["first_name"]) ?>

            <a
                href="../auth/logout.php"
                class="btn btn-light btn-sm ms-3"
            >
                Logout
            </a>

        </div>

    </div>

</nav>


<!-- Main Content -->

<div class="container py-5">

    <div class="mb-4">

        <h1>
            Resident Dashboard
        </h1>

        <p class="text-muted">
            Access public service information and manage your requests.
        </p>

    </div>


    <!-- Dashboard Cards -->

    <div class="row g-4">


        <!-- Services -->

        <div class="col-md-6 col-lg-4">

            <div class="card shadow-sm h-100">

                <div class="card-body">

                    <h5 class="card-title">
                        Public Services
                    </h5>

                    <p class="card-text">
                        Browse available municipal services,
                        requirements, and other service information.
                    </p>

                    <a
                        href="../services/index.php"
                        class="btn btn-primary"
                    >
                        View Services
                    </a>

                </div>

            </div>

        </div>


        <!-- Submit Request -->

        <div class="col-md-6 col-lg-4">

            <div class="card shadow-sm h-100">

                <div class="card-body">

                    <h5 class="card-title">
                        Submit Request
                    </h5>

                    <p class="card-text">
                        Submit an information request or
                        service-related inquiry to the municipality.
                    </p>

                    <a
                        href="../requests/create.php"
                        class="btn btn-primary"
                    >
                        Submit Request
                    </a>

                </div>

            </div>

        </div>


        <!-- Track Request -->

        <div class="col-md-6 col-lg-4">

            <div class="card shadow-sm h-100">

                <div class="card-body">

                    <h5 class="card-title">
                        Track Request
                    </h5>

                    <p class="card-text">
                        View the status and progress of your
                        submitted requests.
                    </p>

                    <a
                        href="../requests/index.php"
                        class="btn btn-primary"
                    >
                        Track Requests
                    </a>

                </div>

            </div>

        </div>


        <!-- Appointments -->

        <div class="col-md-6 col-lg-4">

            <div class="card shadow-sm h-100">

                <div class="card-body">

                    <h5 class="card-title">
                        Appointments
                    </h5>

                    <p class="card-text">
                        View and manage your scheduled appointments.
                    </p>

                    <a
                        href="../appointments/index.php"
                        class="btn btn-primary"
                    >
                        View Appointments
                    </a>

                </div>

            </div>

        </div>


        <!-- Announcements -->

        <div class="col-md-6 col-lg-4">

            <div class="card shadow-sm h-100">

                <div class="card-body">

                    <h5 class="card-title">
                        Announcements
                    </h5>

                    <p class="card-text">
                        View the latest municipal announcements
                        and public information.
                    </p>

                    <a
                        href="../announcements/index.php"
                        class="btn btn-primary"
                    >
                        View Announcements
                    </a>

                </div>

            </div>

        </div>


        <!-- Feedback -->

        <div class="col-md-6 col-lg-4">

            <div class="card shadow-sm h-100">

                <div class="card-body">

                    <h5 class="card-title">
                        Feedback & Inquiry
                    </h5>

                    <p class="card-text">
                        Send feedback, concerns, suggestions,
                        or inquiries to the municipality.
                    </p>

                    <a
                        href="../feedback/create.php"
                        class="btn btn-primary"
                    >
                        Send Feedback
                    </a>

                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>