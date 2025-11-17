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
    <title>Download Payslip - CSV Export</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .content {
            padding: 40px;
        }

        .info-banner {
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            border-left: 4px solid #667eea;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .info-banner h3 {
            color: #667eea;
            font-size: 1.2rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-banner ul {
            margin-left: 20px;
            color: #495057;
        }

        .info-banner li {
            margin: 5px 0;
        }

        .form-section {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .form-section h2 {
            color: #495057;
            font-size: 1.4rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #495057;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-group input,
        .form-group select {
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.4);
        }

        .message {
            margin-top: 20px;
            padding: 15px 20px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }

        .back-button-container {
            margin-top: 30px;
            text-align: center;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .feature-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            text-align: center;
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }

        .feature-card i {
            font-size: 2rem;
            color: #667eea;
            margin-bottom: 10px;
        }

        .feature-card h4 {
            color: #495057;
            margin-bottom: 8px;
        }

        .feature-card p {
            color: #6c757d;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.8rem;
            }

            .content {
                padding: 20px;
            }

            .form-section {
                padding: 20px;
            }

            .feature-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-file-download"></i> Download Payslip</h1>
            <p>Export employee payslip data to CSV format</p>
        </div>

        <div class="content">
            <div class="info-banner">
                <h3><i class="fas fa-info-circle"></i> What You'll Get</h3>
                <ul>
                    <li><strong>CSV File Format:</strong> Compatible with Excel, Google Sheets, and other spreadsheet applications</li>
                    <li><strong>Complete Breakdown:</strong> Days worked, overtime, allowances, deductions, and net income</li>
                    <li><strong>Ready to Use:</strong> Formatted and ready for record-keeping or further analysis</li>
                </ul>
            </div>

            <div class="form-section">
                <h2><i class="fas fa-cog"></i> Select Payslip Details</h2>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_date"><i class="fas fa-calendar-alt"></i> Start Date:</label>
                            <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="end_date"><i class="fas fa-calendar-check"></i> End Date:</label>
                            <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="employee"><i class="fas fa-user"></i> Select Employee:</label>
                            <select id="employee" name="employee" required>
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
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-download"></i> Download CSV Payslip
                    </button>
                </form>
            </div>

            <div class="feature-grid">
                <div class="feature-card">
                    <i class="fas fa-file-csv"></i>
                    <h4>CSV Format</h4>
                    <p>Universal format compatible with all spreadsheet apps</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-chart-line"></i>
                    <h4>Detailed Breakdown</h4>
                    <p>All earnings and deductions included</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-shield-alt"></i>
                    <h4>Secure Export</h4>
                    <p>Direct download with no data storage</p>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="back-button-container">
                <button onclick="location.href='index.php'" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </button>
            </div>
        </div>
    </div>
</body>
</html>
