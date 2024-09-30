<?php
session_start();

if (!isset($_SESSION['logged_in'])) {
    header('Location: index.php');
    exit();
}

require_once "connect.php";
mysqli_report(MYSQLI_REPORT_STRICT);

$show_balance = false; // Flaga do określenia, czy bilans powinien być wyświetlony

// Inicjalizacja zmiennych
$start_date = '';
$end_date = '';
$period = 'current_month'; // Domyślny okres

// Funkcja do translacji kategorii na język polski
function translateCategory($categoryName)
{
    $translations = [
        'Paycheck' => 'Wypłata',
        'Investments' => 'Inwestycje',
        'Passive income' => 'Dochód pasywny',
        "Food" => "Jedzenie",
        "Travel" => "Podróż",
        "Clothes" => "Ubrania",
        "Presents" => "Prezenty",
        "City transport" => "Transport publiczny",
        "Debt repayment" => "Spłata długu",
        "For pension" => "Na emeryturę",
        "Recreation" => "Rekreacja",
        "Health" => "Zdrowie",
        "Hygiene" => "Higiena",
        "Savings" => "Oszczędności",
        "Kids" => "Dzieci",
        "Fuel" => "Paliwo",
        "Fun" => "Zabawa",
        "Taxi" => "Taxi"
    ];

    return $translations[$categoryName] ?? $categoryName; // Zwraca przetłumaczoną kategorię lub oryginalną nazwę, jeśli brak tłumaczenia
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $everything_OK = true;
    $errors = [];

    $period = $_POST['period'] ?? 'current_month'; // Pobieramy wybrany okres

    // Ustalanie dat w zależności od wybranego okresu
    if ($period == 'current_month') {
        $start_date = date('Y-m-01'); // Pierwszy dzień bieżącego miesiąca
        $end_date = date('Y-m-t'); // Ostatni dzień bieżącego miesiąca
    } elseif ($period == 'previous_month') {
        $start_date = date('Y-m-01', strtotime('first day of last month')); // Pierwszy dzień poprzedniego miesiąca
        $end_date = date('Y-m-t', strtotime('last day of last month')); // Ostatni dzień poprzedniego miesiąca
    } elseif ($period == 'custom') {
        // Walidacja własnego zakresu dat
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';

        if (!DateTime::createFromFormat('Y-m-d', $start_date) || !DateTime::createFromFormat('Y-m-d', $end_date)) {
            $everything_OK = false;
            $errors[] = "Data musi być w formacie RRRR-MM-DD!";
        }
    } else {
        $everything_OK = false;
        $errors[] = "Nieprawidłowy wybór okresu!";
    }

    if ($everything_OK) {
        $show_balance = true; // Ustawienie flagi na true, aby pokazać bilans

        try {
            $connection = new mysqli($host, $db_user, $db_password, $db_name);
            if ($connection->connect_errno != 0) {
                throw new Exception(mysqli_connect_error());
            }

            $user_id = $_SESSION['id']; // Pobieranie identyfikatora użytkownika z sesji

            // SQL query to fetch and sum incomes by category for the current user
            $income_query = $connection->prepare("
                SELECT ic.name AS category_name, SUM(i.amount) AS total_amount
                FROM incomes i
                JOIN incomes_category_assigned_to_users ic ON i.income_category_assigned_to_user_id = ic.id
                WHERE i.user_id = ? AND i.date_of_income BETWEEN ? AND ?
                GROUP BY ic.name
                ORDER BY ic.name
            ");

            if (!$income_query) {
                throw new Exception("Błąd przygotowania zapytania do przychodów: " . $connection->error);
            }

            $income_query->bind_param('iss', $user_id, $start_date, $end_date);
            $income_query->execute();
            $income_result = $income_query->get_result();

            // SQL query to fetch and sum expenses by category for the current user
            $expense_query = $connection->prepare("
                SELECT ec.name AS category_name, SUM(e.amount) AS total_amount
                FROM expenses e
                JOIN expenses_category_assigned_to_users ec ON e.expense_category_assigned_to_user_id = ec.id
                WHERE e.user_id = ? AND e.date_of_expense BETWEEN ? AND ?
                GROUP BY ec.name
                ORDER BY ec.name
            ");

            if (!$expense_query) {
                throw new Exception("Błąd przygotowania zapytania do wydatków: " . $connection->error);
            }

            $expense_query->bind_param('iss', $user_id, $start_date, $end_date);
            $expense_query->execute();
            $expense_result = $expense_query->get_result();

            // Prepare results for display
            $incomes = [];
            $total_income = 0;

            while ($row = $income_result->fetch_assoc()) {
                $row['category_name'] = translateCategory($row['category_name']); // Translacja kategorii na polski
                $incomes[] = $row;
                $total_income += $row['total_amount'];
            }

            $expenses = [];
            $total_expense = 0;

            while ($row = $expense_result->fetch_assoc()) {
                $row['category_name'] = translateCategory($row['category_name']); // Translacja kategorii na polski
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

        .thumb-up {
            font-size: 2rem;
            color: green;
        }

        .thumb-down {
            font-size: 2rem;
            color: red;
        }

        #expenseChart {
            width: 500px;
            height: 500px;
        }

        .chart-container {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .result {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 20px 0;
            text-align: center;
        }

        .tables {
            margin-top: 30px;
        }

        .title_table {
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
            font-size: 1.3rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table,
        th,
        td {
            border: 1px solid black;
            text-align: center;
        }

        th,
        td {
            padding: 10px;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> <!-- Załaduj bibliotekę Chart.js -->
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
                <!-- Form -->
                <form method="post" autocomplete="off">
                    <div id="term">
                        <label>Wybierz okres:</label><br><br>
                        <input type="radio" id="current_month" name="period" value="current_month" <?php if ($period === 'current_month') echo 'checked'; ?>>
                        <label for="current_month">Bieżący miesiąc</label><br>
                        <input type="radio" id="previous_month" name="period" value="previous_month" <?php if ($period === 'previous_month') echo 'checked'; ?>>
                        <label for="previous_month">Poprzedni miesiąc</label><br>
                        <input type="radio" id="custom" name="period" value="custom" <?php if ($period === 'custom') echo 'checked'; ?>>
                        <label for="custom">Niestandardowy okres</label><br></br>

                        <div id="custom_dates" style="display: <?php echo $period === 'custom' ? 'block' : 'none'; ?>;">
                            <label for="start_date">Data początkowa:</label>
                            <input id="start_date" type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" /> </br>
                            <label for="end_date">Data końcowa:</label>
                            <input id="end_date" type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" /> </br>
                        </div>

                        <div class="buttons">
                            <input type="submit" value="Pokaż bilans">
                        </div>
                    </div>
                </form>

                <!-- Wynik porównania przychodów i wydatków -->
                <?php if ($show_balance): ?>
                    <div class="result">
                        <?php if ($total_income >= $total_expense): ?>
                            <p>Gratulacje! Masz nadwyżkę: <strong><?php echo number_format($total_income - $total_expense, 2, ',', ' '); ?> zł</strong></p>
                            <span class="thumb-up">&#128077;</span> <!-- Kciuk w górę -->
                        <?php else: ?>
                            <p>Masz deficyt: <strong><?php echo number_format($total_expense - $total_income, 2, ',', ' '); ?> zł</strong></p>
                            <span class="thumb-down">&#128078;</span> <!-- Kciuk w dół -->
                        <?php endif; ?>
                    </div>

                    <!-- Tabele przychodów i wydatków -->
                    <div class="tables">
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
                    </div>

                    <!-- Wykres kołowy wydatków -->
                    <div class="chart-container">
                        <canvas id="expenseChart"></canvas>
                    </div>

                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Funkcja do pokazywania/ukrywania niestandardowego okresu
        document.querySelectorAll('input[name="period"]').forEach((elem) => {
            elem.addEventListener('change', function() {
                if (this.value === 'custom') {
                    document.getElementById('custom_dates').style.display = 'block';
                } else {
                    document.getElementById('custom_dates').style.display = 'none';
                }
            });
        });

        <?php if ($show_balance): ?>
            // Dane do wykresu kołowego z kategorii wydatków
            var expenseLabels = <?php echo json_encode(array_column($expenses, 'category_name')); ?>;
            var expenseData = <?php echo json_encode(array_column($expenses, 'total_amount')); ?>;

            var ctx = document.getElementById('expenseChart').getContext('2d');
            var expenseChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: expenseLabels,
                    datasets: [{
                        data: expenseData,
                        backgroundColor: [
                            '#ff6384', '#36a2eb', '#cc65fe', '#ffce56', '#ff9f40', '#4bc0c0', '#9966ff'
                        ]
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: false, // Ustawienie stałych wymiarów
                    plugins: {
                        legend: {
                            position: 'right', // Ustawienie legendy po prawej stronie
                            labels: {
                                boxWidth: 20,
                                padding: 15
                            }
                        }
                    }
                }
            });
        <?php endif; ?>
    </script>
</body>

</html>