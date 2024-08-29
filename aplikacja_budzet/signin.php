<?php
session_start();

// Check if the 'login' and 'password' fields are set
if (!isset($_POST['login']) || !isset($_POST['password'])) {
	header('Location: index.php'); // Redirect to the index page if they are not set
	exit();
}

require_once "connect.php"; // Include the database connection file

// Create a database connection using a try-catch block for better error handling
try {
	$connection = new mysqli($host, $db_user, $db_password, $db_name);

	// Check for a connection error
	if ($connection->connect_errno) {
		throw new Exception($connection->connect_error);
	}

	$login = $_POST['login'];
	$password = $_POST['password'];

	// Sanitize user input
	$login = htmlentities($login, ENT_QUOTES, "UTF-8");

	// Prepare SQL statement to prevent SQL injection
	$stmt = $connection->prepare("SELECT * FROM users WHERE email = ?");
	if (!$stmt) {
		throw new Exception($connection->error);
	}

	// Bind parameters and execute the statement
	$stmt->bind_param('s', $login);
	$stmt->execute();
	$result = $stmt->get_result();

	$num_users = $result->num_rows; // Check the number of rows returned

	if ($num_users > 0) {
		$row = $result->fetch_assoc();

		// Verify the password
		if (password_verify($password, $row['password'])) {
			$_SESSION['logged_in'] = true; // Set the session variable to indicate the user is logged in
			$_SESSION['id'] = $row['id'];

			unset($_SESSION['error']); // Clear any previous error message
			header('Location: home.php'); // Redirect to the home page
			exit();
		} else {
			$_SESSION['error'] = '<span style="color:red">Nieprawidłowy login lub hasło!</span>';
			header('Location: login.php'); // Redirect to the login page with an error
			exit();
		}
	} else {
		$_SESSION['error'] = '<span style="color:red">Nieprawidłowy login lub hasło!</span>';
		header('Location: login.php'); // Redirect to the login page with an error
		exit();
	}

	// Free result set and close statement
	$result->free();
	$stmt->close();
	$connection->close();
} catch (Exception $e) {
	echo 'Błąd serwera! Przepraszamy za niedogodności. Proszę spróbować później.';
	error_log($e->getMessage()); // Log the error message for debugging
}
