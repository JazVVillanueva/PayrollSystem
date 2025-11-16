<?php
include 'db_connect.php';



$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '2025-01-03';
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '2025-01-30';
$summary_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Fetch all unique employee names
    $employee_query = $conn->query("SELECT DISTINCT Name FROM timesheet ORDER BY Name ASC");
    $employees = [];
    while ($row = $employee_query->fetch_assoc()) {
        $employees[] = $row['Name'];
    }

    $total_net_income = 0;

    // --- Preload per-employee daily rates from `employees`
    $empRate = [];
    if ($qr = $conn->query("SELECT name, rate FROM employees")) {
        while ($r = $qr->fetch_assoc()) {
            $empRate[$r['name']] = (float)$r['rate'];
        }
        $qr->close();
    }


    foreach ($employees as $employee) {
        // Query records for this employee within date range
        $stmt = $conn->prepare("SELECT * FROM timesheet WHERE Name = ? AND Date BETWEEN ? AND ? ORDER BY Date ASC");
        $stmt->bind_param("sss", $employee, $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();

        // Initialize variables for this employee (same as payslip_details.php)
        $total_days_worked = 0;
        $total_overtime_hours = 0;
        $total_night_diff = 0;
        $total_holiday_premium = 0;
        $total_sil_count = 0;
        $total_allowance = 0;
        $total_cashier_bonus = 0;
        $total_overtime_pay = 0.0;
        $daily_rate    = $empRate[$employee] ?? 520.0;   // fallback 520 if missing
        $regular_hours = 8;
        $overtime_rate = $daily_rate / $regular_hours; // kept for compatibility; we won't use it later


        $holiday_dates = [
        '2025-01-01' => 1.00, // Regular
        '2025-01-29' => 0.30, // Special
        ];


        $sss = 0;
        $phic = 0;
        $hdmf = 0;
        $govt_loan = 0;
        $late_absent = 0;
        $misload_shortage = 0;
        $uniform_ca = 0;
        $total_deductions = 0;
        $bonuses = 0;

        $exempt_employees = ['Richards, Sue', 'Grimm, Ben', 'Hammond, Jim', 'Barnes, James', 'Murdock, Matthew', 'Allen, Barry', 'Curry, Arthur'];

        $processed_dates = [];
        $processed_holidays = [];
        $dayBuckets = [];   
        

        while ($row = $result->fetch_assoc()) {
            $date = $row['Date'];
            $time_in = strtotime($row['Time_IN']);
            $time_out = strtotime($row['Time_OUT']);
            $hours = (float)$row['Hours'];
            $role = $row['Role'];
            $remarks = $row['Remarks'];
            $short_misload_bonus_sil = $row['Short_Misload_Bonus_SIL'];
            $deductions = $row['Deductions'];
            $dept_row = $row['Business_Unit'] ?? '';
            $regular_hours_row = (stripos($dept_row, 'Canteen') !== false) ? 12 : 8;
            $ot_rate_row = $daily_rate / $regular_hours_row;

                $is_ot = stripos($remarks, 'Overtime') !== false;
    if (!isset($dayBuckets[$date])) {
        $dayBuckets[$date] = ['base' => 0, 'saw_any_row' => true];
    } else {
        $dayBuckets[$date]['saw_any_row'] = true;
    }
    // Only add to "base" for non-OT rows; OT-only dates will have base == 0 but still count as a day.
    if (!$is_ot) {
        $dayBuckets[$date]['base'] += $hours;
    }

            $has_sil = stripos($short_misload_bonus_sil, 'SIL') !== false;
            if (!$has_sil && !in_array($date, $processed_dates)) {
                $total_days_worked++;
                $processed_dates[] = $date;
            }

            if (stripos($remarks, 'Overtime') !== false) {
                $total_overtime_hours += $hours;          // keep hours if you show them
                $total_overtime_pay   += $hours * $ot_rate_row; // PAY per row
            }


            $night_start = strtotime('20:00');
            $night_end = strtotime('07:00') + 86400;
            if ($time_in >= $night_start && $time_out <= $night_end) {
                $total_night_diff += 52;
            }

            if (!in_array($date, $processed_holidays) && array_key_exists($date, $holiday_dates)) {
                $premium_rate = $holiday_dates[$date];
                $total_holiday_premium += $daily_rate * $premium_rate;
                $processed_holidays[] = $date;
            }

            $total_sil_count += substr_count($short_misload_bonus_sil, 'SIL');

            
            if ($role === 'Cashier') {
                $total_cashier_bonus += 40 * floor($hours / 8);
            }

            if (stripos($remarks, 'Late') !== false) {
                $late_absent += 150;
            }

            if (!empty($deductions) && strpos($deductions, '-') === 0) {
                $misload_shortage += (float)str_replace('-', '', $deductions);
            }

            if (stripos($short_misload_bonus_sil, 'CA') !== false) {
                $uniform_ca += 500;
            }
            if (stripos($short_misload_bonus_sil, 'Uniform') !== false) {
                $uniform_ca += 106;
            }

            if (stripos($short_misload_bonus_sil, 'Bonus') !== false) {
                $bonuses += 0; // Placeholder; adjust if you have specific bonus amounts
            }
        }

        $emp_ded = $conn->query("SELECT sss, phic, hdmf, govt FROM employees WHERE name = '$employee'");
        if ($emp_ded && $d = $emp_ded->fetch_assoc()) {
            $sss = (float)$d['sss'];
            $phic = (float)$d['phic'];
            $hdmf = (float)$d['hdmf'];
            $govt_loan = (float)$d['govt'];
        }


        if ($employee === 'Wayne, Bruce') {
            $govt_loan = 461.25;
        } elseif ($employee === 'Parker, Peter') {
            $govt_loan = 922.9;
        }

        $total_night_diff += $total_sil_count * 52;

        if ($start_date <= '2025-01-05' && $end_date >= '2025-01-05') {
            $total_holiday_premium += $daily_rate * 1.00;
        }

        $basic_pay = $total_days_worked * $daily_rate;
        $overtime_pay = $total_overtime_hours * $overtime_rate;
        $night_diff_pay = $total_night_diff;
        $holiday_pay = $total_holiday_premium;
        $sil_pay = $total_sil_count * $daily_rate;
        $cashier_pay = $total_cashier_bonus;
        $subtotal = $basic_pay + $overtime_pay + $night_diff_pay + $holiday_pay + $sil_pay + $cashier_pay;
        // if ($subtotal > 520) {
        //    $total_allowance = 20;
        // }
        // $daily_rate should come from employees table (rate), not hard-coded.
        $worked_days = count($processed_dates);   // or count($counted_dates) if you used that name

        $total_allowance = ($daily_rate > 520) ? (20 * $worked_days) : 0;


        $gross_income = $basic_pay + $overtime_pay + $night_diff_pay + $holiday_pay
              + $sil_pay + $cashier_pay + $total_allowance;

        $total_deductions = $sss + $phic + $hdmf + $govt_loan + $late_absent + $misload_shortage + $uniform_ca;
        $net_income = $gross_income - $total_deductions + $bonuses;

        $summary_data[] = [
            'name' => $employee,
            'net_income' => $net_income
        ];

        $total_net_income += $net_income;

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salary Summary</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        form { margin-bottom: 20px; }
        select, input { padding: 8px; margin: 5px; }
        button { padding: 10px 15px; background: #007bff; color: white; border: none; cursor: pointer; }
        .summary-table { margin-top: 20px; }
        .summary-table table { width: 100%; border-collapse: collapse; }
        .summary-table th, .summary-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .summary-table th { background-color: #f2f2f2; }
        .back-button { margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Salary Summary</h1>
    <form method="POST">
        <label for="start_date">Start Date:</label>
        <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
        
        <label for="end_date">End Date:</label>
        <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
        
        <button type="submit">Generate Summary</button>
    </form>

    <?php if (!empty($summary_data)): ?>
        <div class="summary-table">
            <h2>Salary Summary (<?php echo htmlspecialchars($start_date); ?> to <?php echo htmlspecialchars($end_date); ?>)</h2>
            <table>
                <tr><th style="color: black;">Employee Name</th><th style="color: black;">Net Income</th></tr>
                <?php foreach ($summary_data as $data): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($data['name']); ?></td>
                        <td><?php echo number_format($data['net_income'], 2); ?> PHP</td>
                    </tr>
                <?php endforeach; ?>
                <tr><th style="color: black;">TOTAL</th><th style="color: black;"><?php echo number_format($total_net_income, 2); ?> PHP</th></tr>
            </table>
        </div>
    <?php endif; ?>

    <div class="back-button">
        <button onclick="location.href='index.php'">Back to Dashboard</button>
    </div>
</body>
</html>
