<?php
// Database connection settings
$servername = "localhost";   
$username   = "root";        
$password   = "";            
$dbname     = "student_information_msdb"; 

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$student = null;
$cgpa_info = null;
$grades_info = null;
$attendance_info = null;
$search_result = null;
$search_error = "";

// Handle logged-in student data
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $student_id = intval($_GET['id']);

    // Personal info
    $stmt = $conn->prepare("SELECT s.student_id, s.roll_number, s.first_name, s.last_name, s.dob, s.contact_number, s.email, s.address, d.department_name
                            FROM students s
                            LEFT JOIN departments d ON s.department_id = d.department_id
                            WHERE s.student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // CGPA info
    $stmt = $conn->prepare("SELECT semester, cgpa FROM academic_info WHERE student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $cgpa_info = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Grades info
    $stmt = $conn->prepare("SELECT g.semester, g.course_code, c.course_name, g.grade
                            FROM grades g
                            JOIN courses c ON g.course_code = c.course_code
                            WHERE g.student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $grades_info = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Attendance info
    $stmt = $conn->prepare("SELECT a.course_code, c.course_name, a.attendance_percentage
                            FROM attendance a
                            JOIN courses c ON a.course_code = c.course_code
                            WHERE a.student_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $attendance_info = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    header("Location: studentlogin.php");
    exit();
}

// Handle search form
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search_roll'])) {
    $search_roll = trim($_POST['search_roll']);
    if (!empty($search_roll)) {
        $stmt = $conn->prepare("SELECT * FROM view_students_with_department WHERE roll_number = ?");
        $stmt->bind_param("s", $search_roll);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $search_result = $result->fetch_assoc();
        } else {
            $search_error = "No student found with Roll Number: " . htmlspecialchars($search_roll);
        }
        $stmt->close();
    } else {
        $search_error = "Please enter a roll number.";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Student Portal Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/x-icon" href="photos/logo.png" />
  </head>
  <body
    class="bg-gradient-to-br from-blue-50 via-gray-100 to-blue-100 font-sans min-h-screen flex flex-col"
  >
    <!-- Header -->
    <header
      class="bg-blue-700 text-white py-5 shadow-lg text-center text-xl font-semibold"
    >
      🎓 Student Information Management System - Dashboard
    </header>

    <!-- Search Section -->
    <section class="bg-white/80 py-6 px-8 shadow-md">
      <div class="max-w-4xl mx-auto">
        <form method="POST">
          <label for="search" class="block text-lg font-semibold text-blue-700 mb-2">
            🔍 Search Students (by Roll Number)
          </label>
          <div class="relative">
            <input
              type="text"
              id="search"
              name="search_roll"
              placeholder="Enter roll number..."
              class="w-full px-5 py-3 rounded-full border border-blue-300 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <button
              type="submit"
              class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-blue-600 text-white px-4 py-2 rounded-full hover:bg-blue-700 transition"
            >
              Search
            </button>
          </div>
        </form>

        <!-- Search Results -->
        <?php if ($search_result): ?>
          <div class="mt-4 p-4 bg-green-100 border border-green-300 rounded-lg">
            <strong>Result Found:</strong><br>
            Name: <?= htmlspecialchars($search_result['first_name'] . ' ' . $search_result['last_name']) ?><br>
            Roll: <?= htmlspecialchars($search_result['roll_number']) ?><br>
            Department: <?= htmlspecialchars($search_result['department_name']) ?><br>
            Email: <?= htmlspecialchars($search_result['email']) ?><br>
            Contact: <?= htmlspecialchars($search_result['contact_number']) ?>
          </div>
        <?php elseif (!empty($search_error)): ?>
          <div class="mt-4 p-4 bg-red-100 border border-red-300 rounded-lg text-red-800">
            <?= $search_error ?>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <!-- Main Content -->
    <main
      class="flex-1 max-w-6xl mx-auto p-8 grid gap-8 sm:grid-cols-2 lg:grid-cols-3"
    >
      <!-- Personal Info -->
      <div class="bg-white/90 backdrop-blur-md p-6 rounded-2xl shadow-lg">
        <h3 class="text-lg font-bold text-blue-700 mb-2">📋 View Personal Info</h3>
        <p class="text-gray-600">
          <?php if ($student): ?>
            Name: <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?><br>
            Roll: <?= htmlspecialchars($student['roll_number']) ?><br>
            Department: <?= htmlspecialchars($student['department_name']) ?><br>
            Email: <?= htmlspecialchars($student['email']) ?><br>
            Contact: <?= htmlspecialchars($student['contact_number']) ?>
          <?php endif; ?>
        </p>
      </div>

      <!-- Academic Info -->
      <div class="bg-white/90 backdrop-blur-md p-6 rounded-2xl shadow-lg">
        <h3 class="text-lg font-bold text-blue-700 mb-2">📊 View Academic Info (CGPA)</h3>
        <p class="text-gray-600">
          <?php if ($cgpa_info): ?>
            <?php foreach ($cgpa_info as $row): ?>
              <?= htmlspecialchars($row['semester']) ?>: <?= htmlspecialchars($row['cgpa']) ?><br>
            <?php endforeach; ?>
          <?php endif; ?>
        </p>
      </div>

      <!-- Grades -->
      <div class="bg-white/90 backdrop-blur-md p-6 rounded-2xl shadow-lg">
        <h3 class="text-lg font-bold text-blue-700 mb-2">📝 View Grades</h3>
        <p class="text-gray-600">
          <?php if ($grades_info): ?>
            <?php foreach ($grades_info as $row): ?>
              <?= htmlspecialchars($row['semester']) ?> - <?= htmlspecialchars($row['course_code']) ?> (<?= htmlspecialchars($row['course_name']) ?>): <?= htmlspecialchars($row['grade']) ?><br>
            <?php endforeach; ?>
          <?php endif; ?>
        </p>
      </div>

      <!-- Attendance -->
      <div class="bg-white/90 backdrop-blur-md p-6 rounded-2xl shadow-lg">
        <h3 class="text-lg font-bold text-blue-700 mb-2">📅 View Attendance</h3>
        <p class="text-gray-600">
          <?php if ($attendance_info): ?>
            <?php foreach ($attendance_info as $row): ?>
              <?= htmlspecialchars($row['course_code']) ?> (<?= htmlspecialchars($row['course_name']) ?>): <?= htmlspecialchars($row['attendance_percentage']) ?>%<br>
            <?php endforeach; ?>
          <?php endif; ?>
        </p>
      </div>

      <!-- Search Students Card -->
      <div class="bg-white/90 backdrop-blur-md p-6 rounded-2xl shadow-lg">
        <h3 class="text-lg font-bold text-blue-700 mb-2">🔍 Search Students</h3>
        <p class="text-gray-600">
          Use the search bar above to find students by roll number.
        </p>
      </div>

      <!-- Students by Course/Dept -->
      <div class="bg-white/90 backdrop-blur-md p-6 rounded-2xl shadow-lg">
        <h3 class="text-lg font-bold text-blue-700 mb-2">🏫 View Students by Course / Department</h3>
        <p class="text-gray-600">
          Browse student lists by course or department.
        </p>
      </div>
    </main>

    <!-- Footer -->
    <footer class="bg-blue-700 text-white text-center py-3 text-sm shadow-inner">
      © 2025 SIMS
    </footer>
  </body>
</html>
