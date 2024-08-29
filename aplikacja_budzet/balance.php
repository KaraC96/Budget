<?php
session_start();

if (!isset($_SESSION['logged_in'])) {
    header('Location: index.php');
    exit();
}

require_once "connect.php";
mysqli_report(MYSQLI_REPORT_STRICT);

$show_balance = false; // Flag to determine if the balance should be displayed

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $everything_OK = true;
    $errors = [];

    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    // Validate dates
    if (!DateTime::createFromFormat('Y-m-d', $start_date) || !DateTime::createFromFormat('Y-m-d', $end_date)) {
        $everything_OK = false;
        $errors[] = "Data musi być w formacie RRRR-MM-DD!";
    }

    if ($everything_OK) {
        $show_balance = true; // Set flag to true to show balance

        try {
            $connection = new mysqli($host, $db_user, $db_password, $db_name);
            if ($connection->connect_errno != 0) {
                throw new Exception(mysqli_connect_error());
            }

            // SQL query to fetch and sum incomes by category
            $income_query = $connection->prepare("
                SELECT ic.name AS category_name, SUM(i.amount) AS total_amount
                FROM incomes i
                JOIN incomes_category_assigned_to_users ic ON i.income_category_assigned_to_user_id = ic.id
                WHERE i.date_of_income BETWEEN ? AND ?
                GROUP BY ic.name
                ORDER BY ic.name
            ");

            if (!$income_query) {
                throw new Exception("Błąd przygotowania zapytania do przychodów: " . $connection->error);
            }

            $income_query->bind_param('ss', $start_date, $end_date);
            $income_query->execute();
            $income_result = $income_query->get_result();

            // SQL query to fetch and sum expenses by category
            $expense_query = $connection->prepare("
                SELECT ec.name AS category_name, SUM(e.amount) AS total_amount
                FROM expenses e
                JOIN expenses_category_assigned_to_users ec ON e.expense_category_assigned_to_user_id = ec.id
                WHERE e.date_of_expense BETWEEN ? AND ?
                GROUP BY ec.name
                ORDER BY ec.name
            ");

            if (!$expense_query) {
                throw new Exception("Błąd przygotowania zapytania do wydatków: " . $connection->error);
            }

            $expense_query->bind_param('ss', $start_date, $end_date);
            $expense_query->execute();
            $expense_result = $expense_query->get_result();

            // Prepare results for display
            $incomes = [];
            $total_income = 0;

            while ($row = $income_result->fetch_assoc()) {
                $incomes[] = $row;
                $total_income += $row['total_amount'];
            }

            $expenses = [];
            $total_expense = 0;

            while ($row = $expense_result->fetch_assoc()) {
                $expenses[] = $row;
                $total_expense += $row['total_amount'];
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
    <title>Bilans budżetu</title>
    <meta name="description" content="Bilans budżetu domowego">
    <meta name="keywords" content="bilans, budżet, przychody, wydatki">
    <meta http-equiv="X-Ua-Compatible" content="IE=edge,chrome=1">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="main2.css">
    <link rel="stylesheet" href="css/fontello.css" type="text/css" />
    <link href='http://fonts.googleapis.com/css?family=Lato|Josefin+Sans&subset=latin,latin-ext' rel='stylesheet' type='text/css'>
    <style>
        input[type="date"],
        input[type="submit"],
        textarea {
            padding: 10px;
            margin-bottom: 10px;
            border: none;
            border-radius: 5px;
            font-family: 'Lato', sans-serif;
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
                <form method="post" autocomplete="off">
                    <div id="term">
                        <label for="start_date">Data początkowa:</label>
                        <input id="start_date" type="date" name="start_date" required /> </br>
                        <label for="end_date">Data końcowa:</label>
                        <input id="end_date" type="date" name="end_date" required /> </br>
                        <div class="buttons">
                            <input type="submit" value="Pokaż bilans">
                        </div>
                    </div>
                </form>

                <?php if ($show_balance): ?>
                    <!-- Displaying selected date range -->
                    <div class="date_range">
                        <p><strong>Wybrany okres:</strong> <?php echo htmlspecialchars($start_date); ?> - <?php echo htmlspecialchars($end_date); ?></p>
                    </div>

                    <!-- Displaying incomes -->
                    <div class="title_table">Przychody</div>
                    <table id="income">
                        <tr>
                            <th>Kategoria</th>
                            <th>Kwota</th>
                        </tr>

                        <?php foreach ($incomes as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                <td><?php echo number_format($row['total_amount'], 2, ',', ' '); ?></td>
                            </tr>
                        <?php endforeach; ?>

                        <tr>
                            <td><strong>Razem:</strong></td>
                            <td><strong><?php echo number_format($total_income, 2, ',', ' '); ?></strong></td>
                        </tr>
                    </table>

                    <!-- Displaying expenses -->
                    <div class="title_table">Wydatki</div>
                    <table id="expense">
                        <tr>
                            <th>Kategoria</th>
                            <th>Kwota</th>
                        </tr>

                        <?php foreach ($expenses as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                <td><?php echo number_format($row['total_amount'], 2, ',', ' '); ?></td>
                            </tr>
                        <?php endforeach; ?>

                        <tr>
                            <td><strong>Razem:</strong></td>
                            <td><strong><?php echo number_format($total_expense, 2, ',', ' '); ?></strong></td>
                        </tr>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>

</html>