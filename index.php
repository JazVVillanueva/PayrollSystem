<?php
include 'db_connect.php';

// Handle form submissions for inserting entries
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['insert'])) {
        // Insert new entry
        $date = $_POST['date'];
        $shift_no = $_POST['shift_no'];
        $business_unit = $_POST['business_unit'];
        $name = $_POST['name'];
        $time_in = $_POST['time_in'];
        $time_out = $_POST['time_out'];
        $hours = $_POST['hours'];
        $role = $_POST['role'];
        $remarks = $_POST['remarks'];
        $deductions = $_POST['deductions'];
        $short_misload_bonus_sil = $_POST['short_misload_bonus_sil'];

        $sql = "INSERT INTO timesheet (Date, Shift_No, Business_Unit, Name, Time_IN, Time_OUT, Hours, Role, Remarks, Deductions, Short_Misload_Bonus_SIL)
                VALUES ('$date', '$shift_no', '$business_unit', '$name', '$time_in', '$time_out', '$hours', '$role', '$remarks', '$deductions', '$short_misload_bonus_sil')";

        if ($conn->query($sql) === TRUE) {
            echo "<div class='alert success'>New record created successfully!</div>";
        } else {
            echo "<div class='alert error'>Error: " . $conn->error . "</div>";
        }
    } elseif (isset($_POST['update'])) {
        // Update existing entry
        $id = $_POST['id'];
        $date = $_POST['date'];
        $shift_no = $_POST['shift_no'];
        $business_unit = $_POST['business_unit'];
        $name = $_POST['name'];
        $time_in = $_POST['time_in'];
        $time_out = $_POST['time_out'];
        $hours = $_POST['hours'];
        $role = $_POST['role'];
        $remarks = $_POST['remarks'];
        $deductions = $_POST['deductions'];
        $short_misload_bonus_sil = $_POST['short_misload_bonus_sil'];

        $sql = "UPDATE timesheet SET Date='$date', Shift_No='$shift_no', Business_Unit='$business_unit', Name='$name', 
                Time_IN='$time_in', Time_OUT='$time_out', Hours='$hours', Role='$role', Remarks='$remarks', 
                Deductions='$deductions', Short_Misload_Bonus_SIL='$short_misload_bonus_sil' 
                WHERE id='$id'";

        if ($conn->query($sql) === TRUE) {
            echo "<div class='alert success'>Record updated successfully!</div>";
        } else {
            echo "<div class='alert error'>Error: " . $conn->error . "</div>";
        }
    }
}

// Handle delete request (single record)
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "DELETE FROM timesheet WHERE id='$id'";

    if ($conn->query($sql) === TRUE) {
        echo "<div class='alert success'>Record deleted successfully!</div>";
    } else {
        echo "<div class='alert error'>Error: " . $conn->error . "</div>";
    }
}

// Handle delete all request
if (isset($_GET['clear_timesheet'])) {
    $conn->query("DELETE FROM timesheet");
    echo "<div class='alert success'>All records deleted successfully!</div>";
    
    // If redirected from batch_upload.php, go back there
    if (isset($_GET['redirect']) && $_GET['redirect'] === 'batch_upload.php') {
        header("Location: batch_upload.php");
        exit();
    }
}

// Automatically show table if data exists (no need for ?show_timesheet=1)
$records = null;
$show_table = false;

// Check if any data exists
$total_result = $conn->query("SELECT COUNT(*) as total FROM timesheet");
if (!$total_result) {
    die("Error executing query: " . $conn->error);
}
$total_rows = $total_result->fetch_assoc()['total'];
$show_table = $total_rows > 0;

// Fetch distinct dates for the date dropdown
$date_options = [];
if ($show_table) {
    $date_query = $conn->query("SELECT DISTINCT Date FROM timesheet ORDER BY STR_TO_DATE(Date, '%Y-%m-%d') ASC");
    while ($row = $date_query->fetch_assoc()) {
        $date_options[] = $row['Date'];
    }
}

if ($show_table) {
    // Build the base query with optional search filters
    $where_clauses = [];
    $params = [];
    $types = "";

    if (!empty($_GET['search_name'])) {
        $where_clauses[] = "Name LIKE ?";
        $params[] = "%" . $_GET['search_name'] . "%";
        $types .= "s";
    }
    if (!empty($_GET['search_date'])) {
        $where_clauses[] = "Date LIKE ?";
        $params[] = "%" . $_GET['search_date'] . "%";
        $types .= "s";
    }
    if (!empty($_GET['search_shift_no'])) {
        $where_clauses[] = "Shift_No LIKE ?";
        $params[] = "%" . $_GET['search_shift_no'] . "%";
        $types .= "s";
    }
    if (!empty($_GET['search_business_unit'])) {
        $where_clauses[] = "Business_Unit LIKE ?";
        $params[] = "%" . $_GET['search_business_unit'] . "%";
        $types .= "s";
    }
    if (!empty($_GET['search_role'])) {
        $where_clauses[] = "Role LIKE ?";
        $params[] = "%" . $_GET['search_role'] . "%";
        $types .= "s";
    }

    $where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

    // Get total rows for pagination (filtered)
    $total_query = "SELECT COUNT(*) as total FROM timesheet $where_sql";
    $stmt_total = $conn->prepare($total_query);
    if (!empty($params)) {
        $stmt_total->bind_param($types, ...$params);
    }
    $stmt_total->execute();
    $total_result = $stmt_total->get_result();
    $total_rows = $total_result->fetch_assoc()['total'];
    $stmt_total->close();

    // Get all results (no pagination)
    $query = "SELECT * FROM timesheet $where_sql ORDER BY Date DESC, Time_IN DESC";
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $records = $stmt->get_result();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll System - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            transition: background 0.3s, color 0.3s;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #1a202c;
        }
        .dark-mode {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%) !important;
            color: #ffffff;
        }
        
        /* Dark mode for cards and sections */
        .dark-mode header,
        .dark-mode section,
        .dark-mode .form-card,
        .dark-mode .table-card {
            background: rgba(30, 30, 46, 0.95) !important;
            border: 1px solid rgba(102, 126, 234, 0.2);
        }
        
        .dark-mode h1, 
        .dark-mode h2,
        .dark-mode h3,
        .dark-mode label {
            color: #e0e0e0 !important;
        }
        
        /* Dark mode for forms and inputs */
        .dark-mode input, 
        .dark-mode select, 
        .dark-mode textarea {
            background-color: rgba(50, 50, 70, 0.8) !important;
            color: #ffffff !important;
            border: 1px solid rgba(102, 126, 234, 0.3) !important;
        }
        
        .dark-mode input::placeholder {
            color: #a0a0a0 !important;
        }
        
        .dark-mode input:focus,
        .dark-mode select:focus {
            border-color: #667eea !important;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2) !important;
        }
        
        /* Dark mode for table */
        .dark-mode table {
            background-color: rgba(30, 30, 46, 0.95) !important;
            color: #ffffff;
        }
        
        .dark-mode th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            color: white !important;
            border-color: rgba(102, 126, 234, 0.3);
        }
        
        .dark-mode td {
            border-color: rgba(102, 126, 234, 0.2);
            color: #e0e0e0;
        }
        
        .dark-mode tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.1) !important;
        }
        
        /* Dark mode for alerts */
        .dark-mode .alert {
            background-color: rgba(50, 50, 70, 0.9) !important;
            color: #ffffff;
            border: 1px solid rgba(102, 126, 234, 0.3);
        }
        
        .dark-mode .alert.success {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%) !important;
            color: #ffffff;
        }
        
        .dark-mode .alert.error {
            background: linear-gradient(135deg, #f56565 0%, #c53030 100%) !important;
            color: #ffffff;
        }
        
        .container {
            max-width: 95%;
            margin: 0 auto;
            padding: 2rem;
        }
        
        header {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        header h1 {
            color: #1a202c;
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }
        
        .theme-toggle {
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .theme-toggle:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        
        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-section, .action-section, .table-section {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        h2 {
            color: #1a202c;
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }
        
        .entry-form .input-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        input, select {
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
            width: 100%;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        input::placeholder {
            color: #a0aec0;
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
            margin: 0.25rem;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .action-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            padding: 2rem;
        }
        
        .action-section button {
            width: 100%;
            padding: 1.25rem;
            font-size: 1.05rem;
        }
        
        .search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding: 1.5rem;
            background: #f7fafc;
            border-radius: 15px;
        }
        
        .dark-mode .search-form {
            background: rgba(50, 50, 70, 0.6) !important;
            border: 1px solid rgba(102, 126, 234, 0.3);
        }
        
        .search-form > div {
            position: relative;
        }
        
        .search-form input[type="text"],
        .search-form select {
            width: 100%;
        }
        
        .table-wrapper {
            overflow-x: auto;
            margin-top: 1rem;
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        
        th, td {
            padding: 0.75rem 0.5rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }
        
        th {
            background: #f7fafc;
            color: #2d3748;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
        }
        
        tbody tr {
            transition: all 0.2s;
        }
        
        tbody tr:hover {
            background: #f7fafc;
        }
        
        tbody tr:last-child td {
            border-bottom: none;
        }
        
        td a {
            color: #e53e3e;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        td a:hover {
            color: #c53030;
        }
        
        .pagination {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        
        .pagination button {
            padding: 0.5rem 1rem;
            margin: 0;
        }
        
        .pagination button.active {
            background: linear-gradient(135deg, #5568d3 0%, #653a8b 100%);
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: #ffffff;
            padding: 2rem;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-content h2 {
            margin-top: 0;
            margin-bottom: 1.5rem;
        }
        
        .modal-content .input-group {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .icon {
            margin-right: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            header {
                flex-direction: column;
                text-align: center;
            }
            
            header h1 {
                font-size: 1.5rem;
            }
            
            .action-section {
                grid-template-columns: 1fr;
            }
            
            .search-form {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 0.85rem;
            }
            
            th, td {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-calculator icon"></i>Payroll Dashboard</h1>
            <button id="theme-toggle" class="theme-toggle"><i class="fas fa-moon icon"></i>Dark Mode</button>
        </header>

        <section class="form-section">
            <h2 id="form-title"><i class="fas fa-plus-circle icon"></i>Add New Entry</h2>
            <form method="POST" class="entry-form" id="entry-form">
                <input type="hidden" name="id" id="entry-id">
                <div class="input-group">
                    <input type="date" name="date" required placeholder="Date">
                    <input type="text" name="shift_no" placeholder="Shift No." required>
                    <input type="text" name="business_unit" placeholder="Business Unit" required>
                    <input type="text" name="name" placeholder="Employee Name" required>
                </div>
                <div class="input-group">
                    <input type="time" name="time_in" required placeholder="Time In">
                    <input type="time" name="time_out" required placeholder="Time Out">
                    <input type="number" name="hours" placeholder="Hours" step="0.01" required>
                    <input type="text" name="role" placeholder="Role" required>
                </div>
                <div class="input-group">
                    <input type="text" name="remarks" placeholder="Remarks">
                    <input type="number" name="deductions" placeholder="Deductions" step="0.01">
                    <input type="text" name="short_misload_bonus_sil" placeholder="Short/Misload/Bonus/SIL">
                </div>
                <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
                    <button type="submit" name="insert"><i class="fas fa-save icon"></i>Add Entry</button>
                    <button type="submit" name="update" style="display:none;"><i class="fas fa-edit icon"></i>Update Entry</button>
                    <button type="button" id="cancel-edit" style="display:none;"><i class="fas fa-times icon"></i>Cancel Edit</button>
                </div>
            </form>
        </section>

        <section class="action-section">
            <h2 style="grid-column: 1 / -1;"><i class="fas fa-tools icon"></i>Quick Actions</h2>
            <button onclick="location.href='batch_upload.php'"><i class="fas fa-file-upload icon"></i>Batch Upload</button>
            <button onclick="location.href='salary_summary.php'"><i class="fas fa-chart-bar icon"></i>Salary Summary</button>
            <button onclick="location.href='payslip_details.php'"><i class="fas fa-file-invoice icon"></i>Individual Payslips</button>
            <button onclick="location.href='send_payslips.php'"><i class="fas fa-envelope icon"></i>Send Payslips</button>
            <button onclick="location.href='download_payslip.php'"><i class="fas fa-download icon"></i>Download Payslip</button>
        </section>

        <section class="table-section">
            <h2><i class="fas fa-table icon"></i>Timesheet Entries</h2>

            <?php if (isset($_GET['upload']) && $_GET['upload'] === 'success'): ?>
                <div class='alert success'><i class="fas fa-check-circle icon"></i>Batch upload completed successfully! Data is now displayed below.</div>
            <?php endif; ?>

            <?php if (!$show_table): ?>
                <p>No data to display. Please upload a timesheet first.</p>
            <?php else: ?>
                <!-- Search Form -->
                <form method="GET" class="search-form">
                    <div style="position: relative;">
                        <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #a0aec0;"></i>
                        <input type="text" name="search_name" placeholder="Search by Name" value="<?php echo htmlspecialchars($_GET['search_name'] ?? ''); ?>" style="padding-left: 36px;">
                    </div>
                    
                    <div style="position: relative;">
                        <i class="far fa-calendar-alt" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #a0aec0; pointer-events: none; z-index: 1;"></i>
                        <select name="search_date" style="padding-left: 36px;">
                            <option value="">All Dates</option>
                            <?php foreach ($date_options as $date): ?>
                                <option value="<?php echo htmlspecialchars($date); ?>" <?php if (isset($_GET['search_date']) && $_GET['search_date'] === $date) echo 'selected'; ?>><?php echo htmlspecialchars($date); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div style="position: relative;">
                        <i class="fas fa-moon" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #a0aec0; pointer-events: none; z-index: 1;"></i>
                        <select name="search_shift_no" style="padding-left: 36px;">
                            <option value="">All Shifts</option>
                            <option value="1" <?php if (isset($_GET['search_shift_no']) && $_GET['search_shift_no'] === '1') echo 'selected'; ?>>Shift 1</option>
                            <option value="2" <?php if (isset($_GET['search_shift_no']) && $_GET['search_shift_no'] === '2') echo 'selected'; ?>>Shift 2</option>
                            <option value="3" <?php if (isset($_GET['search_shift_no']) && $_GET['search_shift_no'] === '3') echo 'selected'; ?>>Shift 3</option>
                        </select>
                    </div>
                    
                    <div style="position: relative;">
                        <i class="fas fa-building" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #a0aec0; pointer-events: none; z-index: 1;"></i>
                        <select name="search_business_unit" style="padding-left: 36px;">
                            <option value="">All Units</option>
                            <option value="Service Crew" <?php if (isset($_GET['search_business_unit']) && $_GET['search_business_unit'] === 'Service Crew') echo 'selected'; ?>>Service Crew</option>
                            <option value="Canteen" <?php if (isset($_GET['search_business_unit']) && $_GET['search_business_unit'] === 'Canteen') echo 'selected'; ?>>Canteen</option>
                            <option value="Satellite Office" <?php if (isset($_GET['search_business_unit']) && $_GET['search_business_unit'] === 'Satellite Office') echo 'selected'; ?>>Satellite Office</option>
                            <option value="Main Office" <?php if (isset($_GET['search_business_unit']) && $_GET['search_business_unit'] === 'Main Office') echo 'selected'; ?>>Main Office</option>
                        </select>
                    </div>
                    
                    <div style="position: relative;">
                        <i class="fas fa-user-tag" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #a0aec0; pointer-events: none; z-index: 1;"></i>
                        <select name="search_role" style="padding-left: 36px;">
                            <option value="">All Roles</option>
                            <option value="Crew" <?php if (isset($_GET['search_role']) && $_GET['search_role'] === 'Crew') echo 'selected'; ?>>Crew</option>
                            <option value="Cashier" <?php if (isset($_GET['search_role']) && $_GET['search_role'] === 'Cashier') echo 'selected'; ?>>Cashier</option>
                        </select>
                    </div>
                    
                    <button type="submit"><i class="fas fa-search icon"></i>Search</button>
                    <a href="index.php" style="text-decoration: none;"><button type="button"><i class="fas fa-redo icon"></i>Clear</button></a>
                </form>

                <button onclick="if(confirm('Are you sure you want to delete all timesheet records?')) location.href='index.php?clear_timesheet=1';" style="background: linear-gradient(135deg, #f56565 0%, #c53030 100%); margin-bottom: 1rem;">
                    <i class="fas fa-trash-alt icon"></i>Delete All Records
                </button>
                
                <div class="table-wrapper" style="max-height: 600px; overflow-y: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Shift No.</th>
                            <th>Business Unit</th>
                            <th>Name</th>
                            <th>Time IN</th>
                            <th>Time OUT</th>
                            <th>Hours</th>
                            <th>Role</th>
                            <th>Remarks</th>
                            <th>Deductions</th>
                            <th>Short/Misload/Bonus/SIL</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $records->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['Date']); ?></td>
                                <td><?php echo htmlspecialchars($row['Shift_No']); ?></td>
                                <td><?php echo htmlspecialchars($row['Business_Unit']); ?></td>
                                <td><?php echo htmlspecialchars($row['Name']); ?></td>
                                <td><?php echo htmlspecialchars($row['Time_IN']); ?></td>
                                <td><?php echo htmlspecialchars($row['Time_OUT']); ?></td>
                                <td><?php echo htmlspecialchars($row['Hours']); ?></td>
                                <td><?php echo htmlspecialchars($row['Role']); ?></td>
                                <td><?php echo htmlspecialchars($row['Remarks'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['Deductions'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['Short_Misload_Bonus_SIL'] ?? ''); ?></td>
                                <td>
                                    <button onclick="editEntry(<?php echo htmlspecialchars(json_encode($row)); ?>)" style="padding: 0.5rem 1rem; margin: 0 0.25rem;"><i class="fas fa-edit"></i></button>
                                    <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this entry?');" style="background: linear-gradient(135deg, #f56565 0%, #c53030 100%); color: white; padding: 0.5rem 1rem; border-radius: 8px; text-decoration: none; display: inline-block;"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                </div>

                <div style="text-align: center; padding: 1rem; color: white; opacity: 0.8;">
                    <i class="fas fa-table icon"></i>Showing all <?php echo $total_rows; ?> records
                </div>
            <?php endif; ?>
        </section>
    </div>
    
    <script>
    const themeToggleButton = document.getElementById('theme-toggle');
    const currentTheme = localStorage.getItem('theme') || 'light';
    document.body.classList.toggle('dark-mode', currentTheme === 'dark'); // Fixed: Use 'dark-mode' to match CSS

    themeToggleButton.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode'); // Fixed: Use 'dark-mode'
        const newTheme = document.body.classList.contains('dark-mode') ? 'dark' : 'light';
        localStorage.setItem('theme', newTheme);
    });

    function editEntry(rowData) {
        // Populate modal fields
        document.getElementById('modal-entry-id').value = rowData.id;
        document.querySelector('#edit-modal input[name="date"]').value = rowData.Date;
        document.querySelector('#edit-modal input[name="shift_no"]').value = rowData.Shift_No;
        document.querySelector('#edit-modal input[name="business_unit"]').value = rowData.Business_Unit;
        document.querySelector('#edit-modal input[name="name"]').value = rowData.Name;
        document.querySelector('#edit-modal input[name="time_in"]').value = rowData.Time_IN;
        document.querySelector('#edit-modal input[name="time_out"]').value = rowData.Time_OUT;
        document.querySelector('#edit-modal input[name="hours"]').value = rowData.Hours;
        document.querySelector('#edit-modal input[name="role"]').value = rowData.Role;
        document.querySelector('#edit-modal input[name="remarks"]').value = rowData.Remarks || '';
        document.querySelector('#edit-modal input[name="deductions"]').value = rowData.Deductions || '';
        document.querySelector('#edit-modal input[name="short_misload_bonus_sil"]').value = rowData.Short_Misload_Bonus_SIL || '';

        // Show modal
        document.getElementById('edit-modal').style.display = 'flex';

        // Attach event listeners after modal is shown
        document.getElementById('cancel-edit-modal').onclick = () => {
            document.getElementById('edit-modal').style.display = 'none';
        };
        document.getElementById('edit-modal').onclick = (e) => {
            if (e.target === document.getElementById('edit-modal')) {
                document.getElementById('edit-modal').style.display = 'none';
            }
        };
    }

    // Handle Individual Payslip Modal
    const payslipBtn = document.getElementById('individual-payslip-btn');
    const payslipModal = document.getElementById('payslip-modal');
    const closePayslipModal = document.getElementById('close-payslip-modal');

    payslipBtn.addEventListener('click', () => {
        console.log('Payslip button clicked'); // Debug: Check if this logs in console
        payslipModal.style.display = 'flex';
    });

    closePayslipModal.addEventListener('click', () => {
        payslipModal.style.display = 'none';
    });

    payslipModal.addEventListener('click', (e) => {
        if (e.target === payslipModal) {
            payslipModal.style.display = 'none';
        }
    });
    </script>


    <!-- Edit Modal -->
    <div id="edit-modal" class="modal">
        <div class="modal-content">
            <h2>Edit Entry</h2>
            <form method="POST" id="edit-form">
                <input type="hidden" name="id" id="modal-entry-id">
                <div class="input-group">
                    <input type="date" name="date" required>
                    <input type="text" name="shift_no" placeholder="Shift No." required>
                    <input type="text" name="business_unit" placeholder="Business Unit" required>
                    <input type="text" name="name" placeholder="Name" required>
                </div>
                <div class="input-group">
                    <input type="time" name="time_in" required>
                    <input type="time" name="time_out" required>
                    <input type="number" name="hours" placeholder="Hours" required>
                    <input type="text" name="role" placeholder="Role" required>
                </div>
                <div class="input-group">
                    <input type="text" name="remarks" placeholder="Remarks">
                    <input type="number" name="deductions" placeholder="Deductions">
                    <input type="number" name="short_misload_bonus_sil" placeholder="Short/Misload/Bonus/SIL">
                </div>
                <div class="modal-buttons">
                    <button type="submit" name="update">Update</button>
                    <button type="button" id="cancel-edit-modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Individual Payslip Modal -->
    <div id="payslip-modal" class="modal">
        <div class="modal-content" style="width: 90%; max-width: 800px;">
            <span id="close-payslip-modal" style="float: right; cursor: pointer; font-size: 24px;">&times;</span>
            <h2>Individual Payslip Details</h2>
            <iframe id="payslip-iframe" src="payslip_details.php" style="width: 100%; height: 600px; border: none;"></iframe>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>
