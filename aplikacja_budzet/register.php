<?php

session_start();

// Validate input data only if the form has been submitted
if (isset($_POST['email'])) {
    $everything_OK = true;

    // Validate email
    $email = $_POST['email'];
    $emailB = filter_var($email, FILTER_SANITIZE_EMAIL);

    if ((filter_var($emailB, FILTER_VALIDATE_EMAIL) == false) || ($emailB != $email)) {
        $everything_OK = false;
        $_SESSION['e_email'] = "Podaj poprawny adres e-mail!";
    }

    // Validate age
    $age = $_POST['age'];
    if (!is_numeric($age) || $age < 1 || $age > 100) {
        $everything_OK = false;
        $_SESSION['e_age'] = "Podaj poprawny wiek (liczba od 1 do 100)!";
    }

    // Assign and format the country
    $country = $_POST['country'];
    $formatted_country = ucwords(strtolower(trim($country)));
    $_SESSION['fr_country'] = $country;

    // Validate URL
    $url = $_POST['url'];
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        $everything_OK = false;
        $_SESSION['e_url'] = "Podaj poprawny adres URL!";
    }

    // Validate passwords
    $password1 = $_POST['password1'];
    $password2 = $_POST['password2'];

    if ((strlen($password1) < 8) || (strlen($password1) > 20)) {
        $everything_OK = false;
        $_SESSION['e_password'] = "Hasło musi posiadać od 8 do 20 znaków!";
    }

    if ($password1 != $password2) {
        $everything_OK = false;
        $_SESSION['e_password'] = "Podane hasła nie są identyczne!";
    }

    $password_hash = password_hash($password1, PASSWORD_DEFAULT);

    // Remember the entered data
    $_SESSION['fr_email'] = $email;
    $_SESSION['fr_age'] = $age;
    $_SESSION['fr_country'] = $country;
    $_SESSION['fr_url'] = $url;
    $_SESSION['fr_password1'] = $password1;
    $_SESSION['fr_password2'] = $password2;

    require_once "connect.php";
    mysqli_report(MYSQLI_REPORT_STRICT);

    try {
        $connection = new mysqli($host, $db_user, $db_password, $db_name);
        if ($connection->connect_errno != 0) {
            throw new Exception(mysqli_connect_errno());
        } else {
            // Check if the email already exists
            $result = $connection->query("SELECT id FROM users WHERE email='$email'");

            if (!$result) throw new Exception($connection->error);

            $num_emails = $result->num_rows;
            if ($num_emails > 0) {
                $everything_OK = false;
                $_SESSION['e_email'] = "Istnieje już konto przypisane do tego adresu e-mail!";
            }

            if ($everything_OK == true) {
                // Hooray, all tests passed, let's add the user to the database

                if ($connection->query("INSERT INTO users VALUES (NULL, '$email', '$password_hash',  '$age', '$formatted_country', '$url', NOW(), NOW())")) {
                    // Get the ID of the newly registered user
                    $user_id = $connection->insert_id;

                    // Add default income categories to the incomes_category_assigned_to_users table
                    $query = "
                        INSERT INTO incomes_category_assigned_to_users (user_id, name)
                        SELECT '$user_id', name
                        FROM incomes_category_default
                    ";

                    if (!$connection->query($query)) {
                        throw new Exception($connection->error);
                    }

                    // Add default expense categories to the expenses_category_assigned_to_users table
                    $query = "
                        INSERT INTO expenses_category_assigned_to_users (user_id, name)
                        SELECT '$user_id', name
                        FROM expenses_category_default
                    ";

                    if (!$connection->query($query)) {
                        throw new Exception($connection->error);
                    }
                    // Add default payment methods to the payment_methods_assigned_to_users table
                    $query = "
                        INSERT INTO payment_methods_assigned_to_users (user_id, name)
                        SELECT '$user_id', name
                        FROM payment_methods_default
                    ";

                    if (!$connection->query($query)) {
                        throw new Exception($connection->error);
                    }

                    $_SESSION['successful_registration'] = true;
                    header('Location: welcome.php');
                } else {
                    throw new Exception($connection->error);
                }
            }

            $connection->close();
        }
    } catch (Exception $e) {
        echo '<span style="color:red;">Błąd serwera! Przepraszamy za niedogodności i prosimy o rejestrację w innym terminie!</span>';
        echo '<br />Informacja developerska: ' . $e;
    }
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
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="css/fontello.css" type="text/css" />
    <link href='https://fonts.googleapis.com/css?family=Lato|Josefin+Sans&subset=latin,latin-ext' rel='stylesheet' type='text/css'>

    <style>
        .label_conteiner {
            margin-bottom: 20px;
        }

        .label_conteiner input {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
        }

        .label_conteiner label {
            display: block;
        }

        .error {
            color: red;
            margin-top: 5px;
            font-size: 0.875em;
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
            <div id="content">
                <form autocomplete="off" method="post">
                    <div class="label_conteiner">
                        <i class="icon-user-plus"></i>
                    </div>

                    <div class="label_conteiner">
                        <input id="email" type="text" placeholder=" " value="<?php echo isset($_SESSION['fr_email']) ? $_SESSION['fr_email'] : ''; ?>" name="email" />
                        <label for="email">Adres e-mail</label>
                        <?php if (isset($_SESSION['e_email'])): ?>
                            <div class="error"><?php echo $_SESSION['e_email'];
                                                unset($_SESSION['e_email']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="label_conteiner">
                        <input id="age" type="text" placeholder=" " value="<?php echo isset($_SESSION['fr_age']) ? $_SESSION['fr_age'] : ''; ?>" name="age" />
                        <label for="age">Wiek</label>
                        <?php if (isset($_SESSION['e_age'])): ?>
                            <div class="error"><?php echo $_SESSION['e_age'];
                                                unset($_SESSION['e_age']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="label_conteiner">
                        <input id="country" type="text" placeholder=" " value="<?php echo isset($_SESSION['fr_country']) ? $_SESSION['fr_country'] : ''; ?>" name="country" />
                        <label for="country">Kraj</label>
                    </div>

                    <div class="label_conteiner">
                        <input id="url" type="text" placeholder=" " value="<?php echo isset($_SESSION['fr_url']) ? $_SESSION['fr_url'] : ''; ?>" name="url" />
                        <label for="url">Social media</label>
                        <?php if (isset($_SESSION['e_url'])): ?>
                            <div class="error"><?php echo $_SESSION['e_url'];
                                                unset($_SESSION['e_url']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="label_conteiner">
                        <input id="password" type="password" placeholder=" " value="<?php echo isset($_SESSION['fr_password1']) ? $_SESSION['fr_password1'] : ''; ?>" name="password1" />
                        <label for="password">Hasło</label>
                        <?php if (isset($_SESSION['e_password'])): ?>
                            <div class="error"><?php echo $_SESSION['e_password'];
                                                unset($_SESSION['e_password']); ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="label_conteiner">
                        <input id="password2" type="password" placeholder=" " value="<?php echo isset($_SESSION['fr_password2']) ? $_SESSION['fr_password2'] : ''; ?>" name="password2" />
                        <label for="password2">Powtórz hasło</label>
                    </div>

                    <button>Zarejestruj się</button>
                </form>
            </div>
        </main>
    </div>
</body>

</html>