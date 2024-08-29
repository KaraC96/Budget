<?php

session_start();

if (!isset($_SESSION['successful_registration'])) {
	header('Location: index.php');
	exit();
} else {
	unset($_SESSION['successful_registration']);
}

//Usuwanie zmiennych pamiętających wartości wpisane do formularza
if (isset($_SESSION['fr_email'])) unset($_SESSION['fr_email']);
if (isset($_SESSION['fr_password1'])) unset($_SESSION['fr_password1']);
if (isset($_SESSION['fr_password2'])) unset($_SESSION['fr_password2']);
if (isset($_SESSION['fr_age'])) unset($_SESSION['fr_age']);
if (isset($_SESSION['fr_country'])) unset($_SESSION['fr_country']);
if (isset($_SESSION['fr_url'])) unset($_SESSION['fr_url']);


//Usuwanie błędów rejestracji
if (isset($_SESSION['e_email'])) unset($_SESSION['e_email']);
if (isset($_SESSION['e_age'])) unset($_SESSION['e_age']);
if (isset($_SESSION['e_password'])) unset($_SESSION['e_password']);

?>

<!DOCTYPE html>
<html lang="pl">

<head>
	<meta charset="utf-8">
	<meta http-equiv="X-Ua-Compatible" content="IE=edge,chrome=1">

	<title>Zarządzanie budżetem domowym</title>

	<meta name="description" content="Aplikacja do zarządzania budżetem domowym.">
	<meta name="keywords" content="przychody, wydatki, budżet, dom">

	<link rel="stylesheet" href="style.css" type="text/css" />
	<link rel="stylesheet" href="css/fontello.css" type="text/css" />
	<link href='https://fonts.googleapis.com/css?family=Lato|Josefin+Sans&subset=latin,latin-ext' rel='stylesheet' type='text/css'>

</head>

<body>

	<div id="container">

		<header>

			<div id="logo">
				<h1>
					Zarządzanie budżetem domowym
			</div>
			</h1>


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
				<br>
				<p>Dziękujemy za rejestrację w serwisie! Możesz już zalogować się na swoje konto!</p>

			</div>
		</main>

	</div>

</body>

</html>