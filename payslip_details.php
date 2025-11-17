<?php
include 'db_connect.php';

$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '2025-01-03';
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '2025-01-30'; // Includes Three Kings Day
$selected_employee = isset($_POST['employee']) ? $_POST['employee'] : '';
$payslip_data = null;
$debug_holidays = []; // For debugging: Track processed holidays

// Fetch unique employee names
$employee_query = $conn->query("SELECT DISTINCT Name FROM timesheet ORDER BY Name ASC");
$employees = [];
while ($row = $employee_query->fetch_assoc()) {
    $employees[] = $row['Name'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($selected_employee)) {
    // Query records for the selected employee within date range
    $stmt = $conn->prepare("SELECT * FROM timesheet WHERE Name = ? AND Date BETWEEN ? AND ? ORDER BY Date ASC");
    $stmt->bind_param("sss", $selected_employee, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    // Initialize variables for computation
    $total_days_worked = 0;
    $total_overtime_hours = 0;
    $total_night_diff = 0;
    $total_holiday_premium = 0;
    $total_sil_count = 0; // Count of SIL occurrences
    $total_allowance = 0;
    $total_cashier_bonus = 0;
    $daily_rate = 520;
    $overtime_rate = 65;
    $holiday_dates = [
        '2025-01-29' => 0.30 // Chinese New Year: Special Non-Working (+30%)
        // Removed Three Kings Day from here to avoid double addition
    ];

    // Deductions variables
    $sss = 0;
    $phic = 0;
    $hdmf = 0;
    $govt_loan = 0;
    $late_absent = 0;
    $misload_shortage = 0;
    $uniform_ca = 0;
    $total_deductions = 0;
    $bonuses = 0; // Bonuses to add to NET income

    // Exempt employees (no SSS, PHIC, HDMF)
    $exempt_employees = ['Richards, Sue', 'Grimm, Ben', 'Hammond, Jim', 'Barnes, James', 'Murdock, Mathew', 'Allen, Barry', 'Curry, Arthur'];

    $processed_dates = []; // To avoid double-counting days
    $processed_holidays = []; // To avoid double-counting holiday premiums

    while ($row = $result->fetch_assoc()) {
        $date = $row['Date'];
        $time_in = strtotime($row['Time_IN']);
        $time_out = strtotime($row['Time_OUT']);
        $hours = (float)$row['Hours'];
        $role = $row['Role'];
        $remarks = $row['Remarks'];
        $short_misload_bonus_sil = $row['Short_Misload_Bonus_SIL'];
        $deductions = $row['Deductions'];

        // Count unique days worked (exclude dates with SIL)
        $has_sil = stripos($short_misload_bonus_sil, 'SIL') !== false;
        if (!$has_sil && !in_array($date, $processed_dates)) {
            $total_days_worked++;
            $processed_dates[] = $date;
        }

        // Overtime: If "Overtime" in Remarks, add Hours to overtime
        if (stripos($remarks, 'Overtime') !== false) {
            $total_overtime_hours += $hours;
        }

        // Night Differential: Count occurrences where shift is within 20:00 - 7:00, multiply by 52
        $night_start = strtotime('20:00');
        $night_end = strtotime('07:00') + 86400; // Next day
        if ($time_in >= $night_start && $time_out <= $night_end) {
            $total_night_diff += 52; // Add 52 per occurrence
        }

        // Holiday Premium: Add only once per unique holiday date (for worked holidays)
        if (!in_array($date, $processed_holidays) && array_key_exists($date, $holiday_dates)) {
            $premium_rate = $holiday_dates[$date];
            $total_holiday_premium += $daily_rate * $premium_rate;
            $processed_holidays[] = $date;
            $debug_holidays[] = $date . ' (+' . ($daily_rate * $premium_rate) . ' PHP)'; // Debug: Track added holidays
        }

        // SIL: Count occurrences of "SIL" in the column, each = 1 * daily_rate
        $total_sil_count += substr_count($short_misload_bonus_sil, 'SIL');

        // Cashier Bonus: 40php per 8hrs if Role is Cashier
        if ($role === 'Cashier') {
            $total_cashier_bonus += 40 * floor($hours / 8);
        }

        // Deductions and Bonuses
        // Late/Absent: 150 per "Late" in Remarks
        if (stripos($remarks, 'Late') !== false) {
            $late_absent += 150;
        }

        // Misload/Shortage: Deductions column (with "-")
        if (!empty($deductions) && strpos($deductions, '-') === 0) {
            $misload_shortage += (float)str_replace('-', '', $deductions);
        }

        // Uniform/CA: 500 per "CA", 106 per "Uniform" in Short/Misload/Bonus/SIL
        if (stripos($short_misload_bonus_sil, 'CA') !== false) {
            $uniform_ca += 500;
        }
        if (stripos($short_misload_bonus_sil, 'Uniform') !== false) {
            $uniform_ca += 106;
        }

        // Bonuses: "Bonus" in Short/Misload/Bonus/SIL (add to NET income)
        if (stripos($short_misload_bonus_sil, 'Bonus') !== false) {
            // Assuming bonus amount is in the column without "-", e.g., "Bonus 200" -> add 200
            // For simplicity, if "Bonus" is present, add a fixed amount or parse; here, I'll assume it's added separately if needed.
            // If the column has "Bonus 200", you can parse it; for now, I'll set a placeholder.
            $bonuses += 0; // Adjust if you have specific bonus amounts
        }
    }

    // Standard Deductions (if not exempt)
    if (!in_array($selected_employee, $exempt_employees)) {
        $sss = 562.5;
        $phic = 312.5;
        $hdmf = 200;
    }

    // Govt. Loan
    if ($selected_employee === 'Wayne, Bruce') {
        $govt_loan = 461.25;
    } elseif ($selected_employee === 'Parker, Peter') {
        $govt_loan = 922.9;
    }

    // Add SIL to night differential: Each SIL counts as 52 PHP night diff
    $total_night_diff += $total_sil_count * 52;

    // Add Three Kings Day premium to all employees if in range (even if not worked)
    if ($start_date <= '2025-01-05' && $end_date >= '2025-01-05') {
        $total_holiday_premium += $daily_rate * 1.00; // +520 PHP
        $debug_holidays[] = '2025-01-05 (+520 PHP)'; // Debug: Track added holidays
    }

    // Allowance: 20php if total daily pay > 520 (after basic + bonuses)
    $basic_pay = $total_days_worked * $daily_rate;
    $overtime_pay = $total_overtime_hours * $overtime_rate;
    $night_diff_pay = $total_night_diff;
    $holiday_pay = $total_holiday_premium;
    $sil_pay = $total_sil_count * $daily_rate; // SIL pay = count * 520
    $cashier_pay = $total_cashier_bonus;
    $subtotal = $basic_pay + $overtime_pay + $night_diff_pay + $holiday_pay + $sil_pay + $cashier_pay;
    if ($subtotal > 520) {
        $total_allowance = 20;
    }

    // Gross Income
    $gross_income = $subtotal + $total_allowance;

    // Total Deductions
    $total_deductions = $sss + $phic + $hdmf + $govt_loan + $late_absent + $misload_shortage + $uniform_ca;

    // Net Income
    $net_income = $gross_income - $total_deductions + $bonuses;

    $payslip_data = [
        'employee' => $selected_employee,
        'total_days_worked' => $total_days_worked,
        'daily_rate' => $daily_rate,
        'overtime_hours' => $total_overtime_hours,
        'overtime_rate' => $overtime_rate,
        'allowance' => $total_allowance,
        'night_diff' => $night_diff_pay,
        'holiday' => $holiday_pay,
        'sil' => $sil_pay,
        'gross_income' => $gross_income,
        'sss' => $sss,
        'phic' => $phic,
        'hdmf' => $hdmf,
        'govt_loan' => $govt_loan,
        'late_absent' => $late_absent,
        'misload_shortage' => $misload_shortage,
        'uniform_ca' => $uniform_ca,
        'total_deductions' => $total_deductions,
        'net_income' => $net_income
    ];

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Individual Payslip Details</title>
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
        .container { max-width: 1200px; margin: 0 auto; }
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
        form { display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap; }
        .form-group { flex: 1; min-width: 200px; }
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
        .payslip-breakdown {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            animation: slideUp 0.5s ease-out;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .payslip-breakdown h2 {
            color: #1a202c;
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f7fafc;
        }
        .payslip-breakdown table { 
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .payslip-breakdown th, .payslip-breakdown td { 
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        .payslip-breakdown th { 
            background: #f7fafc;
            color: #2d3748;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .payslip-breakdown tbody tr:hover { background: #f7fafc; }
        .payslip-breakdown tbody tr:last-child td { border-bottom: none; }
        .payslip-breakdown tr th:first-child { border-top-left-radius: 10px; }
        .payslip-breakdown tr th:last-child { border-top-right-radius: 10px; }
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
        .debug {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .debug h3 {
            color: #1a202c;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }
        .icon { margin-right: 0.5rem; }
        @media (max-width: 768px) {
            body { padding: 1rem; }
            h1 { font-size: 1.5rem; }
            form { flex-direction: column; }
            .form-group { width: 100%; }
            button { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-file-invoice-dollar icon"></i>Generate Individual Payslip</h1>
            <p class="subtitle">View detailed payslip breakdown for any employee</p>
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
                        <?php foreach ($employees as $emp): ?>
                            <option value="<?php echo htmlspecialchars($emp); ?>" <?php if ($emp === $selected_employee) echo 'selected'; ?>><?php echo htmlspecialchars($emp); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit"><i class="fas fa-calculator icon"></i>Generate Payslip</button>
            </form>
        </div>

    <?php if ($payslip_data): ?>
        <div class="payslip-breakdown">
            <h2><i class="fas fa-user-tie icon"></i>Payslip for <?php echo htmlspecialchars($payslip_data['employee']); ?></h2>
            <p style="color: #718096; margin-bottom: 1.5rem;"><i class="far fa-calendar icon"></i><?php echo htmlspecialchars($start_date); ?> to <?php echo htmlspecialchars($end_date); ?></p>
            <table>
                <tr><th style="color: black; font-weight: bold;">Item</th><th style="color: black; font-weight: bold;">Value</th></tr>
                <tr><td>Total Days of Work</td><td><?php echo $payslip_data['total_days_worked']; ?></td></tr>
                <tr><td>Rate (per day)</td><td><?php echo $payslip_data['daily_rate']; ?> PHP</td></tr>
                <tr><td>Hrs of Overtime</td><td><?php echo $payslip_data['overtime_hours']; ?></td></tr>
                <tr><td>Rate (per overtime hour)</td><td><?php echo $payslip_data['overtime_rate']; ?> PHP</td></tr>
                <tr><td>Allowance</td><td><?php echo $payslip_data['allowance']; ?> PHP</td></tr>
                <tr><td>Night Diff.</td><td><?php echo $payslip_data['night_diff']; ?> PHP</td></tr>
                <tr><td>Holiday</td><td><?php echo $payslip_data['holiday']; ?> PHP</td></tr>
                <tr><td>SIL</td><td><?php echo $payslip_data['sil']; ?> PHP</td></tr>
                <tr><th style="color: black; font-weight: bold;">GROSS Income</th><th style="color: black; font-weight: bold;"><?php echo number_format($payslip_data['gross_income'], 2); ?> PHP</th></tr>
            </table>
        </div>

        <!-- Deductions and Net Income Table -->
        <div class="payslip-breakdown">
            <h2><i class="fas fa-calculator icon"></i>Deductions and Net Income</h2>
            <table>
                <tr><th style="color: black; font-weight: bold;">Item</th><th style="color: black; font-weight: bold;">Value</th></tr>
                <tr><td>SSS</td><td><?php echo number_format($payslip_data['sss'], 2); ?> PHP</td></tr>
                <tr><td>PHIC</td><td><?php echo number_format($payslip_data['phic'], 2); ?> PHP</td></tr>
                <tr><td>HDMF</td><td><?php echo number_format($payslip_data['hdmf'], 2); ?> PHP</td></tr>
                <tr><td>Govt. Loan</td><td><?php echo number_format($payslip_data['govt_loan'], 2); ?> PHP</td></tr>
                <tr><td>Late/Absent</td><td><?php echo number_format($payslip_data['late_absent'], 2); ?> PHP</td></tr>
                <tr><td>Misload/Shortage</td><td><?php echo number_format($payslip_data['misload_shortage'], 2); ?> PHP</td></tr>
                <tr><td>Uniform/CA</td><td><?php echo number_format($payslip_data['uniform_ca'], 2); ?> PHP</td></tr>
                <tr><th style="color: black; font-weight: bold;">Total Deductions</th><th style="color: black; font-weight: bold;"><?php echo number_format($payslip_data['total_deductions'], 2); ?> PHP</th></tr>
                <tr><th style="color: black; font-weight: bold;">Net Income</th><th style="color: black; font-weight: bold;"><?php echo number_format($payslip_data['net_income'], 2); ?> PHP</th></tr>
            </table>
        </div>

        <!-- Debug Section -->
        <div class="debug">
            <h3><i class="fas fa-bug icon"></i>Debug: Processed Holidays</h3>
            <?php if (empty($debug_holidays)): ?>
                <p>No holidays processed for this employee in the selected date range.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($debug_holidays as $holiday): ?>
                        <li><?php echo htmlspecialchars($holiday); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
		    <?php endif; ?>

        <div class="back-button">
            <button onclick="location.href='index.php'"><i class="fas fa-arrow-left icon"></i>Back to Dashboard</button>
        </div>
    </div>
</body>
</html>
