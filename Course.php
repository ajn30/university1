<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Management</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
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
        <!-- Main content -->
        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-md-4">
            <div class="container mt-5">

                <?php
                // Enable error reporting for debugging
                error_reporting(E_ALL);
                ini_set('display_errors', 1);

                // Database connection
                $conn = new mysqli('localhost', 'root', '', 'university');

                // Check connection
                if ($conn->connect_error) {
                    die("Connection failed: " . $conn->connect_error);
                }

                // Initialize the variable for editing
                $courseToEdit = null;

                // Handle edit request
                if (isset($_GET['edit'])) {
                    $courseId = $_GET['edit'];
                    
                    // Prepare the statement
                    $editStmt = $conn->prepare("SELECT course_id, course_name, budget, department_id FROM Course WHERE course_id = ?");
                    if ($editStmt) {
                        $editStmt->bind_param("i", $courseId);
                        $editStmt->execute();
                        $result = $editStmt->get_result();
                        
                        // Check if any course was found
                        if ($result->num_rows > 0) {
                            $courseToEdit = $result->fetch_assoc();
                        } else {
                            echo '<div class="alert alert-warning" role="alert">No course found with ID: ' . htmlspecialchars($courseId) . '</div>';
                        }
                        $editStmt->close();
                    } else {
                        echo '<div class="alert alert-danger" role="alert">Error preparing statement: ' . $conn->error . '</div>';
                    }
                }

                // Handle create or edit request
                if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                    $courseName = $_POST['course_name'] ?? '';
                    $budget = $_POST['budget'] ?? '';
                    $departmentId = $_POST['department_id'] ?? '';
                    $courseId = $_POST['course_id'] ?? null;

                    // Use prepared statements to prevent SQL injection
                    if ($courseId) {
                        // Update existing course
                        $stmt = $conn->prepare("UPDATE Course SET course_name = ?, budget = ?, department_id = ? WHERE course_id = ?");
                        $stmt->bind_param("ssii", $courseName, $budget, $departmentId, $courseId);
                    } else {
                        // Insert new course
                        $stmt = $conn->prepare("INSERT INTO Course (course_name, budget, department_id) VALUES (?, ?, ?)");
                        $stmt->bind_param("ssi", $courseName, $budget, $departmentId);
                    }

                    if ($stmt->execute()) {
                        echo '<div class="alert alert-success" role="alert">Course saved successfully.</div>';
                    } else {
                        echo '<div class="alert alert-danger" role="alert">Error: ' . $stmt->error . '</div>';
                    }
                    $stmt->close();
                }

                // Read courses
                $result = $conn->query("SELECT course_id, course_name, budget, department_id FROM Course");

                // Display courses
                echo "<h2>Courses</h2>";
                echo "<table class='table table-bordered'>";
                echo "<thead><tr><th>ID</th><th>Name</th><th>Budget</th><th>Department ID</th><th>Actions</th></tr></thead>";
                echo "<tbody>";

                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . $row['course_id'] . "</td>";
                    echo "<td>" . $row['course_name'] . "</td>";
                    echo "<td>" . $row['budget'] . "</td>";
                    echo "<td>" . $row['department_id'] . "</td>"; // Display department ID for now
                    echo "<td>
                            <a href='?edit=" . $row['course_id'] . "' class='btn btn-warning btn-sm'>Edit</a> 
                            <a href='?delete=" . $row['course_id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure you want to delete this course?\");'>Delete</a>
                          </td>";
                    echo "</tr>";
                }
                echo "</tbody></table>";

                // Handle delete
                if (isset($_GET['delete'])) {
                    $courseId = $_GET['delete'];
                    $deleteStmt = $conn->prepare("DELETE FROM Course WHERE course_id = ?");
                    $deleteStmt->bind_param("i", $courseId);
                    if ($deleteStmt->execute()) {
                        echo '<div class="alert alert-success" role="alert">Course deleted successfully.</div>';
                        header("Location: course.php");
                        exit;
                    } else {
                        echo '<div class="alert alert-danger" role="alert">Error: ' . $deleteStmt->error . '</div>';
                    }
                    $deleteStmt->close();
                }

                // Fetch departments for dropdown
                $departmentResult = $conn->query("SELECT department_id, department_name FROM Department");
                $departments = [];
                while ($row = $departmentResult->fetch_assoc()) {
                    $departments[] = $row;
                }
                ?>

                <!-- Form for adding or editing a course -->
                <form method="POST" class="mt-4">
                    <input type="hidden" name="course_id" value="<?php echo $courseToEdit['course_id'] ?? ''; ?>">
                    <div class="form-group">
                        <label for="course_name">Course Name</label>
                        <input type="text" name="course_name" class="form-control" placeholder="Course Name" value="<?php echo $courseToEdit['course_name'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="budget">Budget</label>
                        <input type="text" name="budget" class="form-control" placeholder="Budget" value="<?php echo $courseToEdit['budget'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="department_id">Department</label>
                        <select name="department_id" class="form-control" required>
                            <option value="">Select a department</option>
                            <?php foreach ($departments as $department) : ?>
                                <option value="<?php echo htmlspecialchars($department['department_id']); ?>" <?php echo (isset($courseToEdit) && $courseToEdit['department_id'] == $department['department_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($department['department_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <?php echo isset($courseToEdit) ? 'Update Course' : 'Add Course'; ?>
                    </button>
                </form>

                <!-- Back button -->
                <button onclick="window.location.href='index.php';" class="btn btn-secondary mt-3">Back</button>

                <?php
                // Close the connection if it's defined
                if (isset($conn) && $conn instanceof mysqli) {
                    $conn->close();
                }
                ?>

            </div> <!-- End of container -->
        </main>
    </div>
</div> <!-- End of container-fluid -->

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
