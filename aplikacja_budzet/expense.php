<?php
session_start();

if (!isset($_SESSION['logged_in'])) {
    header('Location: index.php');
    exit();
}

require_once "connect.php";
mysqli_report(MYSQLI_REPORT_STRICT);

$success_message = "";
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $everything_OK = true;

    $user_id = $_SESSION['id'];

    // Validate amount
    $amount = $_POST['amount'];

    // Replace comma with a period if the user used a comma as the decimal separator
    $amount = str_replace(',', '.', $amount);

    // Check if the amount is a valid decimal number with max 8 digits and 2 decimal places
    if (!preg_match('/^\d{1,6}(\.\d{1,2})?$/', $amount)) {
        $everything_OK = false;
        $errors[] = "Wpisz poprawną kwotę!";
    }

    // Validate date
    $date_of_expense = $_POST['date_of_expense'];
    if (!DateTime::createFromFormat('Y-m-d', $date_of_expense)) {
        $everything_OK = false;
        $errors[] = "Podaj datę!";
    }

    // Validate category
    $expense_category = $_POST['expense_category'] ?? '';
    if (empty($expense_category)) {
        $everything_OK = false;
        $errors[] = "Wybierz kategorię wydatku!";
    }

    // Validation of payment methods
    $payment_method = $_POST['payment_method'] ?? '';
    if (empty($payment_method)) {
        $everything_OK = false;
        $errors[] = "Wybierz metodę płatności!";
    }

    // Validate comment
    $expense_comment = $_POST['expense_comment'];
    if (strlen($expense_comment) > 100) {
        $everything_OK = false;
        $errors[] = "Komentarz nie może przekraczać 100 znaków!";
    }

    if ($everything_OK) {
        try {
            $connection = new mysqli($host, $db_user, $db_password, $db_name);
            if ($connection->connect_errno != 0) {
                throw new Exception(mysqli_connect_errno());
            }

            $expense_category_assigned_to_user_id = 0;
            $category_query = $connection->prepare("SELECT id FROM expenses_category_assigned_to_users WHERE name = ? AND user_id = ?");
            $category_query->bind_param('si', $expense_category, $user_id);
            $category_query->execute();
            $category_result = $category_query->get_result();

            if ($category_result->num_rows > 0) {
                $category_row = $category_result->fetch_assoc();
                $expense_category_assigned_to_user_id = $category_row['id'];
            } else {
                throw new Exception("Wybrana kategoria nie istnieje.");
            }

            $payment_method_assigned_to_user_id = 0;
            $payment_method_query = $connection->prepare("SELECT id FROM payment_methods_assigned_to_users WHERE name = ? AND user_id = ?");
            $payment_method_query->bind_param('si', $payment_method, $user_id);
            $payment_method_query->execute();
            $payment_method_result = $payment_method_query->get_result();

            if ($payment_method_result->num_rows > 0) {
                $payment_method_row = $payment_method_result->fetch_assoc();
                $payment_method_assigned_to_user_id = $payment_method_row['id'];
            } else {
                throw new Exception("Wybrana metoda płatności nie istnieje.");
            }

            // Add expense to the database
            $insert_expense = $connection->prepare("INSERT INTO expenses (user_id, expense_category_assigned_to_user_id, payment_method_assigned_to_user_id, amount, date_of_expense, expense_comment) VALUES (?, ?, ?, ?, ?, ?)");
            $insert_expense->bind_param('iiidss', $user_id, $expense_category_assigned_to_user_id, $payment_method_assigned_to_user_id, $amount, $date_of_expense, $expense_comment); // 'd' for double
            if ($insert_expense->execute()) {
                $success_message = "Wydatek został dodany pomyślnie!";
            } else {
                throw new Exception("Błąd przy dodawaniu wydatku: " . $connection->error);
            }

            $connection->close();
        } catch (Exception $e) {
            $errors[] = 'Błąd serwera! Przepraszamy za niedogodności i prosimy o spróbowanie później. Informacja developerska: ' . $e->getMessage();
        }
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
    <link rel="stylesheet" href="main2.css">
    <link rel="stylesheet" href="css/fontello.css" type="text/css" />
    <link href='http://fonts.googleapis.com/css?family=Lato|Josefin+Sans&subset=latin,latin-ext' rel='stylesheet' type='text/css'>
    <style>
        label {
            font-weight: bold;
            margin-right: 10px;
        }

        input[type="text"],
        input[type="date"],
        textarea {
            padding: 10px;
            margin-bottom: 10px;
            border: none;
            border-radius: 5px;
            font-family: 'Lato', sans-serif;
        }

        .input-group {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .input-group label {
            margin-right: 10px;
            min-width: 80px;
        }

        .input-group input {
            flex: 1;
        }

        .checkbox-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 16px;
        }

        .checkbox-container div {
            display: flex;
            align-items: center;
            flex: 1 1 23%;
            box-sizing: border-box;
            margin-bottom: 10px;
        }

        textarea {
            width: 100%;
            box-sizing: border-box;
            font-family: 'Lato', sans-serif;
        }

        .buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .buttons input {
            width: 48%;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .buttons input:hover {
            background-color: #444;
        }

        .success-message {
            color: green;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .error-message {
            color: red;
            font-weight: bold;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <div id="container">
        <header>
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
            <div id="content1">
                <!-- Display success message if expense is added successfully -->
                <?php if (!empty($success_message)): ?>
                    <div class="success-message"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <!-- Display validation errors -->
                <?php if (!empty($errors)): ?>
                    <div class="error-message">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form autocomplete="off" method="post">
                    <div class="title">Dodaj wydatek</br></br></div>

                    <div class="input-group">
                        <label for="amount">Kwota:</label>
                        <input id="amount" type="text" name="amount" placeholder="Kwota wydatku" />
                    </div>

                    <div class="input-group">
                        <label for="date_of_expense">Data:</label>
                        <input id="date_of_expense" type="date" name="date_of_expense" placeholder="Data wydatku" />
                    </div>

                    <br>
                    <label>Kategoria wydatku:</br></br></label>
                    <div class="checkbox-container">
                        <div><input type="radio" id="food" name="expense_category" value="Food"><label for="food">Jedzenie</label></div>
                        <div><input type="radio" id="travel" name="expense_category" value="Travel"><label for="travel">Podróż</label></div>
                        <div><input type="radio" id="clothes" name="expense_category" value="Clothes"><label for="clothes">Ubrania</label></div>
                        <div><input type="radio" id="presents" name="expense_category" value="Presents"><label for="presents">Prezenty</label></div>
                        <div><input type="radio" id="city_transport" name="expense_category" value="City transport"><label for="city_transport">Transport publiczny</label></div>
                        <div><input type="radio" id="debt_repayment" name="expense_category" value="Debt repayment"><label for="debt_repayment">Spłata długu</label></div>
                        <div><input type="radio" id="for_pension" name="expense_category" value="For pension"><label for="for_pension">Na emeryturę</label></div>
                        <div><input type="radio" id="recreation" name="expense_category" value="Recreation"><label for="recreation">Rekreacja</label></div>
                        <div><input type="radio" id="health" name="expense_category" value="Health"><label for="health">Zdrowie</label></div>
                        <div><input type="radio" id="hygiene" name="expense_category" value="Hygiene"><label for="hygiene">Higiena</label></div>
                        <div><input type="radio" id="savings" name="expense_category" value="Savings"><label for="savings">Oszczędności</label></div>
                        <div><input type="radio" id="kids" name="expense_category" value="Kids"><label for="kids">Dzieci</label></div>
                        <div><input type="radio" id="fuel" name="expense_category" value="Fuel"><label for="fuel">Paliwo</label></div>
                        <div><input type="radio" id="fun" name="expense_category" value="Fun"><label for="fun">Zabawa</label></div>
                        <div><input type="radio" id="taxi" name="expense_category" value="Taxi"><label for="taxi">Taxi</label></div>
                        <div><input type="radio" id="another" name="expense_category" value="Another"><label for="another">Inne</label></div>
                    </div>
                    <br>

                    <label>Metoda płatności:</br></br></label>
                    <div class="checkbox-container">
                        <div><input type="radio" id="credit_card" name="payment_method" value="Credit card"><label for="credit_card">Karta kredytowa</label></div>
                        <div><input type="radio" id="cash" name="payment_method" value="Cash"><label for="cash">Gotówka</label></div>
                        <div><input type="radio" id="debit_card" name="payment_method" value="Debit card"><label for="debit_card">Karta debetowa</label></div>
                    </div>

                    <label for="expense_comment"></br>Komentarz:</br></br></label>
                    <textarea id="expense_comment" type="text" name="expense_comment" rows="4" placeholder="Dodaj komentarz do wydatku (opcjonalnie)"></textarea>

                    <div class="buttons">
                        <input type="submit" value="Dodaj wydatek">
                        <input type="reset" value="Anuluj">
                    </div>
                </form>
                </br>
            </div>
        </main>
    </div>
</body>

</html>