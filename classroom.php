<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classroom Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            height: 100vh;
            background-color: #343a40;
            padding-top: 20px;
        }
        .sidebar a {
            color: #ffffff;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
    </style>
</head>
<body>
<div class="d-flex">

    <!-- Sidebar -->
    <nav class="sidebar p-3">
        <h2 class="text-white">University Management</h2>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="classroom.php">Classroom Management</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="instructor.php">Instructor Management</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="course.php">Course Management</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="timeslot.php">Time Slot Management</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="student.php">Student Management</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="index.php">Dashboard</a>
            </li>
        </ul>
    </nav>

<div class="content">
    <div class="container mt-5">
        <?php
        // Database connection
        $conn = new mysqli('localhost', 'root', '', 'university');

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $roomNumber = $_POST['room_number'];
            $building = $_POST['building'];
            $capacity = $_POST['capacity'];
            $classroomId = $_POST['classroom_id'] ?? null;

            if ($classroomId) {
                // Update existing classroom
                $stmt = $conn->prepare("UPDATE Classroom SET room_number = ?, capacity = ? WHERE classroom_id = ?");
                $stmt->bind_param("ssi", $roomNumber, $capacity, $classroomId);
                $message = "Classroom updated successfully.";
            } else {
                // Check if the building already exists
                $checkStmt = $conn->prepare("SELECT * FROM Classroom WHERE building = ?");
                $checkStmt->bind_param("s", $building);
                $checkStmt->execute();
                $result = $checkStmt->get_result();

                if ($result->num_rows > 0) {
                    // Building exists, update instead of insert
                    $classroomId = $result->fetch_assoc()['classroom_id']; // Get the existing classroom_id
                    $stmt = $conn->prepare("UPDATE Classroom SET room_number = ?, capacity = ? WHERE classroom_id = ?");
                    $stmt->bind_param("ssi", $roomNumber, $capacity, $classroomId);
                    $message = "Classroom updated successfully.";
                } else {
                    // Insert new classroom if building does not exist
                    $stmt = $conn->prepare("INSERT INTO Classroom (room_number, building, capacity) VALUES (?, ?, ?)");
                    $stmt->bind_param("ssi", $roomNumber, $building, $capacity);
                    $message = "Classroom added successfully.";
                }
            }

            // Execute the statement
            if ($stmt->execute()) {
                echo '<div class="alert alert-success" role="alert">' . $message . '</div>';
            } else {
                echo '<div class="alert alert-danger" role="alert">Error: ' . $stmt->error . '</div>';
            }

            $stmt->close();
            if (!isset($classroomId)) {
                $checkStmt->close(); // Only close if it was opened
            }
        }

        // Read classrooms
        $result = $conn->query("SELECT * FROM Classroom");

        // Display classrooms
        echo "<h2>Classrooms</h2>";
        echo "<div class='table-responsive'>";
        echo "<table class='table table-bordered table-striped'>";
        echo "<thead><tr><th>ID</th><th>Room Number</th><th>Building</th><th>Capacity</th><th>Actions</th></tr></thead>";
        echo "<tbody>";

        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['classroom_id'] . "</td>";
            echo "<td>" . htmlspecialchars($row['room_number']) . "</td>";
            echo "<td>" . htmlspecialchars($row['building']) . "</td>";
            echo "<td>" . htmlspecialchars($row['capacity']) . "</td>";
            echo "<td>
                    <a href='?edit=" . $row['classroom_id'] . "' class='btn btn-warning btn-sm'>Edit</a> 
                    <a href='?delete=" . $row['classroom_id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure you want to delete this classroom?\");'>Delete</a>
                  </td>";
            echo "</tr>";
        }
        echo "</tbody></table></div>";

        // Delete classroom
        if (isset($_GET['delete'])) {
            $classroomId = $_GET['delete'];
            $deleteStmt = $conn->prepare("DELETE FROM Classroom WHERE classroom_id = ?");
            $deleteStmt->bind_param("i", $classroomId);
            if ($deleteStmt->execute()) {
                echo '<div class="alert alert-success" role="alert">Classroom deleted successfully.</div>';
                header("Location: classroom.php"); // Redirect to avoid re-submission
                exit;
            } else {
                echo '<div class="alert alert-danger" role="alert">Error: ' . $deleteStmt->error . '</div>';
            }
            $deleteStmt->close();
        }

        // Edit classroom
        $classroomToEdit = null;
        if (isset($_GET['edit'])) {
            $classroomId = $_GET['edit'];
            $editStmt = $conn->prepare("SELECT * FROM Classroom WHERE classroom_id = ?");
            $editStmt->bind_param("i", $classroomId);
            $editStmt->execute();
            $classroomToEdit = $editStmt->get_result()->fetch_assoc();
            $editStmt->close();
        }
        ?>

        <!-- Form for adding or editing a classroom -->
        <div class="card mt-4">
            <div class="card-header">
                <h5><?php echo isset($classroomToEdit) ? 'Edit Classroom' : 'Add Classroom'; ?></h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="classroom_id" value="<?php echo htmlspecialchars($classroomToEdit['classroom_id'] ?? ''); ?>">
                    <div class="mb-3">
                        <label for="room_number" class="form-label">Room Number</label>
                        <input type="text" name="room_number" class="form-control" placeholder="Room Number" value="<?php echo htmlspecialchars($classroomToEdit['room_number'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="building" class="form-label">Building</label>
                        <input type="text" name="building" class="form-control" placeholder="Building" value="<?php echo htmlspecialchars($classroomToEdit['building'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="capacity" class="form-label">Capacity</label>
                        <input type="number" name="capacity" class="form-control" placeholder="Capacity" value="<?php echo htmlspecialchars($classroomToEdit['capacity'] ?? ''); ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <?php echo isset($classroomToEdit) ? 'Update Classroom' : 'Add Classroom'; ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Back button -->
        <button onclick="window.location.href='index.php';" class="btn btn-secondary mt-3">Back</button>

        <?php
        // Close the connection
        $conn->close();
        ?>

    </div> <!-- End of container -->
</div> <!-- End of content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
