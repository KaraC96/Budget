<?php
session_start();

if (!isset($_SESSION['logged_in'])) {
    header('Location: index.php');
    exit();
}

require_once "connect.php";
mysqli_report(MYSQLI_REPORT_STRICT);

// Initialize variables to store user input and errors
$success_message = "";
$errors = [];

$amount = '';
$date_of_expense = date('Y-m-d'); // Set to today's date by default
$expense_category = '';
$payment_method = '';
$expense_comment = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $everything_OK = true;

    $user_id = $_SESSION['id'];

    // Validate amount
    $amount = $_POST['amount'] ?? '';

    // Replace comma with a period if the user used a comma as the decimal separator
    $amount = str_replace(',', '.', $amount);

    // Check if the amount is a valid decimal number with max 8 digits and 2 decimal places
    if (!preg_match('/^\d{1,6}(\.\d{1,2})?$/', $amount)) {
        $everything_OK = false;
        $errors[] = "Wpisz poprawną kwotę!";
    }

    // Validate date
    $date_of_expense = $_POST['date_of_expense'] ?? date('Y-m-d'); // Default to today's date if not set
    $date_format = 'Y-m-d';
    $date_object = DateTime::createFromFormat($date_format, $date_of_expense);
    if (!$date_object || $date_object->format($date_format) !== $date_of_expense) {
        $everything_OK = false;
        $errors[] = "Podaj poprawną datę!";
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
    $expense_comment = $_POST['expense_comment'] ?? '';
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

            // Check category and retrieve its id
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

            // Check payment method and retrieve its id
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
            $insert_expense->bind_param('iiidss', $user_id, $expense_category_assigned_to_user_id, $payment_method_assigned_to_user_id, $amount, $date_of_expense, $expense_comment);
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
                        <input id="amount" type="text" name="amount" placeholder="Kwota wydatku" value="<?php echo htmlspecialchars($amount); ?>" />
                    </div>

                    <div class="input-group">
                        <label for="date_of_expense">Data:</label>
                        <input id="date_of_expense" type="date" name="date_of_expense" placeholder="Data wydatku" value="<?php echo htmlspecialchars($date_of_expense); ?>" />
                    </div>

                    <br>
                    <label>Kategoria wydatku:</br></br></label>
                    <div class="checkbox-container">
                        <!-- Dynamic Radio Buttons with selected check -->
                        <?php
                        $categories = ["Food" => "Jedzenie", "Travel" => "Podróż", "Clothes" => "Ubrania", "Presents" => "Prezenty", "City transport" => "Transport publiczny", "Debt repayment" => "Spłata długu", "For pension" => "Na emeryturę", "Recreation" => "Rekreacja", "Health" => "Zdrowie", "Hygiene" => "Higiena", "Savings" => "Oszczędności", "Kids" => "Dzieci", "Fuel" => "Paliwo", "Fun" => "Zabawa", "Taxi" => "Taxi", "Another" => "Inne"];
                        foreach ($categories as $value => $label):
                        ?>
                            <div>
                                <input type="radio" id="<?php echo $value; ?>" name="expense_category" value="<?php echo $value; ?>" <?php if ($expense_category === $value) echo 'checked'; ?>>
                                <label for="<?php echo $value; ?>"><?php echo $label; ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <br>

                    <label>Metoda płatności:</br></br></label>
                    <div class="checkbox-container">
                        <!-- Dynamic Radio Buttons with selected check -->
                        <?php
                        $payment_methods = ["Credit card" => "Karta kredytowa", "Cash" => "Gotówka", "Debit card" => "Karta debetowa"];
                        foreach ($payment_methods as $value => $label):
                        ?>
                            <div>
                                <input type="radio" id="<?php echo $value; ?>" name="payment_method" value="<?php echo $value; ?>" <?php if ($payment_method === $value) echo 'checked'; ?>>
                                <label for="<?php echo $value; ?>"><?php echo $label; ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <label for="expense_comment"></br>Komentarz:</br></br></label>
                    <textarea id="expense_comment" type="text" name="expense_comment" rows="4" placeholder="Dodaj komentarz do wydatku (opcjonalnie)"><?php echo htmlspecialchars($expense_comment); ?></textarea>

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