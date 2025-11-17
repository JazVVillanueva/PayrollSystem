<?php
include 'db_connect.php';

$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '2025-01-03';
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '2025-01-30';
$selected_employee = isset($_POST['employee']) ? $_POST['employee'] : '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($selected_employee)) {
    // Payslip calculation (same as before)
    $stmt = $conn->prepare("SELECT * FROM timesheet WHERE Name = ? AND Date BETWEEN ? AND ? ORDER BY Date ASC");
    $stmt->bind_param("sss", $selected_employee, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    // Initialize variables
    $total_days_worked = 0;
    $total_overtime_hours = 0;
    $total_night_diff = 0;
    $total_holiday_premium = 0;
    $total_sil_count = 0;
    $total_allowance = 0;
    $total_cashier_bonus = 0;
    $daily_rate = 520;
    $overtime_rate = 65;
    $holiday_dates = ['2025-01-29' => 0.30];

    $sss = 0;
    $phic = 0;
    $hdmf = 0;
    $govt_loan = 0;
    $late_absent = 0;
    $misload_shortage = 0;
    $uniform_ca = 0;
    $total_deductions = 0;
    $bonuses = 0;

    $exempt_employees = ['Richards, Sue', 'Grimm, Ben', 'Hammond, Jim', 'Barnes, James', 'Murdock, Mathew', 'Allen, Barry', 'Curry, Arthur'];

    $processed_dates = [];
    $processed_holidays = [];

    while ($row = $result->fetch_assoc()) {
        $date = $row['Date'];
        $time_in = strtotime($row['Time_IN']);
        $time_out = strtotime($row['Time_OUT']);
        $hours = (float)$row['Hours'];
        $role = $row['Role'];
        $remarks = $row['Remarks'];
        $short_misload_bonus_sil = $row['Short_Misload_Bonus_SIL']; // Fixed to underscores
        $deductions = $row['Deductions'];

        $has_sil = stripos($short_misload_bonus_sil, 'SIL') !== false;
        if (!$has_sil && !in_array($date, $processed_dates)) {
            $total_days_worked++;
            $processed_dates[] = $date;
        }

        if (stripos($remarks, 'Overtime') !== false) {
            $total_overtime_hours += $hours;
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
            $bonuses += 0; // Placeholder
        }
    }

    if (!in_array($selected_employee, $exempt_employees)) {
        $sss = 562.5;
        $phic = 312.5;
        $hdmf = 200;
    }

    if ($selected_employee === 'Wayne, Bruce') {
        $govt_loan = 461.25;
    } elseif ($selected_employee === 'Parker, Peter') {
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
    if ($subtotal > 520) {
        $total_allowance = 20;
    }

    $gross_income = $subtotal + $total_allowance;
    $total_deductions = $sss + $phic + $hdmf + $govt_loan + $late_absent + $misload_shortage + $uniform_ca;
    $net_income = $gross_income - $total_deductions + $bonuses;

    $stmt->close();

    // Generate CSV content
    $csv_content = "Payslip for $selected_employee ($start_date to $end_date)\n";
    $csv_content .= "Item,Value\n";
    $csv_content .= "Total Days of Work,$total_days_worked\n";
    $csv_content .= "Rate (per day),$daily_rate PHP\n";
    $csv_content .= "Hrs of Overtime,$total_overtime_hours\n";
    $csv_content .= "Rate (per overtime hour),$overtime_rate PHP\n";
    $csv_content .= "Allowance,$total_allowance PHP\n";
    $csv_content .= "Night Diff.,$night_diff_pay PHP\n";
    $csv_content .= "Holiday,$holiday_pay PHP\n";
    $csv_content .= "SIL,$sil_pay PHP\n";
    $csv_content .= "GROSS Income," . number_format($gross_income, 2) . " PHP\n";
    $csv_content .= "SSS," . number_format($sss, 2) . " PHP\n";
    $csv_content .= "PHIC," . number_format($phic, 2) . " PHP\n";
    $csv_content .= "HDMF," . number_format($hdmf, 2) . " PHP\n";
    $csv_content .= "Govt. Loan," . number_format($govt_loan, 2) . " PHP\n";
    $csv_content .= "Late/Absent," . number_format($late_absent, 2) . " PHP\n";
    $csv_content .= "Misload/Shortage," . number_format($misload_shortage, 2) . " PHP\n";
    $csv_content .= "Uniform/CA," . number_format($uniform_ca, 2) . " PHP\n";
    $csv_content .= "Total Deductions," . number_format($total_deductions, 2) . " PHP\n";
    $csv_content .= "Net Income," . number_format($net_income, 2) . " PHP\n";

    // Suppress warnings and force CSV download
    error_reporting(0); // Suppress warnings to avoid header issues
    $csv_file_name = 'payslip_' . str_replace(' ', '_', $selected_employee) . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $csv_file_name . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo $csv_content;
    exit; // Stop further execution to force download

    // Success message (won't show due to exit, but can be added if needed)
    $message = 'Payslip CSV downloaded successfully!';
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Payslip PDF</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
        }
        .container { max-width: 800px; margin: 0 auto; }
        .header {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        h1 { 
            color: #1a202c;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .subtitle { color: #718096; font-size: 0.95rem; }
        .form-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        form { display: flex; gap: 1rem; flex-direction: column; }
        .form-group { margin-bottom: 1rem; }
        label { 
            display: block;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        select, input { 
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }
        select:focus, input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        button { 
            padding: 0.75rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        .message { 
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error { 
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .back-button { text-align: center; }
        .back-button button {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }
        .back-button button:hover {
            background: #667eea;
            color: white;
        }
        .icon { margin-right: 0.5rem; }
        @media (max-width: 768px) {
            body { padding: 1rem; }
            h1 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-file-pdf icon"></i>Download Payslip PDF</h1>
            <p class="subtitle">Generate and download individual payslip in PDF format</p>
        </div>

        <div class="form-card">
            <form method="POST">
                <div class="form-group">
                    <label><i class="far fa-calendar-alt icon"></i>Start Date</label>
                    <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                </div>
                
                <div class="form-group">
                    <label><i class="far fa-calendar-check icon"></i>End Date</label>
                    <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-user icon"></i>Select Employee</label>
                    <select name="employee" required>
                        <option value="">-- Select Employee --</option>
                        <?php
                        include 'db_connect.php';
                        $employee_query = $conn->query("SELECT DISTINCT Name FROM timesheet ORDER BY Name ASC");
                        while ($row = $employee_query->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($row['Name']) . '">' . htmlspecialchars($row['Name']) . '</option>';
                        }
                        $conn->close();
                        ?>
                    </select>
                </div>
                
                <button type="submit"><i class="fas fa-file-pdf icon"></i>Download PDF</button>
            </form>
        </div>

        <?php if ($message): ?>
            <div class="message">
                <i class="fas fa-check-circle icon"></i><?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="back-button">
            <button onclick="location.href='index.php'"><i class="fas fa-arrow-left icon"></i>Back to Dashboard</button>
        </div>
    </div>
</body>
</html>
