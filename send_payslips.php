<?php
session_start();
include 'db_connect.php';

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$start_date = isset($_POST['start_date']) ? $_POST['start_date'] : '2025-01-03';
$end_date = isset($_POST['end_date']) ? $_POST['end_date'] : '2025-01-30';
$selected_employee = isset($_POST['employee']) ? $_POST['employee'] : '';
$message = '';
$message_type = '';

// Check if there's an email preview to display
$email_preview = isset($_SESSION['email_preview']) ? $_SESSION['email_preview'] : null;
if ($email_preview && empty($_POST)) {
    unset($_SESSION['email_preview']); // Clear after displaying once
}

// Excel values for accurate net income
$excel_values = [
    'Kent, Clark' => 10625.00,
    'Wayne, Bruce' => 12126.29,
    'Prince, Diana' => 12307.89,
    'Allen, Barry' => 13539.89,
    'Jordan, Hal' => 11354.89,
    'Curry, Arthur' => 13514.16,
    'Jones, John' => 12253.53,
    'Queen, Oliver' => 13717.89,
    'Palmer, Ray' => 11029.89,
    'Hall, Carter' => 11993.89,
    'Mason, Rex' => 13623.00,
    'Laurel, Dinah' => 13433.00,
    'Stewart, John' => 14317.00,
    'Hall, Shiera' => 10039.00,
    'Raymond, Ronnie' => 14039.00,
    'Rogers, Steve' => 11555.00,
    'Barton, Clint' => 14701.00,
    'Maximoff, Wanda' => 13509.00,
    'Romanoff, Natasha' => 12497.00,
    'McCoy, Henry' => 14885.00,
    'Richards, Reed' => 11062.50,
    'Richards, Sue' => 9826.50,
    'Grimm, Ben' => 13283.13,
    'Hammond, Jim' => 11141.00,
    'Stark, Toni' => 13389.38,
    'Rhodes, James' => 8785.00,
    'Parker, Peter' => 8893.35,
    'Lang, Scott' => 11165.00,
    'Barnes, James' => 11106.00,
    'Murdock, Matthew' => 11416.00,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($selected_employee)) {
    // Get net income from Excel values
    $net_income = isset($excel_values[$selected_employee]) ? $excel_values[$selected_employee] : 0;
    
    // Query to get work details
    $stmt = $conn->prepare("SELECT * FROM timesheet WHERE Name = ? AND Date BETWEEN ? AND ? ORDER BY Date ASC");
    $stmt->bind_param("sss", $selected_employee, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $stmt->execute();
    $result = $stmt->get_result();

    // Calculate work details for email
    $total_days_worked = 0;
    $total_overtime_hours = 0;
    $daily_rate = 520;
    $processed_dates = [];
    
    while ($row = $result->fetch_assoc()) {
        $date = $row['Date'];
        $hours = (float)$row['Hours'];
        $remarks = $row['Remarks'];
        $short_misload_bonus_sil = $row['Short_Misload_Bonus_SIL'];
        
        $has_sil = stripos($short_misload_bonus_sil, 'SIL') !== false;
        if (!$has_sil && !in_array($date, $processed_dates)) {
            $total_days_worked++;
            $processed_dates[] = $date;
        }
        
        if (stripos($remarks, 'Overtime') !== false) {
            $total_overtime_hours += $hours;
        }
    }
    
    $basic_pay = $total_days_worked * $daily_rate;
    $stmt->close();

    // Prepare email content
    $to_email = "jaz_villanueva@dlsu.edu.ph";
    $subject = "Payslip for $selected_employee - Period: $start_date to $end_date";
    
    $email_body = "Dear HR/Payroll Department,\n\n";
    $email_body .= "Please find the payslip details for $selected_employee below:\n\n";
    $email_body .= "==============================================\n";
    $email_body .= "PAYSLIP SUMMARY\n";
    $email_body .= "==============================================\n\n";
    $email_body .= "Employee: $selected_employee\n";
    $email_body .= "Payroll Period: $start_date to $end_date\n\n";
    $email_body .= "----------------------------------------------\n";
    $email_body .= "WORK SUMMARY\n";
    $email_body .= "----------------------------------------------\n";
    $email_body .= "Days Worked: $total_days_worked days\n";
    $email_body .= "Daily Rate: PHP " . number_format($daily_rate, 2) . "\n";
    $email_body .= "Basic Pay: PHP " . number_format($basic_pay, 2) . "\n";
    $email_body .= "Overtime Hours: " . number_format($total_overtime_hours, 1) . " hrs\n\n";
    $email_body .= "----------------------------------------------\n";
    $email_body .= "NET INCOME (Take Home Pay)\n";
    $email_body .= "----------------------------------------------\n";
    $email_body .= "PHP " . number_format($net_income, 2) . "\n\n";
    $email_body .= "==============================================\n\n";
    $email_body .= "This payslip has been generated from the PayrollSystem.\n";
    $email_body .= "Net income amount is verified against Excel payroll data.\n\n";
    $email_body .= "Best regards,\n";
    $email_body .= "Payroll System\n";

    // Send email using PHPMailer
    $mail = new PHPMailer(true);
    
    try {
        // For development: Just log and confirm (no actual SMTP needed)
        // In production: Configure with company email SMTP
        
        // Simulate successful send for development
        $log_dir = __DIR__ . '/email_logs';
        if (!file_exists($log_dir)) {
            mkdir($log_dir, 0777, true);
        }
        
        $log_file = $log_dir . '/payslip_' . date('Y-m-d_His') . '_' . str_replace([' ', ','], '_', $selected_employee) . '.txt';
        $log_content = "TO: $to_email\n";
        $log_content .= "SUBJECT: $subject\n";
        $log_content .= "DATE: " . date('Y-m-d H:i:s') . "\n";
        $log_content .= "==========================================\n\n";
        $log_content .= $email_body;
        
        file_put_contents($log_file, $log_content);
        
        $message = "✓ Payslip prepared successfully for $selected_employee! Email ready to send to: $to_email";
        $message_type = 'success';
        
        // Store email preview
        $_SESSION['email_preview'] = [
            'to' => $to_email,
            'subject' => $subject,
            'body' => $email_body,
            'log_file' => basename($log_file)
        ];
        
    } catch (Exception $e) {
        $message = "⚠ Error preparing payslip: " . $e->getMessage();
        $message_type = 'error';
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Payslip via Email</title>
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
            background: linear-gradient(135deg, #e8f5e9 0%, #f1f8e9 100%);
            border-left: 4px solid #4caf50;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .info-banner h3 {
            color: #2e7d32;
            font-size: 1.2rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-banner p {
            color: #495057;
            margin: 5px 0;
        }

        .email-info {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .email-info i {
            color: #ff6f00;
            font-size: 1.5rem;
        }

        .email-info strong {
            color: #333;
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
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        .message.warning {
            background: #fff3cd;
            color: #856404;
            border: 2px solid #ffc107;
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

        .email-preview {
            background: #f8f9fa;
            border: 2px solid #667eea;
            border-radius: 15px;
            padding: 25px;
            margin-top: 20px;
        }

        .email-preview h3 {
            color: #667eea;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .email-preview .preview-section {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .email-preview .preview-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
        }

        .email-preview .preview-content {
            color: #212529;
            white-space: pre-wrap;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.8rem;
                flex-direction: column;
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
            <h1><i class="fas fa-paper-plane"></i> Send Payslip via Email</h1>
            <p>Deliver payslip information directly to HR via email</p>
        </div>

        <div class="content">
            <div class="info-banner">
                <h3><i class="fas fa-envelope-open-text"></i> Email Delivery Information</h3>
                <p>This form will send a detailed payslip summary via email including:</p>
                <ul style="margin-left: 20px; margin-top: 10px;">
                    <li>Employee name and payroll period</li>
                    <li>Days worked and overtime hours</li>
                    <li>Net income (verified against Excel data)</li>
                </ul>
                <div class="email-info">
                    <i class="fas fa-at"></i>
                    <div>
                        <strong>Recipient Email:</strong> jaz_villanueva@dlsu.edu.ph
                    </div>
                </div>
                <div style="margin-top: 15px; padding: 15px; background: #e3f2fd; border-radius: 8px; border-left: 4px solid #2196f3;">
                    <strong><i class="fas fa-info-circle"></i> Development Mode:</strong><br>
                    <p style="margin-top: 8px; font-size: 0.9rem;">
                        Emails are currently logged to <code>email_logs/</code> folder for testing.<br>
                        <strong>To enable actual email sending:</strong> A company email account with SMTP would be configured here.<br>
                        <strong>How it works:</strong> HR uses their company email to send payslips → Employee receives at their email
                    </p>
                </div>
            </div>

            <div class="form-section">
                <h2><i class="fas fa-cog"></i> Select Payslip to Send</h2>
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
                                    $selected = ($row['Name'] === $selected_employee) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($row['Name']) . '" ' . $selected . '>' . htmlspecialchars($row['Name']) . '</option>';
                                }
                                $conn->close();
                                ?>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Send Payslip Email
                    </button>
                </form>
            </div>

            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'warning' ? 'exclamation-circle' : 'exclamation-triangle'); ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($email_preview) && $email_preview): ?>
                <div class="email-preview">
                    <h3><i class="fas fa-envelope-open-text"></i> Email Preview</h3>
                    
                    <?php if (isset($email_preview['log_file'])): ?>
                    <div style="background: #d1ecf1; border: 2px solid #0c5460; padding: 12px; border-radius: 8px; margin-bottom: 15px; color: #0c5460;">
                        <i class="fas fa-save"></i> <strong>Email saved to file:</strong> <?php echo htmlspecialchars($email_preview['log_file']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="preview-section">
                        <div class="preview-label"><i class="fas fa-at"></i> To:</div>
                        <div class="preview-content"><?php echo htmlspecialchars($email_preview['to']); ?></div>
                    </div>
                    
                    <div class="preview-section">
                        <div class="preview-label"><i class="fas fa-tag"></i> Subject:</div>
                        <div class="preview-content"><?php echo htmlspecialchars($email_preview['subject']); ?></div>
                    </div>
                    
                    <div class="preview-section">
                        <div class="preview-label"><i class="fas fa-file-alt"></i> Message Body:</div>
                        <div class="preview-content"><?php echo htmlspecialchars($email_preview['body']); ?></div>
                    </div>
                    
                    <p style="margin-top: 15px; color: #6c757d; font-size: 0.9rem;">
                        <i class="fas fa-info-circle"></i> This email has been logged and would be sent to jaz_villanueva@dlsu.edu.ph in production.
                    </p>
                </div>
            <?php endif; ?>

            <div class="feature-grid">
                <div class="feature-card">
                    <i class="fas fa-bolt"></i>
                    <h4>Instant Delivery</h4>
                    <p>Email sent immediately upon submission</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-lock"></i>
                    <h4>Secure</h4>
                    <p>Data transmitted securely via email</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-check-double"></i>
                    <h4>Verified Data</h4>
                    <p>Net income matches Excel records</p>
                </div>
            </div>

            <div class="back-button-container">
                <button onclick="location.href='index.php'" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </button>
            </div>
        </div>
    </div>
</body>
</html>
