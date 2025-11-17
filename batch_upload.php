<?php
include 'db_connect.php';

// Check if data already exists in the timesheet table
$result = $conn->query("SELECT COUNT(*) as count FROM timesheet");
if (!$result) {
    die("Error checking database: " . $conn->error);
}
$row = $result->fetch_assoc();
$count = $row['count'];

if ($count > 0) {
    // Data exists: Show message with count and option to clear
    echo "<script>
        var proceed = confirm('Data already exists in the database ($count rows). Please clear the existing data before uploading a new CSV. Click OK to clear all data and proceed with upload, or Cancel to go back.');
        if (proceed) {
            // Clear the table
            window.location.href = 'index.php?clear_timesheet=1&redirect=batch_upload.php';
        } else {
            // Go back to dashboard
            window.location.href = 'index.php';
        }
    </script>";
    exit();
}

// Proceed with upload handling if no data exists
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    
    // Validate the uploaded file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo "<script>alert('File upload error: " . $file['error'] . "');</script>";
        exit();
    }
    if ($file['type'] !== 'text/csv' && !preg_match('/\.csv$/i', $file['name'])) {
        echo "<script>alert('Invalid file type. Please upload a CSV file.');</script>";
        exit();
    }
    if ($file['size'] > 5 * 1024 * 1024) { // Limit to 5MB for safety
        echo "<script>alert('File is too large. Maximum size is 5MB.');</script>";
        exit();
    }
    
    // Open the CSV file
    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) {
        echo "<script>alert('Error opening the CSV file.');</script>";
        exit();
    }
    
    // Prepare the INSERT statement (using prepared statements for security)
    // Note: We're inserting into the first 11 columns; ID auto-increments, is_deleted defaults to 0
    $stmt = $conn->prepare("INSERT INTO timesheet (Date, Shift_No, Business_Unit, Name, Time_IN, Time_OUT, Hours, Role, Remarks, Deductions, Short_Misload_Bonus_SIL) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        echo "<script>alert('Database error: Could not prepare statement.');</script>";
        fclose($handle);
        exit();
    }
    
    // Skip the header row if present (uncomment if your CSV has headers)
    // fgetcsv($handle);
    
    $rowsInserted = 0;
    $errors = [];
    
    // Process each row
	while (($data = fgetcsv($handle, 1000, ",")) !== false) {
		// Ensure exactly 11 columns (skip malformed rows)
		if (count($data) !== 11) {
        $errors[] = "Skipped row with " . count($data) . " columns (expected 11).";
        continue;
		}
    
		// Convert date to standard YYYY-MM-DD format if needed
		$data[0] = date('Y-m-d', strtotime($data[0]));
    
		// Bind parameters (assuming data types match your table: date, varchar, etc.)
		$stmt->bind_param("ssssssissss", $data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], $data[7], $data[8], $data[9], $data[10]);
    
		if (!$stmt->execute()) {
			$errors[] = "Error inserting row: " . $stmt->error;
		} else {
			$rowsInserted++;
		}
}
    
    fclose($handle);
    $stmt->close();
    
    // Show results and redirect
    if ($rowsInserted > 0) {
        $message = "Batch upload successful! $rowsInserted rows inserted.";
        if (!empty($errors)) {
            $message .= " Warnings: " . implode("; ", $errors);
        }
        echo "<script>
            alert('$message');
            window.location.href = 'index.php?show_timesheet=1&upload=success';
        </script>";
    } else {
        $errorMsg = "No rows inserted. Errors: " . implode("; ", $errors);
        echo "<script>alert('$errorMsg');</script>";
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch Upload</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h2>Batch Upload Timesheet</h2>
    <p>Upload a CSV file with columns: Date, Shift No., Business Unit, Name, Time IN, Time OUT, Hours, Role, Remarks, Deductions, Short/Misload/Bonus/SIL.</p>
    <p><strong>Note:</strong> Ensure dates are in YYYY-MM-DD format. If your CSV has a header row, the code will skip it (currently commented out).</p>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="file" accept=".csv" required>
        <button type="submit">Upload CSV</button>
    </form>
    <br>
    <a href="index.php">Back to Dashboard</a>
</body>
</html>
