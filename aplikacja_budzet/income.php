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

// Initialize variables to store user input and errors
$amount = '';
$date_of_income = date('Y-m-d'); // Set to today's date by default
$income_category = '';
$income_comment = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $everything_OK = true;

    $user_id = $_SESSION['id'];

    // Assign POST data to variables
    $amount = $_POST['amount'] ?? '';
    $date_of_income = $_POST['date_of_income'] ?? date('Y-m-d'); // Use today's date if not set
    $income_category = $_POST['income_category'] ?? '';
    $income_comment = $_POST['income_comment'] ?? '';

    // Validate amount
    $amount = str_replace(',', '.', $amount);

    if (!preg_match('/^\d{1,6}(\.\d{1,2})?$/', $amount)) {
        $everything_OK = false;
        $errors[] = "Wpisz poprawną kwotę!";
    }

    // Validate date
    $date_format = 'Y-m-d';
    $date_object = DateTime::createFromFormat($date_format, $date_of_income);

    if (!$date_object || $date_object->format($date_format) !== $date_of_income) {
        $everything_OK = false;
        $errors[] = "Podaj poprawną datę!";
    } else {
        $date_of_income = $date_object->format($date_format);
    }

    // Validate category
    if (empty($income_category)) {
        $everything_OK = false;
        $errors[] = "Wybierz kategorię przychodu!";
    }

    // Validate comment
    if (strlen($income_comment) > 100) {
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
            $income_category_assigned_to_user_id = 0;
            $category_query = $connection->prepare("SELECT id FROM incomes_category_assigned_to_users WHERE name = ? AND user_id = ?");
            $category_query->bind_param('si', $income_category, $user_id);
            $category_query->execute();
            $category_result = $category_query->get_result();

            if ($category_result->num_rows > 0) {
                $category_row = $category_result->fetch_assoc();
                $income_category_assigned_to_user_id = $category_row['id'];
            } else {
                throw new Exception("Wybrana kategoria nie istnieje.");
            }

            // Add income to the database
            $insert_income = $connection->prepare("INSERT INTO incomes (user_id, income_category_assigned_to_user_id, amount, date_of_income, income_comment) VALUES (?, ?, ?, ?, ?)");
            $insert_income->bind_param('iisss', $user_id, $income_category_assigned_to_user_id, $amount, $date_of_income, $income_comment);
            if ($insert_income->execute()) {
                $success_message = "Przychód został dodany pomyślnie!";
            } else {
                throw new Exception("Błąd przy dodawaniu przychodu: " . $connection->error);
            }

            $connection->close();
        } catch (Exception $e) {
            echo '<span style="color:red;">Błąd serwera! Przepraszamy za niedogodności i prosimy o spróbowanie później.</span>';
            echo '<br />Informacja developerska: ' . $e->getMessage();
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
            margin-bottom: 10px;
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
                <!-- Display success message -->
                <?php if (!empty($success_message)): ?>
                    <div class="success-message"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <!-- Display errors -->
                <?php if (!empty($errors)): ?>
                    <div class="error-message">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form autocomplete="off" method="post">
                    <div class="title">Dodaj przychód</br></br></div>

                    <div class="input-group">
                        <label for="amount">Kwota:</label>
                        <input id="amount" type="text" name="amount" placeholder="Kwota przychodu" value="<?php echo htmlspecialchars($amount); ?>" />
                    </div>

                    <div class="input-group">
                        <label for="date_of_income">Data:</label>
                        <input id="date_of_income" type="date" name="date_of_income" placeholder="Data przychodu" value="<?php echo htmlspecialchars($date_of_income); ?>" />
                    </div>

                    <br>
                    <label>Kategoria przychodu:</br></br></label>
                    <div class="checkbox-container">
                        <div><input type="radio" id="paycheck" name="income_category" value="Paycheck" <?php if ($income_category == 'Paycheck') echo 'checked'; ?>><label for="paycheck">Wypłata</label></div>
                        <div><input type="radio" id="investments" name="income_category" value="Investments" <?php if ($income_category == 'Investments') echo 'checked'; ?>><label for="investments">Inwestycje</label></div>
                        <div><input type="radio" id="passiveIncome" name="income_category" value="Passive income" <?php if ($income_category == 'Passive income') echo 'checked'; ?>><label for="passiveIncome">Dochód pasywny</label></div>
                        <div><input type="radio" id="another" name="income_category" value="Another" <?php if ($income_category == 'Another') echo 'checked'; ?>><label for="another">Inne</label></div>
                    </div>

                    <label for="income_comment"></br>Komentarz:</br></br></label>
                    <textarea id="income_comment" type="text" name="income_comment" rows="4" placeholder="Dodaj komentarz do przychodu (opcjonalnie)"><?php echo htmlspecialchars($income_comment); ?></textarea>

                    <div class="buttons">
                        <input type="submit" value="Dodaj przychód">
                        <input type="reset" value="Anuluj">
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>

</html>