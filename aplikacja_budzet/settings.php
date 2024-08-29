<?php

session_start();

// Check if the user is logged in; if not, redirect to the homepage
if (!isset($_SESSION['logged_in'])) {
    header('Location: index.php');
    exit();
}

?>

<!DOCTYPE html>
<html lang="pl">

<head>

    <meta charset="utf-8">
    <title>Zarządzanie budżetem domowym</title>
    <meta name="description" content="Aplikacja do zarządzania budżetem domowym.">
    <meta name="keywords" content="przychody, wydatki, budżet, dom">

    <meta http-equiv="X-Ua-Compatible" content="IE=edge,chrome=1">

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/fontello.css" type="text/css" />
    <link href='http://fonts.googleapis.com/css?family=Lato|Josefin+Sans&subset=latin,latin-ext' rel='stylesheet' type='text/css'>

</head>

<body>
    <div id="container">

        <header>

            <div id="logo">
                Zarządzanie budżetem domowym
            </div>

            <nav id="topnav">
                <ul class="menu">
                    <li><a href="home.php">Strona główna</a></li>
                    <li><a href="income.php">Dodaj przychód</a></li>
                    <li><a href="expense.php">Dodaj wydatek</a></li>
                    <li><a href="balance.php">Przeglądaj bilans</a></li>
                    <li><a href="settings.php">Ustawienia</a></li>
                    <li><a href="logout.php">Wyloguj się</a></li>
                </ul>
            </nav>

        </header>

        <main>

            <div class="container1">
                <div class="tile1">
                    <a href="income.php" class="tilelink"><i class="icon-plus-outline"></i><br /><br />Dodaj
                        przychód</a>
                </div>
                <div class="tile2">
                    <a href="expense.php" class="tilelink"><i class="icon-minus-outline"></i><br /><br />Dodaj
                        wydatek</a>
                </div>
            </div>
            <div class="container1">
                <div class="tile3">
                    <a href="balance.php" class="tilelink"><i class="icon-chart-bar"></i><br /><br />Przeglądaj
                        bilans</a>
                </div>
            </div>
            <div class="container1">
                <div class="tile4">
                    <a href="settings.php" class="tilelink"><i class="icon-cogs"></i><br /><br />Ustawienia</a>
                </div>
                <div class="tile5">
                    <a href="logout.php" class="tilelink"><i class="icon-off"></i><br /><br />Wyloguj się</a>
                </div>
            </div>

        </main>

    </div>

</body>

</html>