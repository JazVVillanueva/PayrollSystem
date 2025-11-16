<?php
// payroll.php

// Database connection
include 'db_connect.php';

// Fetch employee details
$employees = $conn->query("SELECT name, rate, sss, phic, hdmf, govt FROM employees");
$holidays = $conn->query("SELECT date, description, holiday_type FROM holidays");
$payroll = [];

// Process each employee
while ($employee = $employees->fetch_assoc()) {
    $daily_rate = $employee['rate'] / 30; // Assuming a 30-day month
    $total_deductions = $employee['sss'] + $employee['phic'] + $employee['hdmf'] + $employee['govt'];
    $total_pay = $employee['rate'] - $total_deductions;
    $holiday_pay = 0;

    // Reset holiday pointer
    $holidays->data_seek(0); 

    // Check holidays for this employee
    while ($holiday = $holidays->fetch_assoc()) {
        // Check if the holiday falls within the payroll period
        $holiday_date = new DateTime($holiday['date']);
        $start_date = new DateTime('2025-01-03');
        $end_date = new DateTime('2025-01-30');

        if ($holiday_date >= $start_date && $holiday_date <= $end_date) {
            // Assuming you have a way to check if the employee worked on this holiday
            // This logic may need to be adjusted based on your actual data structure
            if (/* condition to check if employee worked on this holiday */ true) { // Change this condition
                if ($holiday['holiday_type'] == 'Regular') {
                    $holiday_pay += $daily_rate * 1.00; // 100% rate
                } elseif ($holiday['holiday_type'] == 'Special') {
                    $holiday_pay += $daily_rate * 0.30; // 30% rate
                }
            }
        }
    }

    // Final payroll calculation
    $total_pay += $holiday_pay;
    $payroll[] = [
        'name' => $employee['name'],
        'total_pay' => $total_pay
    ];
}

// HTML output for payroll details
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Summary</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Payroll Summary</h1>
        <table>
            <thead>
                <tr>
                    <th>Employee Name</th>
                    <th>Total Pay</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payroll as $entry): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($entry['name']); ?></td>
                        <td><?php echo number_format($entry['total_pay'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<?php
$conn->close();
?>