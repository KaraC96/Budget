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
    <title>Zarządzanie budżetem domowym</title>
    <meta name="description" content="Aplikacja do zarządzania budżetem domowym.">
    <meta name="keywords" content="przychody, wydatki, budżet, dom">
    <meta http-equiv="X-Ua-Compatible" content="IE=edge,chrome=1">

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="css/fontello.css" type="text/css" />
    <link href='https://fonts.googleapis.com/css?family=Lato|Josefin+Sans&subset=latin,latin-ext' rel='stylesheet' type='text/css'>

    <style>
        form {
            background: linear-gradient(to left,
                    rgba(255, 255, 255, 0.5),
                    rgba(255, 255, 255, 0.5));
            width: 200vw;
            max-width: 300px;
            padding: 36px 24px;
            border-radius: 15px;
            box-shadow: 0px 8px 24px 0 rgba(54, 54, 54);
            font-size: 20px;
            text-align: center;
            margin-left: auto;
            margin-right: auto;
        }

        .label_conteiner {
            margin-bottom: 32px;
            position: relative;
        }

        input,
        button {
            background: linear-gradient(to left,
                    rgba(255, 255, 255, 0.5),
                    rgba(255, 255, 255, 0.5));
            border: none;
            border-radius: 18px;
            box-shadow: 0px 8px 24px 0 rgb(95, 94, 94);
            padding: 12px 24px;
            font-size: 24px;
            color: #1b4864;
        }

        input {
            width: 250px;
        }

        label {
            color: rgba(70, 86, 116, 0.5);
            cursor: text;
            left: 24px;
            opacity: 1;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
        }

        input:focus+label,
        input:not(:placeholder-shown)+label {
            left: 200px;
            opacity: 0;
        }
    </style>

</head>

<body>
    <div id="container">
        <header>
            <nav id="topnav">
                <ul class="menu">
                    <li><a href="index.php">Strona główna</a></li>
                    <li><a href="login.php">Logowanie</a></li>
                    <li><a href="register.php">Rejestracja</a></li>
                </ul>
            </nav>
        </header>

        <main>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="error">
                    <?php echo $_SESSION['error']; ?>
                </div>
                <?php unset($_SESSION['error']); // Clear the error after displaying 
                ?>
            <?php endif; ?>

            <div id="content">
                <form action="signin.php" method="post" id="log" autocomplete="off">
                    <div class="label_conteiner">
                        <i class="icon-user"></i>
                    </div>
                    <div class="label_conteiner">
                        <input id="user" type="text" placeholder=" " name="login" />
                        <label for="user">Nazwa Użytkownika</label>
                    </div>
                    <div class="label_conteiner">
                        <input id="password" type="password" placeholder=" " name="password" />
                        <label for="password">Hasło</label>
                    </div>
                    <button type="submit">Zaloguj się</button>
                </form>
            </div>
        </main>
    </div>
</body>

</html>