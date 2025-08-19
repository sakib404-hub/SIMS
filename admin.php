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
$msg_dept_search = "";
$data_dept_students = null;

// Handle Add Student
if (isset($_POST['add_student'])) {
    try {
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

        // Clear any result sets left by the procedure
        while ($conn->more_results() && $conn->next_result()) { 
            $conn->use_result(); 
        }

        $stmt->close();

    } catch (mysqli_sql_exception $e) {
        $msg_add_student = "⚠️ Failed to add student: " . $e->getMessage();
    }
}

// Handle Preview Student Before Delete
$delete_preview = null;
if (isset($_POST['preview_delete'])) {
    $id = intval($_POST['student_id']);
    $stmt = $conn->prepare("CALL SearchStudentById(?)");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $delete_preview = $result->fetch_assoc();
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
        $msg_update_student = "✅ Student updated successfully!";
    } else {
        $msg_update_student = "❌ Error: " . $stmt->error;
    }
    $stmt->close();
}
// Handle Enroll Student
if (isset($_POST['enroll_student'])) {
    try {
        $stmt = $conn->prepare("CALL EnrollStudent(?, ?, ?)");
        $stmt->bind_param("iss",
            $_POST['student_id'],
            $_POST['course_code'],
            $_POST['semester']
        );

        if ($stmt->execute()) {
            $msg_enroll = "✅ Student enrolled successfully!";
        } else {
            $msg_enroll = "❌ Error: " . $stmt->error;
        }

        // Clear extra results from procedure
        while ($conn->more_results() && $conn->next_result()) {
            $conn->use_result();
        }

        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $msg_enroll = "⚠️ Enrollment failed: " . $e->getMessage();
    }
}


// Handle Add Grade
if (isset($_POST['add_grade'])) {
    $stmt = $conn->prepare("CALL AddGrade(?, ?, ?, ?)");
    $stmt->bind_param("isss",
        $_POST['student_id'],
        $_POST['course_code'],
        $_POST['semester'],
        $_POST['grade']
    );

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $msg_add_grade = $row['message']; // message from procedure
    } else {
        $msg_add_grade = "❌ Error: " . $stmt->error;
    }
    $stmt->close();

    // Flush extra results from CALL
    while ($conn->more_results() && $conn->next_result()) {;}
}

// Handle Add/Update CGPA (by roll number)
if (isset($_POST['add_cgpa'])) {
    try {
        $stmt = $conn->prepare("CALL UpdateCGPAByRoll(?, ?, ?)");
        $stmt->bind_param("ssd",
            $_POST['roll_number'],
            $_POST['semester'],
            $_POST['cgpa']
        );

        if ($stmt->execute()) {
            $msg_cgpa = "✅ CGPA saved successfully!";
        } else {
            $msg_cgpa = "❌ Error: " . $stmt->error;
        }

        // Clear extra results
        while ($conn->more_results() && $conn->next_result()) {
            $conn->use_result();
        }

        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $msg_cgpa = "⚠️ Failed to save CGPA: " . $e->getMessage();
    }
}// Handle View CGPA
if (isset($_POST['view_cgpa'])) {
    try {
        $stmt = $conn->prepare("CALL GetStudentCGPA(?)");
        $stmt->bind_param("s", $_POST['roll_number']);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $data_cgpa = $result->fetch_all(MYSQLI_ASSOC);

            if (empty($data_cgpa)) {
                $msg_cgpa_search = "⚠️ No CGPA records found for roll number " . $_POST['roll_number'];
            } else {
                $msg_cgpa_search = "✅ Found " . count($data_cgpa) . " CGPA record(s).";
            }
        } else {
            $msg_cgpa_search = "❌ Error: " . $stmt->error;
        }

        // Clear extra results from procedure
        while ($conn->more_results() && $conn->next_result()) {
            $conn->use_result();
        }

        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $msg_cgpa_search = "⚠️ Failed to fetch CGPA: " . $e->getMessage();
    }
}



// Handle Add Course
if (isset($_POST['add_course'])) {
    $stmt = $conn->prepare("CALL AddCourse(?, ?, ?)");
    $stmt->bind_param("ssi",
        $_POST['course_code'],
        $_POST['course_name'],
        $_POST['department_id']
    );

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $msg_add_course = $row['message']; // get message from procedure
    } else {
        $msg_add_course = "❌ Error: " . $stmt->error;
    }
    $stmt->close();

    // Flush extra results (required after CALL)
    while ($conn->more_results() && $conn->next_result()) {;}
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
// Handle Preview Student Before Delete
$delete_preview = null;
if (isset($_POST['preview_delete'])) {
    $id = intval($_POST['student_id']);
    $stmt = $conn->prepare("CALL SearchStudentById(?)");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $delete_preview = $result->fetch_assoc();
        if ($delete_preview) {
            $msg_delete_student = "✅ Student found! Please confirm deletion.";
        } else {
            $msg_delete_student = "⚠️ No student found with ID: " . $id;
        }
    } else {
        $msg_delete_student = "❌ Error: " . $stmt->error;
    }
    $stmt->close();
}



// Handle Show Students
if (isset($_POST['view_students'])) {
    $result = $conn->query("SELECT * FROM view_students_with_department");
    $data_students = $result->fetch_all(MYSQLI_ASSOC);
}

// Handle View Grades
if (isset($_POST['view_grades'])) {
    try {
        $stmt = $conn->prepare("CALL GetStudentGrades(?)");
        $stmt->bind_param("s", $_POST['roll_number']);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $data_grades = $result->fetch_all(MYSQLI_ASSOC);

            if (empty($data_grades)) {
                $msg_search = "⚠️ This student is not enrolled in any course.";
            } else {
                $msg_search = "✅ Found " . count($data_grades) . " grade(s) for this student.";
            }
        } else {
            $msg_search = "❌ Error: " . $stmt->error;
        }

        // Clear extra result sets from procedure
        while ($conn->more_results() && $conn->next_result()) {
            $conn->use_result();
        }

        $stmt->close();

    } catch (mysqli_sql_exception $e) {
        $msg_search = "❌ Failed to fetch grades: " . $e->getMessage();
    }
}

// Handle Enroll Student (by roll number)
if (isset($_POST['enroll_student'])) {
    try {
        $stmt = $conn->prepare("CALL EnrollStudentByRoll(?, ?, ?)");
        $stmt->bind_param("sss",
            $_POST['roll_number'],
            $_POST['course_code'],
            $_POST['semester']
        );

        if ($stmt->execute()) {
            $msg_enroll = "✅ Student enrolled successfully!";
        } else {
            $msg_enroll = "❌ Error: " . $stmt->error;
        }

        // Clear extra results
        while ($conn->more_results() && $conn->next_result()) {
            $conn->use_result();
        }

        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $msg_enroll = "⚠️ Enrollment failed: " . $e->getMessage();
    }
}



// Handle View Courses
// Fetch all departments for dropdown
$departments = [];
$result = $conn->query("SELECT department_name FROM departments ORDER BY department_name");
if ($result) {
    $departments = $result->fetch_all(MYSQLI_ASSOC);
}

// View all courses
if (isset($_POST['view_courses'])) {
    $result = $conn->query("SELECT * FROM view_courses_with_department");
    $data_courses = $result->fetch_all(MYSQLI_ASSOC);
}

// View courses by department
if (isset($_POST['view_courses_by_department'])) {
    $dept_name = $_POST['department_name'];
    $stmt = $conn->prepare("SELECT * FROM view_courses_with_department WHERE department_name = ?");
    $stmt->bind_param("s", $dept_name);
    $stmt->execute();
    $result = $stmt->get_result();
    $data_courses = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Fetch all departments for dropdown
$departments = [];
$result = $conn->query("SELECT department_name FROM departments ORDER BY department_name");
if ($result) {
    $departments = $result->fetch_all(MYSQLI_ASSOC);
}
// Handle Search by Department (using procedure)
if (isset($_POST['search_by_department'])) {
    $dept_name = $_POST['department_name'];

    try {
        $stmt = $conn->prepare("CALL GetStudentsByDepartment(?)");
        $stmt->bind_param("s", $dept_name);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $data_dept_students = $result->fetch_all(MYSQLI_ASSOC);

            if (empty($data_dept_students)) {
                $msg_dept_search = "⚠️ No students found in department: $dept_name";
            } else {
                $msg_dept_search = "✅ Found " . count($data_dept_students) . " student(s) in $dept_name.";
            }
        } else {
            $msg_dept_search = "❌ Error: " . $stmt->error;
        }

        // Clear extra results from procedure
        while ($conn->more_results() && $conn->next_result()) {
            $conn->use_result();
        }

        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $msg_dept_search = "❌ Search failed: " . $e->getMessage();
    }
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
<header class="bg-[#174B6C] text-white py-4 px-6 shadow-md text-center">
  <h1 class="text-lg font-semibold">🛠️ SIMS - Admin Dashboard</h1>
</header>

<div class="flex flex-1">
  <!-- Sidebar -->
  <aside class="bg-[#40A9E0] text-white w-64 flex-shrink-0 shadow-lg flex flex-col justify-between">
  <nav class="flex flex-col p-4 gap-2">
    <!-- <a href="#add-student" 
       class="px-3 py-2 rounded-lg hover:bg-[#174B6C] transition-colors">Add Student</a> -->
       <a href="#add-student"
       class="px-3 py-2 bg-none rounded-lg hover:bg-[#174B6C] transition-colors" >
        Add Student
       </a>
    <a href="#delete-student" 
       class="px-3 py-2 rounded-lg hover:bg-[#174B6C] transition-colors">Delete Student</a>
    <a href="#update-student" 
       class="px-3 py-2 rounded-lg hover:bg-[#174B6C] transition-colors">Update Student</a>
       <a href="#enroll-student" 
   class="px-3 py-2 rounded-lg hover:bg-[#174B6C] transition-colors">Enroll Student</a>

    <a href="#add-grade" 
       class="px-3 py-2 rounded-lg hover:bg-[#174B6C] transition-colors">Add Grade</a>
       <a href="#add-cgpa" 
   class="px-3 py-2 rounded-lg hover:bg-[#174B6C] transition-colors">Add/Update CGPA</a>

   <a href="#view-cgpa" 
   class="px-3 py-2 rounded-lg hover:bg-[#174B6C] transition-colors">View CGPA</a>

    <a href="#add-course" 
       class="px-3 py-2 rounded-lg hover:bg-[#174B6C] transition-colors">Add Course</a>
    <a href="#search-student" 
       class="px-3 py-2 rounded-lg hover:bg-[#174B6C] transition-colors">Search Student</a>
    <a href="#view-students" 
       class="px-3 py-2 rounded-lg hover:bg-[#174B6C] transition-colors">Show Students</a>
    <a href="#view-grades" 
       class="px-3 py-2 rounded-lg hover:bg-[#174B6C] transition-colors">View Grades</a>
    <a href="#view-courses" 
       class="px-3 py-2 rounded-lg hover:bg-[#174B6C] transition-colors">View Courses</a>
    <a href="#search-department" 
       class="px-3 py-2 rounded-lg hover:bg-[#174B6C] transition-colors">Search by Department</a>
  </nav>

  <!-- Logout button -->
  <div class="p-4">
    <a href="index.php" 
       class="block bg-red-500 px-4 py-2 rounded-lg hover:bg-red-600 text-center transition-colors">Logout</a>
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
  
  <!-- Step 1: Search student by ID -->
  <form method="post" class="space-y-2 mb-4">
    <input type="hidden" name="current_section" value="delete-student">
    <input name="student_id" placeholder="Student ID" type="number" class="p-2 border rounded w-full" required>
    <button type="submit" name="preview_delete" class="bg-yellow-500 text-white px-4 py-2 rounded">
      Preview Student
    </button>
  </form>

  <?php if ($msg_delete_student): ?>
    <div class="mt-2 text-sm 
         <?= str_starts_with($msg_delete_student, '✅') ? 'text-green-600' : 'text-red-600' ?>">
      <?= htmlspecialchars($msg_delete_student) ?>
    </div>
  <?php endif; ?>

  <?php if ($delete_preview): ?>
    <div class="p-4 border rounded bg-white shadow mt-3">
      <p><strong>ID:</strong> <?= htmlspecialchars($delete_preview['student_id']) ?></p>
      <p><strong>Roll:</strong> <?= htmlspecialchars($delete_preview['roll_number']) ?></p>
      <p><strong>Name:</strong> <?= htmlspecialchars($delete_preview['first_name'] . " " . $delete_preview['last_name']) ?></p>
      <p><strong>Department:</strong> <?= htmlspecialchars($delete_preview['department_name']) ?></p>
      
      <!-- Confirm Delete -->
      <form method="post" class="mt-3">
        <input type="hidden" name="current_section" value="delete-student">
        <input type="hidden" name="student_id" value="<?= htmlspecialchars($delete_preview['student_id']) ?>">
        <button type="submit" name="delete_student" class="bg-red-600 text-white px-4 py-2 rounded">
          Confirm Delete
        </button>
      </form>
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
  <section id="enroll-student" class="section-content">
  <h2 class="text-2xl font-bold text-blue-700 mb-4">Enroll Student</h2>

  <!-- Enroll Form -->
  <form method="post" class="space-y-2">
    <input type="hidden" name="current_section" value="enroll-student">

    <input type="text" name="roll_number" placeholder="Roll Number" class="p-2 border rounded w-full" required>
    <input type="text" name="course_code" placeholder="Course Code (e.g. CS101)" class="p-2 border rounded w-full" required>
    <input type="text" name="semester" placeholder="Semester (e.g. Fall 2023)" class="p-2 border rounded w-full" required>

    <button type="submit" name="enroll_student" class="bg-blue-500 text-white px-4 py-2 rounded">
      Enroll
    </button>
  </form>

  <!-- Message -->
  <?php if (isset($msg_enroll)): ?>
    <p class="mt-4 font-semibold 
      <?= strpos($msg_enroll, '✅') === 0 ? 'text-green-600' : 'text-red-600' ?>">
      <?= htmlspecialchars($msg_enroll) ?>
    </p>
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
    <!-- Add/Update/CGPA  -->
    <section id="add-cgpa" class="section-content">
    <h2 class="text-2xl font-bold text-blue-700 mb-4">Add/Update CGPA</h2>
     <form method="post" class="space-y-2">
    <input type="hidden" name="current_section" value="add-cgpa">

    <input type="text" name="roll_number" placeholder="Roll Number" class="p-2 border rounded w-full" required>
    <input type="text" name="semester" placeholder="Semester (e.g. Fall 2023)" class="p-2 border rounded w-full" required>
    <input type="number" step="0.01" min="0" max="4.00" name="cgpa" placeholder="CGPA (e.g. 3.75)" class="p-2 border rounded w-full" required>

    <button type="submit" name="add_cgpa" class="bg-blue-500 text-white px-4 py-2 rounded">
      Save CGPA
    </button>
  </form>

  <?php if (isset($msg_cgpa)): ?>
    <p class="mt-4 font-semibold 
      <?= strpos($msg_cgpa, '✅') === 0 ? 'text-green-600' : 'text-red-600' ?>">
      <?= htmlspecialchars($msg_cgpa) ?>
    </p>
  <?php endif; ?>
</section>

<section id="view-cgpa" class="section-content">
  <h2 class="text-2xl font-bold text-blue-700 mb-4">View CGPA</h2>

  <!-- Search Form -->
  <form method="post" class="space-y-2">
    <input type="hidden" name="current_section" value="view-cgpa">
    
    <input type="text" 
           name="roll_number" 
           placeholder="Enter Roll Number" 
           class="p-2 border rounded w-full" 
           required>

    <button type="submit" 
            name="view_cgpa" 
            class="bg-blue-500 text-white px-4 py-2 rounded">
      View
    </button>
  </form>

  <!-- Show error/success message -->
  <?php if (isset($msg_cgpa_search)): ?>
    <p class="mt-4 font-semibold 
      <?= strpos($msg_cgpa_search, '✅') === 0 ? 'text-green-600' : 'text-red-600' ?>">
      <?= htmlspecialchars($msg_cgpa_search) ?>
    </p>
  <?php endif; ?>

  <!-- Show table only if data exists -->
  <?php if (isset($data_cgpa) && !empty($data_cgpa)): ?>
    <div class="overflow-x-auto mt-4">
      <table class="min-w-full border border-gray-300 bg-white">
        <thead class="bg-gray-200">
          <tr>
            <th class="border px-4 py-2">Roll Number</th>
            <th class="border px-4 py-2">Full Name</th>
            <th class="border px-4 py-2">Department</th>
            <th class="border px-4 py-2">Semester</th>
            <th class="border px-4 py-2">CGPA</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($data_cgpa as $row): ?>
            <tr>
              <td class="border px-4 py-2"><?= htmlspecialchars($row['roll_number']) ?></td>
              <td class="border px-4 py-2"><?= htmlspecialchars($row['full_name']) ?></td>
              <td class="border px-4 py-2"><?= htmlspecialchars($row['department_name']) ?></td>
              <td class="border px-4 py-2"><?= htmlspecialchars($row['semester']) ?></td>
              <td class="border px-4 py-2"><?= htmlspecialchars($row['cgpa']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
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

  <!-- Search Form -->
  <form method="post" class="space-y-2">
    <input type="hidden" name="current_section" value="view-grades">
    <input name="roll_number" placeholder="Roll Number" class="p-2 border rounded w-full" required>
    <button type="submit" name="view_grades" class="bg-blue-500 text-white px-4 py-2 rounded">View</button>
  </form>

  <!-- Message -->
  <?php if (isset($msg_search)): ?>
    <p class="mt-4 font-semibold 
      <?= strpos($msg_search, '✅') === 0 ? 'text-green-600' : 'text-red-600' ?>">
      <?= htmlspecialchars($msg_search) ?>
    </p>
  <?php endif; ?>

  <!-- Table -->
  <?php if (isset($data_grades) && !empty($data_grades)): ?>
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

</section>
    <!-- View Courses -->
    <section id="view-courses" class="section-content">
  <h2 class="text-2xl font-bold text-blue-700 mb-4">View Courses</h2>

  <!-- Show all courses -->
  <form method="post" class="mb-4">
    <input type="hidden" name="current_section" value="view-courses">
    <button type="submit" name="view_courses" class="bg-blue-500 text-white px-4 py-2 rounded">
      Show All Courses
    </button>
  </form>

  <!-- Filter by Department -->
  <form method="post" class="mb-4 flex gap-2">
    <input type="hidden" name="current_section" value="view-courses">
    <select name="department_name" class="p-2 border rounded w-full" required>
      <option value="">-- Select Department --</option>
      <?php foreach ($departments as $dept): ?>
        <option value="<?= htmlspecialchars($dept['department_name']) ?>">
          <?= htmlspecialchars($dept['department_name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <button type="submit" name="view_courses_by_department" class="bg-green-500 text-white px-4 py-2 rounded">
      Filter
    </button>
  </form>

  <!-- Show results -->
  <?php if (!empty($data_courses)): ?>
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

<!-- Search Students by Department -->
<section id="search-department" class="section-content">
  <h2 class="text-2xl font-bold text-blue-700 mb-4">Search Students by Department</h2>
  <form method="post" class="space-y-2">
    <input type="hidden" name="current_section" value="search-department">
    
    <select name="department_name" class="p-2 border rounded w-full" required>
  <option value="">-- Select Department --</option>
  <?php foreach ($departments as $dept): ?>
    <option value="<?= htmlspecialchars($dept['department_name']) ?>">
      <?= htmlspecialchars($dept['department_name']) ?>
    </option>
  <?php endforeach; ?>
</select>
    
    <button type="submit" name="search_by_department" class="bg-blue-500 text-white px-4 py-2 rounded">
      Search
    </button>
  </form>

  <?php if ($msg_dept_search): ?>
    <p class="mt-2 text-sm text-yellow-700"><?= htmlspecialchars($msg_dept_search) ?></p>
  <?php endif; ?>

  <?php if ($data_dept_students): ?>
    <div class="overflow-x-auto mt-4">
      <table class="min-w-full border border-gray-300 bg-white">
        <thead class="bg-gray-200">
          <tr>
            <?php foreach(array_keys($data_dept_students[0]) as $col): ?>
              <th class="border px-4 py-2"><?= htmlspecialchars($col) ?></th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach($data_dept_students as $row): ?>
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
<footer class="bg-[#174B6C] text-white text-center py-3 text-sm shadow-inner">
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