<?php
// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "student_information_msdb";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

$message = ""; // feedback message
$result_data = null; // for displaying query results

// Handle Add Student
if (isset($_POST['add_student'])) {
    $stmt = $conn->prepare("CALL AddStudent(?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssi",
        $_POST['roll_number'],
        $_POST['first_name'],
        $_POST['last_name'],
        $_POST['dob'],
        $_POST['contact_number'],
        $_POST['email'],
        $_POST['address'],
        $_POST['department_id']
    );
    if ($stmt->execute()) {
        $message = "✅ Student added successfully!";
    } else {
        $message = "❌ Error: " . $stmt->error;
    }
    $stmt->close();
}

// Handle Delete Student
if (isset($_POST['delete_student'])) {
    $stmt = $conn->prepare("CALL DeleteStudent(?)");
    $stmt->bind_param("i", $_POST['student_id']);
    if ($stmt->execute()) {
        $message = "✅ Student deleted successfully!";
    } else {
        $message = "❌ Error: " . $stmt->error;
    }
    $stmt->close();
}

// Handle Update Student
if (isset($_POST['update_student'])) {
    $stmt = $conn->prepare("CALL UpdateStudent(?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssssi",
        $_POST['student_id'],
        $_POST['roll_number'],
        $_POST['first_name'],
        $_POST['last_name'],
        $_POST['dob'],
        $_POST['contact_number'],
        $_POST['address'],
        $_POST['department_id']
    );
    if ($stmt->execute()) {
        $message = "✅ Student updated successfully!";
    } else {
        $message = "❌ Error: " . $stmt->error;
    }
    $stmt->close();
}

// Handle Add Grade
if (isset($_POST['add_grade'])) {
    $stmt = $conn->prepare("INSERT INTO grades (student_id, course_code, semester, grade) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss",
        $_POST['student_id'],
        $_POST['course_code'],
        $_POST['semester'],
        $_POST['grade']
    );
    if ($stmt->execute()) {
        $message = "✅ Grade added successfully!";
    } else {
        $message = "❌ Error: " . $stmt->error;
    }
    $stmt->close();
}

// Handle Add Course
if (isset($_POST['add_course'])) {
    $stmt = $conn->prepare("INSERT INTO courses (course_code, course_name, department_id) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi",
        $_POST['course_code'],
        $_POST['course_name'],
        $_POST['department_id']
    );
    if ($stmt->execute()) {
        $message = "✅ Course added successfully!";
    } else {
        $message = "❌ Error: " . $stmt->error;
    }
    $stmt->close();
}

// Handle Search Student
if (isset($_POST['search_student'])) {
    if (!empty($_POST['student_id'])) {
        $stmt = $conn->prepare("CALL SearchStudentById(?)");
        $stmt->bind_param("i", $_POST['student_id']);
    } else {
        $stmt = $conn->prepare("CALL SearchStudentByRoll(?)");
        $stmt->bind_param("s", $_POST['roll_number']);
    }
    if ($stmt->execute()) {
        $result_data = $stmt->get_result();
    } else {
        $message = "❌ Error: " . $stmt->error;
    }
    $stmt->close();
}

// Handle Show Students
if (isset($_POST['view_students'])) {
    $result_data = $conn->query("SELECT * FROM view_students_with_department");
}

// Handle View Grades
if (isset($_POST['view_grades'])) {
    $stmt = $conn->prepare("CALL GetStudentGrades(?)");
    $stmt->bind_param("s", $_POST['roll_number']);
    if ($stmt->execute()) {
        $result_data = $stmt->get_result();
    } else {
        $message = "❌ Error: " . $stmt->error;
    }
    $stmt->close();
}

// Handle View Courses
if (isset($_POST['view_courses'])) {
    $result_data = $conn->query("SELECT * FROM courses");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
// Smooth scroll
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener("click", function(e) {
      e.preventDefault();
      document.querySelector(this.getAttribute("href")).scrollIntoView({
        behavior: "smooth"
      });
    });
  });
});
</script>
</head>
<body class="bg-gray-100 font-sans min-h-screen flex flex-col">
<!-- Header -->
<header class="bg-blue-700 text-white py-4 px-6 flex justify-between items-center shadow-md">
  <h1 class="text-lg font-semibold">🛠️ SIMS - Admin Dashboard</h1>
  <a href="index.html" class="bg-red-500 px-4 py-2 rounded-lg hover:bg-red-600 transition">Logout</a>
</header>

<div class="flex flex-1">
  <!-- Sidebar -->
  <aside class="bg-blue-800 text-white w-64 flex-shrink-0 shadow-lg">
    <nav class="flex flex-col p-4 gap-2">
      <a href="#add-student" class="px-3 py-2 rounded-lg hover:bg-blue-600">Add Student</a>
      <a href="#delete-student" class="px-3 py-2 rounded-lg hover:bg-blue-600">Delete Student</a>
      <a href="#update-student" class="px-3 py-2 rounded-lg hover:bg-blue-600">Update Student</a>
      <a href="#add-grade" class="px-3 py-2 rounded-lg hover:bg-blue-600">Add Grade</a>
      <a href="#add-course" class="px-3 py-2 rounded-lg hover:bg-blue-600">Add Course</a>
      <a href="#search-student" class="px-3 py-2 rounded-lg hover:bg-blue-600">Search Student</a>
      <a href="#view-students" class="px-3 py-2 rounded-lg hover:bg-blue-600">Show Students</a>
      <a href="#view-grades" class="px-3 py-2 rounded-lg hover:bg-blue-600">View Grades</a>
      <a href="#view-courses" class="px-3 py-2 rounded-lg hover:bg-blue-600">View Courses</a>
    </nav>
  </aside>

  <!-- Main Content -->
  <main class="flex-1 p-6 space-y-12">
    <?php if ($message): ?>
      <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>

    <!-- Add Student -->
    <section id="add-student">
      <h2 class="text-2xl font-bold text-blue-700 mb-4">Add Student</h2>
      <form method="post" class="space-y-2">
        <input name="roll_number" placeholder="Roll Number" class="p-2 border rounded w-full" required>
        <input name="first_name" placeholder="First Name" class="p-2 border rounded w-full" required>
        <input name="last_name" placeholder="Last Name" class="p-2 border rounded w-full" required>
        <input type="date" name="dob" class="p-2 border rounded w-full" required>
        <input name="contact_number" placeholder="Contact Number" class="p-2 border rounded w-full">
        <input name="email" placeholder="Email (optional)" class="p-2 border rounded w-full">
        <textarea name="address" placeholder="Address" class="p-2 border rounded w-full"></textarea>
        <input name="department_id" placeholder="Department ID" type="number" class="p-2 border rounded w-full" required>
        <button type="submit" name="add_student" class="bg-blue-500 text-white px-4 py-2 rounded">Add</button>
      </form>
    </section>

    <!-- Delete Student -->
    <section id="delete-student">
      <h2 class="text-2xl font-bold text-blue-700 mb-4">Delete Student</h2>
      <form method="post" class="space-y-2">
        <input name="student_id" placeholder="Student ID" type="number" class="p-2 border rounded w-full" required>
        <button type="submit" name="delete_student" class="bg-red-500 text-white px-4 py-2 rounded">Delete</button>
      </form>
    </section>

    <!-- Update Student -->
    <section id="update-student">
      <h2 class="text-2xl font-bold text-blue-700 mb-4">Update Student</h2>
      <form method="post" class="space-y-2">
        <input name="student_id" placeholder="Student ID" type="number" class="p-2 border rounded w-full" required>
        <input name="roll_number" placeholder="Roll Number" class="p-2 border rounded w-full" required>
        <input name="first_name" placeholder="First Name" class="p-2 border rounded w-full" required>
        <input name="last_name" placeholder="Last Name" class="p-2 border rounded w-full" required>
        <input type="date" name="dob" class="p-2 border rounded w-full" required>
        <input name="contact_number" placeholder="Contact Number" class="p-2 border rounded w-full">
        <textarea name="address" placeholder="Address" class="p-2 border rounded w-full"></textarea>
        <input name="department_id" placeholder="Department ID" type="number" class="p-2 border rounded w-full" required>
        <button type="submit" name="update_student" class="bg-green-500 text-white px-4 py-2 rounded">Update</button>
      </form>
    </section>

    <!-- Add Grade -->
    <section id="add-grade">
      <h2 class="text-2xl font-bold text-blue-700 mb-4">Add Grade</h2>
      <form method="post" class="space-y-2">
        <input name="student_id" placeholder="Student ID" type="number" class="p-2 border rounded w-full" required>
        <input name="course_code" placeholder="Course Code" class="p-2 border rounded w-full" required>
        <input name="semester" placeholder="Semester" class="p-2 border rounded w-full" required>
        <input name="grade" placeholder="Grade" class="p-2 border rounded w-full" required>
        <button type="submit" name="add_grade" class="bg-blue-500 text-white px-4 py-2 rounded">Add</button>
      </form>
    </section>

    <!-- Add Course -->
    <section id="add-course">
      <h2 class="text-2xl font-bold text-blue-700 mb-4">Add Course</h2>
      <form method="post" class="space-y-2">
        <input name="course_code" placeholder="Course Code" class="p-2 border rounded w-full" required>
        <input name="course_name" placeholder="Course Name" class="p-2 border rounded w-full" required>
        <input name="department_id" placeholder="Department ID" type="number" class="p-2 border rounded w-full" required>
        <button type="submit" name="add_course" class="bg-blue-500 text-white px-4 py-2 rounded">Add</button>
      </form>
    </section>

    <!-- Search Student -->
    <section id="search-student">
      <h2 class="text-2xl font-bold text-blue-700 mb-4">Search Student</h2>
      <form method="post" class="space-y-2">
        <input name="student_id" placeholder="Student ID" type="number" class="p-2 border rounded w-full">
        <input name="roll_number" placeholder="Roll Number" class="p-2 border rounded w-full">
        <button type="submit" name="search_student" class="bg-blue-500 text-white px-4 py-2 rounded">Search</button>
      </form>
    </section>

    <!-- Show Students -->
    <section id="view-students">
      <h2 class="text-2xl font-bold text-blue-700 mb-4">All Students</h2>
      <form method="post">
        <button type="submit" name="view_students" class="bg-blue-500 text-white px-4 py-2 rounded">Show Students</button>
      </form>
    </section>

    <!-- View Grades -->
    <section id="view-grades">
      <h2 class="text-2xl font-bold text-blue-700 mb-4">View Grades</h2>
      <form method="post" class="space-y-2">
        <input name="roll_number" placeholder="Roll Number" class="p-2 border rounded w-full" required>
        <button type="submit" name="view_grades" class="bg-blue-500 text-white px-4 py-2 rounded">View</button>
      </form>
    </section>

    <!-- View Courses -->
    <section id="view-courses">
      <h2 class="text-2xl font-bold text-blue-700 mb-4">View Courses</h2>
      <form method="post">
        <button type="submit" name="view_courses" class="bg-blue-500 text-white px-4 py-2 rounded">Show Courses</button>
      </form>
    </section>

    <!-- Results Table -->
    <?php if ($result_data && $result_data->num_rows > 0): ?>
      <div class="overflow-x-auto mt-6">
        <table class="min-w-full border border-gray-300 bg-white">
          <thead class="bg-gray-200">
            <tr>
              <?php foreach(array_keys($result_data->fetch_assoc()) as $col): ?>
                <th class="border px-4 py-2"><?= htmlspecialchars($col) ?></th>
              <?php endforeach; $result_data->data_seek(0); ?>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = $result_data->fetch_assoc()): ?>
              <tr>
                <?php foreach ($row as $cell): ?>
                  <td class="border px-4 py-2"><?= htmlspecialchars($cell) ?></td>
                <?php endforeach; ?>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </main>
</div>

<footer class="bg-blue-700 text-white text-center py-3 text-sm shadow-inner">
  © 2025 SIMS
</footer>
</body>
</html>
