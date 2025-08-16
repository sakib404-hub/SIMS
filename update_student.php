<?php
include 'db_connect.php';
$message = "";

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Update Student</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans min-h-screen flex flex-col">

<header class="bg-blue-700 text-white py-4 px-6 flex justify-between items-center shadow-md">
  <h1 class="text-lg font-semibold">Update Student</h1>
  <a href="admin.php" class="bg-gray-500 px-4 py-2 rounded-lg hover:bg-gray-600">Back to Dashboard</a>
</header>

<main class="flex-1 p-6">
  <?php if ($message): ?>
    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-4"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>
  
  <form method="post" class="space-y-3">
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
</main>

<footer class="bg-blue-700 text-white text-center py-3 text-sm shadow-inner">
  © 2025 SIMS
</footer>
</body>
</html>
