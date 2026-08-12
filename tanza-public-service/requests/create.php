<?php

session_start();

require_once "../config/database.php";

// Make sure the resident is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

// Make sure only residents can submit requests
if ($_SESSION["role"] !== "resident") {
    header("Location: ../index.php");
    exit;
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $service = trim($_POST["service"]);
    $description = trim($_POST["description"]);

    if ($service === "" || $description === "") {

        $message = "Please complete all required fields.";

    } else {

        // Generate a unique request reference number
        $reference_no = "TZ-" . date("Y") . "-" . strtoupper(
            substr(uniqid(), -6)
        );

        // Initial request status
        $status = "Pending Review";

        // Insert request
        $stmt = $pdo->prepare("
            INSERT INTO requests
            (
                user_id,
                reference_no,
                service,
                description,
                status,
                submitted_at,
                updated_at
            )
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");

        $stmt->execute([
            $_SESSION["user_id"],
            $reference_no,
            $service,
            $description,
            $status
        ]);

        $message = "Request submitted successfully! Reference No.: "
                 . $reference_no;
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

    <title>Submit Request - Tanza Public Service Portal</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>

<body class="bg-light">

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-7">

            <div class="card shadow">

                <div class="card-body p-4">

                    <h2 class="mb-4">
                        Submit a Request
                    </h2>

                    <?php if ($message !== ""): ?>

                        <div class="alert alert-info">
                            <?= htmlspecialchars($message) ?>
                        </div>

                    <?php endif; ?>


                    <form method="POST">

                        <div class="mb-3">

                            <label class="form-label">
                                Service / Request Type
                            </label>

                            <select
                                name="service"
                                class="form-select"
                                required
                            >

                                <option value="">
                                    Select a service
                                </option>

                                <option value="Business Permit Information Request">
                                    Business Permit Information Request
                                </option>

                                <option value="Barangay Clearance Information Request">
                                    Barangay Clearance Information Request
                                </option>

                                <option value="Community Tax Certificate Inquiry">
                                    Community Tax Certificate Inquiry
                                </option>

                                <option value="Indigency Certificate Information Request">
                                    Indigency Certificate Information Request
                                </option>

                                <option value="General Public Service Inquiry">
                                    General Public Service Inquiry
                                </option>

                            </select>

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Description
                            </label>

                            <textarea
                                name="description"
                                class="form-control"
                                rows="5"
                                placeholder="Describe your request or inquiry..."
                                required
                            ></textarea>

                        </div>


                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            Submit Request
                        </button>

                        <a
                            href="../resident/dashboard.php"
                            class="btn btn-secondary"
                        >
                            Back to Dashboard
                        </a>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

</body>

</html>