<?php

session_start();

// Check if the user is logged in
if ((isset($_SESSION['logged_in'])) && ($_SESSION['logged_in'] == true)) {
	header('Location: home.php'); // Redirect to the home page if already logged in
	exit();
}

?>

<!DOCTYPE html>
<html lang="pl">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

	<title>Zarządzanie budżetem domowym</title>

	<meta name="description" content="Aplikacja do zarządzania budżetem domowym.">
	<meta name="keywords" content="przychody, wydatki, budżet, dom">

	<link rel="stylesheet" href="style.css" type="text/css">
	<link rel="stylesheet" href="css/fontello.css" type="text/css">
	<link href="https://fonts.googleapis.com/css?family=Lato|Josefin+Sans&subset=latin,latin-ext" rel="stylesheet" type="text/css">
</head>

<body>
	<div id="container">
		<header>
			<div id="logo">
				<h1>Zarządzanie budżetem domowym</h1>
			</div>

			<nav id="topnav">
				<ul class="menu">
					<li><a href="index.php">Strona główna</a></li>
					<li><a href="login.php">Logowanie</a></li>
					<li><a href="register.php">Rejestracja</a></li>
				</ul>
			</nav>
		</header>

		<main>
			<div id="content">
				<h1>Gdzie są moje pieniądze?</h1>
				<p>Zacznij zarządzać budżetem domowym. Pokaż innym, że potrafisz coś zaoszczędzić!</p>
			</div>
		</main>
	</div>
</body>

</html>