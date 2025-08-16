<?php
// Database connection (in case we use DB later for admin accounts)
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "student_information_msdb"; 

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$usernameError = "";
$passwordError = "";

// Handle login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $inputUser = trim($_POST['username']);
    $inputPass = trim($_POST['password']);

    // Hardcoded credentials (replace with DB query if needed)
    $adminUser = "sakib404-hub";
    $adminPass = "26297@kib";

    if ($inputUser !== $adminUser) {
        $usernameError = "Invalid username";
    }

    if ($inputPass !== $adminPass) {
        $passwordError = "Invalid password";
    }

    if (empty($usernameError) && empty($passwordError)) {
        header("Location: admin.php");
        exit();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin - Login page</title>
    <link rel="icon" type="image/x-icon" href="photos/logo.png" />
    <link rel="stylesheet" href="Styles/admin.css" />
    <script src="https://cdn.tailwindcss.com"></script>
  </head>
  <body
    class="bg-gradient-to-br from-blue-50 via-gray-100 to-blue-100 font-sans min-h-screen flex flex-col"
  >
    <!-- Header -->
    <header
      class="bg-blue-700 text-white py-5 shadow-lg text-center text-xl font-semibold"
    >
      🛠️ Student Information Management System - Admin Login
    </header>

    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center px-4">
      <div
        class="container bg-white/90 backdrop-blur-md p-8 rounded-2xl shadow-lg max-w-md w-full"
      >
        <h1 class="text-2xl font-bold text-blue-700 mb-6">Admin Login</h1>

        <!-- Login Form -->
        <form method="POST" class="space-y-4">
          <div class="form-group">
            <label for="username" class="block font-medium text-gray-700"
              >Username</label
            >
            <input
              type="text"
              id="username"
              name="username"
              value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
              placeholder="username"
              required
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500"
            />
            <?php if (!empty($usernameError)): ?>
              <div class="error-message text-red-500"><?= $usernameError ?></div>
            <?php endif; ?>
          </div>

          <div class="form-group">
            <label for="password" class="block font-medium text-gray-700"
              >Password</label
            >
            <input
              type="password"
              id="password"
              name="password"
              placeholder="password"
              required
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:border-blue-500"
            />
            <?php if (!empty($passwordError)): ?>
              <div class="error-message text-red-500"><?= $passwordError ?></div>
            <?php endif; ?>
          </div>

          <button
            type="submit"
            class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition"
          >
            Login
          </button>
        </form>

        <a href="index.php" class="block mt-4 text-blue-600 hover:underline"
          >← Back to Home</a
        >
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
