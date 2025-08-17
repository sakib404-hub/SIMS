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

// Track active section (default: add-student)
$current_section = "add-student";
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["current_section"])) {
    $current_section = $_POST["current_section"];
}

// Separate messages & result sets for each section
$msg_add_student = $msg_delete_student = $msg_update_student = "";
$msg_add_grade = $msg_add_course = $msg_search = "";
$data_search = $data_students = $data_grades = $data_courses = null;

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
        $msg_add_student = "✅ Student added successfully!";
    } else {
        $msg_add_student = "❌ Error: " . $stmt->error;
    }
    $stmt->close();
}

// Handle Delete Student
if (isset($_POST['delete_student'])) {
    try {
        $stmt = $conn->prepare("CALL DeleteStudent(?)");
        $stmt->bind_param("i", $_POST['student_id']);
        if ($stmt->execute()) {
            $msg_delete_student = "✅ Student deleted successfully!";
        }
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $msg_delete_student = "⚠️ Cannot delete student: " . $e->getMessage();
    }
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
        $msg_update_student = "✅ Student updated successfully!";
    } else {
        $msg_update_student = "❌ Error: " . $stmt->error;
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
        $msg_add_grade = "✅ Grade added successfully!";
    } else {
        $msg_add_grade = "❌ Error: " . $stmt->error;
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
        $msg_add_course = "✅ Course added successfully!";
    } else {
        $msg_add_course = "❌ Error: " . $stmt->error;
    }
    $stmt->close();
}

// Handle Search Student
if (isset($_POST['search_student'])) {
    $roll = trim($_POST['roll_number']);
    if (!empty($roll)) {
        $stmt = $conn->prepare("CALL SearchStudentByRoll(?)");
        $stmt->bind_param("s", $roll);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $data_search = $result->fetch_all(MYSQLI_ASSOC);
            if (empty($data_search)) {
                $msg_search = "⚠️ No student found with roll number: $roll";
            }
        } else {
            $msg_search = "❌ Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $msg_search = "⚠️ Please enter a roll number.";
    }
}

// Handle Show Students
if (isset($_POST['view_students'])) {
    $result = $conn->query("SELECT * FROM view_students_with_department");
    $data_students = $result->fetch_all(MYSQLI_ASSOC);
}

// Handle View Grades
if (isset($_POST['view_grades'])) {
    $stmt = $conn->prepare("CALL GetStudentGrades(?)");
    $stmt->bind_param("s", $_POST['roll_number']);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $data_grades = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        $msg_search = "❌ Error: " . $stmt->error;
    }
    $stmt->close();
}

// Handle View Courses
if (isset($_POST['view_courses'])) {
    $result = $conn->query("SELECT * FROM courses");
    $data_courses = $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans min-h-screen flex flex-col">

<!-- Header -->
<header class="bg-blue-700 text-white py-4 px-6 shadow-md text-center">
  <h1 class="text-lg font-semibold">🛠️ SIMS - Admin Dashboard</h1>
</header>

<div class="flex flex-1">
  <!-- Sidebar -->
  <aside class="bg-blue-800 text-white w-64 flex-shrink-0 shadow-lg flex flex-col justify-between">
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
    <!-- Logout button -->
    <div class="p-4">
      <a href="index.php" class="block bg-red-500 px-4 py-2 rounded-lg hover:bg-red-600 text-center">Logout</a>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="flex-1 p-6 space-y-12">

    <!-- Add Student -->
    <section id="add-student" class="section-content">
      <h2 class="text-2xl font-bold text-blue-700 mb-4">Add Student</h2>
      <form method="post" class="space-y-2">
        <input type="hidden" name="current_section" value="add-student">
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
      <?php if ($msg_add_student): ?>
        <p class="mt-2 text-sm text-yellow-700"><?= htmlspecialchars($msg_add_student) ?></p>
      <?php endif; ?>
    </section>

    <!-- Delete Student -->
    <section id="delete-student" class="section-content">
      <h2 class="text-2xl font-bold text-blue-700 mb-4">Delete Student</h2>
      <form method="post" class="space-y-2">
        <input type="hidden" name="current_section" value="delete-student">
        <input name="student_id" placeholder="Student ID" type="number" class="p-2 border rounded w-full" required>
        <button type="submit" name="delete_student" class="bg-red-500 text-white px-4 py-2 rounded">Delete</button>
      </form>
      <?php if ($msg_delete_student): ?>
        <div class="mt-2 text-sm <?= str_starts_with($msg_delete_student, '✅') ? 'text-green-600' : 'text-red-600' ?>">
          <?= htmlspecialchars($msg_delete_student) ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- Update Student -->
    <section id="update-student" class="section-content">
      <h2 class="text-2xl font-bold text-blue-700 mb-4">Update Student</h2>
      <form method="post" class="space-y-2">
        <input type="hidden" name="current_section" value="update-student">
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
      <?php if ($msg_update_student): ?>
        <p class="mt-2 text-sm text-yellow-700"><?= htmlspecialchars($msg_update_student) ?></p>
      <?php endif; ?>
    </section>

    <!-- Add Grade -->
    <section id="add-grade" class="section-content">
      <h2 class="text-2xl font-bold text-blue-700 mb-4">Add Grade</h2>
      <form method="post" class="space-y-2">
        <input type="hidden" name="current_section" value="add-grade">
        <input name="student_id" placeholder="Student ID" type="number" class="p-2 border rounded w-full" required>
        <input name="course_code" placeholder="Course Code" class="p-2 border rounded w-full" required>
        <input name="semester" placeholder="Semester" class="p-2 border rounded w-full" required>
        <input name="grade" placeholder="Grade" class="p-2 border rounded w-full" required>
        <button type="submit" name="add_grade" class="bg-blue-500 text-white px-4 py-2 rounded">Add</button>
      </form>
      <?php if ($msg_add_grade): ?>
        <p class="mt-2 text-sm text-yellow-700"><?= htmlspecialchars($msg_add_grade) ?></p>
      <?php endif; ?>
    </section>

    <!-- Add Course -->
    <section id="add-course" class="section-content">
      <h2 class="text-2xl font-bold text-blue-700 mb-4">Add Course</h2>
      <form method="post" class="space-y-2">
        <input type="hidden" name="current_section" value="add-course">
        <input name="course_code" placeholder="Course Code" class="p-2 border rounded w-full" required>
        <input name="course_name" placeholder="Course Name" class="p-2 border rounded w-full" required>
        <input name="department_id" placeholder="Department ID" type="number" class="p-2 border rounded w-full" required>
        <button type="submit" name="add_course" class="bg-blue-500 text-white px-4 py-2 rounded">Add</button>
      </form>
      <?php if ($msg_add_course): ?>
        <p class="mt-2 text-sm text-yellow-700"><?= htmlspecialchars($msg_add_course) ?></p>
      <?php endif; ?>
    </section>

    <!-- Search Student -->
    <section id="search-student" class="section-content">
      <h2 class="text-2xl font-bold text-blue-700 mb-4">Search Student</h2>
      <form method="post" class="space-y-2">
        <input type="hidden" name="current_section" value="search-student">
        <input name="roll_number" placeholder="Roll Number" class="p-2 border rounded w-full" required>
        <button type="submit" name="search_student" class="bg-blue-500 text-white px-4 py-2 rounded">Search</button>
      </form>
      <?php if ($msg_search): ?>
        <p class="mt-2 text-sm text-yellow-700"><?= htmlspecialchars($msg_search) ?></p>
      <?php endif; ?>
      <?php if ($data_search): ?>
        <div class="overflow-x-auto mt-4">
          <table class="min-w-full border border-gray-300 bg-white">
            <thead class="bg-gray-200">
              <tr>
                <?php foreach(array_keys($data_search[0]) as $col): ?>
                  <th class="border px-4 py-2"><?= htmlspecialchars($col) ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach($data_search as $row): ?>
                <tr>
                  <?php foreach ($row as $cell): ?>
                    <td class="border px-4 py-2"><?= htmlspecialchars($cell) ?></td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <!-- Show Students -->
    <section id="view-students" class="section-content">
      <h2 class="text-2xl font-bold text-blue-700 mb-4">All Students</h2>
      <form method="post">
        <input type="hidden" name="current_section" value="view-students">
        <button type="submit" name="view_students" class="bg-blue-500 text-white px-4 py-2 rounded">Show Students</button>
      </form>
      <?php if ($data_students): ?>
        <div class="overflow-x-auto mt-4">
          <table class="min-w-full border border-gray-300 bg-white">
            <thead class="bg-gray-200">
              <tr>
                <?php foreach(array_keys($data_students[0]) as $col): ?>
                  <th class="border px-4 py-2"><?= htmlspecialchars($col) ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach($data_students as $row): ?>
                <tr>
                  <?php foreach ($row as $cell): ?>
                    <td class="border px-4 py-2"><?= htmlspecialchars($cell) ?></td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <!-- View Grades -->
    <section id="view-grades" class="section-content">
      <h2 class="text-2xl font-bold text-blue-700 mb-4">View Grades</h2>
      <form method="post" class="space-y-2">
        <input type="hidden" name="current_section" value="view-grades">
        <input name="roll_number" placeholder="Roll Number" class="p-2 border rounded w-full" required>
        <button type="submit" name="view_grades" class="bg-blue-500 text-white px-4 py-2 rounded">View</button>
      </form>
      <?php if ($data_grades): ?>
        <div class="overflow-x-auto mt-4">
          <table class="min-w-full border border-gray-300 bg-white">
            <thead class="bg-gray-200">
              <tr>
                <?php foreach(array_keys($data_grades[0]) as $col): ?>
                  <th class="border px-4 py-2"><?= htmlspecialchars($col) ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach($data_grades as $row): ?>
                <tr>
                  <?php foreach ($row as $cell): ?>
                    <td class="border px-4 py-2"><?= htmlspecialchars($cell) ?></td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <!-- View Courses -->
    <section id="view-courses" class="section-content">
      <h2 class="text-2xl font-bold text-blue-700 mb-4">View Courses</h2>
      <form method="post">
        <input type="hidden" name="current_section" value="view-courses">
        <button type="submit" name="view_courses" class="bg-blue-500 text-white px-4 py-2 rounded">Show Courses</button>
      </form>
      <?php if ($data_courses): ?>
        <div class="overflow-x-auto mt-4">
          <table class="min-w-full border border-gray-300 bg-white">
            <thead class="bg-gray-200">
              <tr>
                <?php foreach(array_keys($data_courses[0]) as $col): ?>
                  <th class="border px-4 py-2"><?= htmlspecialchars($col) ?></th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach($data_courses as $row): ?>
                <tr>
                  <?php foreach ($row as $cell): ?>
                    <td class="border px-4 py-2"><?= htmlspecialchars($cell) ?></td>
                  <?php endforeach; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

  </main>
</div>

<footer class="bg-blue-700 text-white text-center py-3 text-sm shadow-inner">
  © 2025 SIMS
</footer>

<script>
document.addEventListener("DOMContentLoaded", () => {
  const sections = document.querySelectorAll("section");
  const links = document.querySelectorAll("aside nav a");
 const activeSection = "<?= $current_section ?>";

// Hide all sections
sections.forEach(sec => sec.classList.add("hidden"));

// Show the correct one from PHP
if (activeSection) {
    const sec = document.getElementById(activeSection);
    if (sec) sec.classList.remove("hidden");
} else {
    sections[0].classList.remove("hidden"); // fallback
}

// Highlight sidebar link
links.forEach(link => {
    if (link.getAttribute("href") === "#" + activeSection) {
        link.classList.add("bg-blue-600");
    } else {
        link.classList.remove("bg-blue-600");
    }
});

  // Hide all
  sections.forEach(sec => sec.classList.add("hidden"));

  // Show current
  const targetSection = document.getElementById(activeSection);
  if (targetSection) targetSection.classList.remove("hidden");
  else sections[0].classList.remove("hidden");

  // Sidebar clicks
  links.forEach(link => {
    link.addEventListener("click", e => {
      e.preventDefault();
      const targetId = link.getAttribute("href").substring(1);
      sections.forEach(sec => sec.classList.add("hidden"));
      const sec = document.getElementById(targetId);
      if (sec) sec.classList.remove("hidden");
    });
  });
});
</script>

</body>
</html>
