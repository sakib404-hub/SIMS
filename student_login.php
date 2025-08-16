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

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $roll_number = trim($_POST['roll-number']);

    $stmt = $conn->prepare("SELECT student_id FROM students WHERE roll_number = ?");
    $stmt->bind_param("s", $roll_number);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Roll number found → redirect to dashboard with ID
        $stmt->bind_result($student_id);
        $stmt->fetch();
        header("Location: student_dashboard.php?id=" . $student_id);
        exit();
    } else {
        $error = "Invalid Roll Number. Please try again.";
    }

    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Student Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/x-icon" href="photos/logo.png" />
  </head>
  <body
    class="bg-gradient-to-br from-blue-50 via-gray-100 to-blue-100 min-h-screen flex flex-col font-sans"
  >
    <!-- Header -->
    <header
      class="bg-blue-700 text-white py-5 shadow-lg text-center text-xl font-semibold"
    >
      🎓 Student Information Management System - Login
    </header>

    <!-- Login Form -->
    <main class="flex-1 flex items-center justify-center p-6">
      <div
        class="bg-white/90 backdrop-blur-md shadow-xl rounded-xl p-8 w-full max-w-md"
      >
        <h2 class="text-2xl font-bold text-blue-700 text-center mb-6">
          🔐 Student Login
        </h2>
        <?php if (!empty($error)): ?>
          <div class="mb-4 text-red-600 font-semibold text-center">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>
        <form method="POST" action="">
          <div class="mb-4">
            <label
              for="roll-number"
              class="block text-gray-700 font-semibold mb-2"
              >Roll Number</label
            >
            <input
              type="text"
              id="roll-number"
              name="roll-number"
              required
              class="w-full px-4 py-2 border border-blue-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Enter your roll number (e.g. 1001)"
            />
          </div>
          <button
            type="submit"
            class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition"
          >
            Login
          </button>
        </form>
        <div class="mt-4 text-center">
          <a href="index.php" class="text-blue-600 hover:underline text-sm"
            >← Back to Home</a
          >
        </div>
      </div>
    </main>

    <!-- Footer -->
    <footer
      class="bg-blue-700 text-white text-center py-3 text-sm shadow-inner"
    >
      © 2025 SIMS
    </footer>
  </body>
</html>
