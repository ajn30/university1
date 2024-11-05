<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"> <!-- Link to Bootstrap 5.3.0 CSS -->
    <style>
        body {
            background-color: #f8f9fa;
        }
        .sidebar {
            height: 1400px;
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
                <a class="nav-link" href="classroom.php">Classroom Management</a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="instructor.php">Instructor Management</a>
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

    <div class="content container">

        <?php
        // Database connection
        $conn = new mysqli('localhost', 'root', '', 'university');

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Create or Edit
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $firstName = $_POST['first_name'] ?? '';
            $lastName = $_POST['last_name'] ?? '';
            $dateOfBirth = $_POST['date_of_birth'] ?? '';
            $email = $_POST['email'] ?? '';
            $yearLevel = $_POST['year_level'] ?? '';
            $enrollmentDate = $_POST['enrollment_date'] ?? '';
            $credits = !empty($_POST['credits']) ? $_POST['credits'] : NULL; // Capture credits input, set to NULL if empty
            $studentId = $_POST['student_id'] ?? null;

            // Use prepared statements to prevent SQL injection
            if ($studentId) {
                // Update existing student
                $stmt = $conn->prepare("UPDATE Student SET first_name = ?, last_name = ?, date_of_birth = ?, email = ?, year_level = ?, enrollment_date = ?, credits = ? WHERE student_id = ?");
                $stmt->bind_param("ssssisii", $firstName, $lastName, $dateOfBirth, $email, $yearLevel, $enrollmentDate, $credits, $studentId);
            } else {
                // Insert new student
                $stmt = $conn->prepare("INSERT INTO Student (first_name, last_name, date_of_birth, email, year_level, enrollment_date, credits) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssisi", $firstName, $lastName, $dateOfBirth, $email, $yearLevel, $enrollmentDate, $credits);
            }

            if ($stmt->execute()) {
                echo '<div class="alert alert-success" role="alert">Student saved successfully.</div>';
            } else {
                echo '<div class="alert alert-danger" role="alert">Error: ' . $stmt->error . '</div>';
            }
            $stmt->close();
        }

        // Read students
        $result = $conn->query("SELECT student_id, first_name, last_name, date_of_birth, year_level, enrollment_date, credits FROM Student");

        // Display students
        echo "<h2>Students</h2>";
        echo "<table class='table table-bordered'>";
        echo "<thead><tr><th>ID</th><th>First Name</th><th>Last Name</th><th>Date of Birth</th><th>Year Level</th><th>Enrollment Date</th><th>Credits</th><th>Actions</th></tr></thead>";
        echo "<tbody>";

        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['student_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['first_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['last_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['date_of_birth']) . "</td>";
            echo "<td>" . htmlspecialchars($row['year_level']) . "</td>";
            echo "<td>" . htmlspecialchars($row['enrollment_date']) . "</td>";
            echo "<td>" . (is_null($row['credits']) ? 'NULL' : htmlspecialchars($row['credits'])) . "</td>"; // Display credits as NULL if no value
            echo "<td>
                    <a href='?edit=" . $row['student_id'] . "' class='btn btn-warning btn-sm'>Edit</a> 
                    <a href='?delete=" . $row['student_id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure you want to delete this student?\");'>Delete</a>
                  </td>";
            echo "</tr>";
        }
        echo "</tbody></table>";

        // Delete student
        if (isset($_GET['delete'])) {
            $studentId = $_GET['delete'];
            $deleteStmt = $conn->prepare("DELETE FROM Student WHERE student_id = ?");
            $deleteStmt->bind_param("i", $studentId);
            if ($deleteStmt->execute()) {
                echo '<div class="alert alert-success" role="alert">Student deleted successfully.</div>';
                header("Location: student.php"); // Redirect to avoid re-submission
                exit;
            } else {
                echo '<div class="alert alert-danger" role="alert">Error: ' . $deleteStmt->error . '</div>';
            }
            $deleteStmt->close();
        }

        // Edit student
        $studentToEdit = null;
        if (isset($_GET['edit'])) {
            $studentId = $_GET['edit'];
            $editStmt = $conn->prepare("SELECT * FROM Student WHERE student_id = ?");
            $editStmt->bind_param("i", $studentId);
            $editStmt->execute();
            $studentToEdit = $editStmt->get_result()->fetch_assoc();
            $editStmt->close();
        }
        ?>

        <!-- Form for adding or editing a student -->
        <div class="card mt-4">
            <div class="card-header">
                <h5><?php echo $studentToEdit ? 'Edit Student' : 'Add Student'; ?></h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="student_id" value="<?php echo $studentToEdit['student_id'] ?? ''; ?>">
                    <div class="mb-3">
                        <label for="first_name" class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-control" placeholder="First Name" value="<?php echo htmlspecialchars($studentToEdit['first_name'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="last_name" class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control" placeholder="Last Name" value="<?php echo htmlspecialchars($studentToEdit['last_name'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control" value="<?php echo htmlspecialchars($studentToEdit['date_of_birth'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" placeholder="Email" value="<?php echo htmlspecialchars($studentToEdit['email'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="year_level" class="form-label">Year Level</label>
                        <input type="number" name="year_level" class="form-control" placeholder="Year Level" value="<?php echo htmlspecialchars($studentToEdit['year_level'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="enrollment_date" class="form-label">Enrollment Date</label>
                        <input type="date" name="enrollment_date" class="form-control" value="<?php echo htmlspecialchars($studentToEdit['enrollment_date'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="credits" class="form-label">Credits</label>
                        <input type="number" name="credits" class="form-control" placeholder="0" value="<?php echo htmlspecialchars($studentToEdit['credits'] ?? ''); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <?php echo $studentToEdit ? 'Update Student' : 'Add Student'; ?>
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

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script> <!-- Link to Bootstrap JS -->
    </div>
</div>
</body>
</html>
