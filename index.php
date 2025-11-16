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
$rows_per_page = 10; // Adjust as needed
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $rows_per_page;

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
    $total_pages = ceil($total_rows / $rows_per_page);
    $stmt_total->close();

    // Get paginated results
    $query = "SELECT id, Date, Shift_No, Business_Unit, Name, Time_IN, Time_OUT, Hours, Role, Remarks, Deductions, Short_Misload_Bonus_SIL FROM timesheet $where_sql ORDER BY STR_TO_DATE(Date, '%Y-%m-%d') ASC LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    $types .= "ii";
    $params[] = $rows_per_page;
    $params[] = $offset;
    $stmt->bind_param($types, ...$params);
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
    <title>Payroll System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            transition: background-color 0.3s, color 0.3s;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #ffffff;
            color: #000000;
        }
        .dark-mode {
            background-color: #121212;
            color: #ffffff;
        }
        /* Dark mode for forms and inputs */
        .dark-mode input, .dark-mode select, .dark-mode button {
            background-color: #333333;
            color: #ffffff;
            border: 1px solid #555555;
        }
        .dark-mode input::placeholder {
            color: #cccccc;
        }
        /* Dark mode for table */
        .dark-mode table {
            background-color: #1e1e1e;
            color: #ffffff;
        }
        .dark-mode th, .dark-mode td {
            border-color: #555555;
        }
        .dark-mode th {
            background-color: #333333;
        }
        /* Dark mode for alerts */
        .dark-mode .alert {
            background-color: #333333;
            color: #ffffff;
            border-color: #555555;
        }
        .dark-mode .alert.success {
            background-color: #4caf50;
            color: #ffffff;
        }
        .dark-mode .alert.error {
            background-color: #f44336;
            color: #ffffff;
        }
        /* Dark mode for links */
        .dark-mode a {
            color: #4fc3f7;
        }
        .dark-mode a:hover {
            color: #81d4fa;
        }
        /* Other elements (e.g., headers, sections) */
        .dark-mode h1, .dark-mode h2 {
            color: #ffffff;
        }
        /* Ensure buttons and toggles are visible */
        button {
            background-color: #007bff;
            color: #ffffff;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            margin: 5px;
        }
        .dark-mode button {
            background-color: #0056b3;
        }
        button:hover {
            background-color: #0056b3;
        }
        .dark-mode button:hover {
            background-color: #003d82;
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
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }
        .dark-mode .modal-content {
            background-color: #1e1e1e;
            color: #ffffff;
        }
        .modal-content h2 {
            margin-top: 0;
        }
        .modal-content .input-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .modal-content input, .modal-content select {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .dark-mode .modal-content input, .dark-mode .modal-content select {
            background-color: #333333;
            color: #ffffff;
            border-color: #555555;
        }
        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Payroll Dashboard</h1>
            <button id="theme-toggle">Toggle Dark/Light Mode</button>
        </header>

        <section class="form-section">
            <h2 id="form-title">Add New Entry</h2>
            <form method="POST" class="entry-form" id="entry-form">
                <input type="hidden" name="id" id="entry-id">
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
                    <input type="text" name="short_misload_bonus_sil" placeholder="Short/Misload/Bonus/SIL">
                </div>
                <button type="submit" name="insert">Add Entry</button>
                <button type="submit" name="update" style="display:none;">Update Entry</button>
                <button type="button" id="cancel-edit" style="display:none;">Cancel Edit</button>
            </form>
        </section>

        <section class="action-section">
            <h2>Actions</h2>
            <button onclick="location.href='batch_upload.php'">Batch Upload Timesheet</button>
            <button onclick="location.href='salary_summary.php'">Salary Summary</button>
            <button onclick="location.href='payslip_details.php'">Individual Payslips</button>
            <button onclick="location.href='send_payslips.php'">Send Payslip Emails</button>
            <button onclick="location.href='download_payslip.php'">Download Individual Payslip</button>
        </section>

        <section class="table-section">
            <h2>Timesheet Entries</h2>

            <?php if (isset($_GET['upload']) && $_GET['upload'] === 'success'): ?>
                <div class='alert success'>Batch upload completed successfully! Data is now displayed below.</div>
            <?php endif; ?>

            <?php if (!$show_table): ?>
                <p>No data to display. Please upload a timesheet first.</p>
            <?php else: ?>
                <!-- Search Form -->
                <form method="GET" class="search-form">
                    <input type="text" name="search_name" placeholder="Search by Name" value="<?php echo htmlspecialchars($_GET['search_name'] ?? ''); ?>">
                    
                    <select name="search_date">
                        <option value="">All Dates</option>
                        <?php foreach ($date_options as $date): ?>
                            <option value="<?php echo htmlspecialchars($date); ?>" <?php if (isset($_GET['search_date']) && $_GET['search_date'] === $date) echo 'selected'; ?>><?php echo htmlspecialchars($date); ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select name="search_shift_no">
                        <option value="">All Shift Nos.</option>
                        <option value="1" <?php if (isset($_GET['search_shift_no']) && $_GET['search_shift_no'] === '1') echo 'selected'; ?>>1</option>
                        <option value="2" <?php if (isset($_GET['search_shift_no']) && $_GET['search_shift_no'] === '2') echo 'selected'; ?>>2</option>
                        <option value="3" <?php if (isset($_GET['search_shift_no']) && $_GET['search_shift_no'] === '3') echo 'selected'; ?>>3</option>
                    </select>
                    
                    <select name="search_business_unit">
                        <option value="">All Business Units</option>
                        <option value="Service Crew" <?php if (isset($_GET['search_business_unit']) && $_GET['search_business_unit'] === 'Service Crew') echo 'selected'; ?>>Service Crew</option>
                        <option value="Canteen" <?php if (isset($_GET['search_business_unit']) && $_GET['search_business_unit'] === 'Canteen') echo 'selected'; ?>>Canteen</option>
                        <option value="Satellite Office" <?php if (isset($_GET['search_business_unit']) && $_GET['search_business_unit'] === 'Satellite Office') echo 'selected'; ?>>Satellite Office</option>
                        <option value="Main Office" <?php if (isset($_GET['search_business_unit']) && $_GET['search_business_unit'] === 'Main Office') echo 'selected'; ?>>Main Office</option>
                    </select>
                    
                    <select name="search_role">
                        <option value="">All Roles</option>
                        <option value="Crew" <?php if (isset($_GET['search_role']) && $_GET['search_role'] === 'Crew') echo 'selected'; ?>>Crew</option>
                        <option value="Cashier" <?php if (isset($_GET['search_role']) && $_GET['search_role'] === 'Cashier') echo 'selected'; ?>>Cashier</option>
                    </select>
                    
                    <button type="submit">Search</button>
                    <a href="index.php"><button type="button">Clear Search</button></a>
                </form>

                <button onclick="if(confirm('Are you sure you want to delete all timesheet records?')) location.href='index.php?clear_timesheet=1';">Delete All Records</button>
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
                                    <button onclick="editEntry(<?php echo htmlspecialchars(json_encode($row)); ?>)">Edit</button>
                                    <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this entry?');">Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="pagination">
                    <?php if ($current_page > 1): ?>
                        <a href="?page=<?php echo $current_page - 1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>"><button>Previous</button></a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>">
                            <button <?php if ($i == $current_page) echo 'class="active"'; ?>><?php echo $i; ?></button>
                        </a>
                    <?php endfor; ?>
                    <?php if ($current_page < $total_pages): ?>
                        <a href="?page=<?php echo $current_page + 1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>"><button>Next</button></a>
                    <?php endif; ?>
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
