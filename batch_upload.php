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
    <title>Batch Upload - Payroll System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container { max-width: 800px; width: 100%; }
        .upload-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            text-align: center;
        }
        h2 { 
            color: #1a202c;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .subtitle {
            color: #718096;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        .upload-area {
            border: 3px dashed #cbd5e0;
            border-radius: 15px;
            padding: 3rem 2rem;
            margin: 2rem 0;
            transition: all 0.3s;
            background: #f7fafc;
        }
        .upload-area:hover {
            border-color: #667eea;
            background: #edf2f7;
        }
        .upload-icon {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        input[type="file"] {
            display: none;
        }
        .file-label {
            display: inline-block;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1.05rem;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .file-label:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        button { 
            padding: 1rem 2.5rem;
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1.05rem;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(72, 187, 120, 0.4);
            margin-top: 1.5rem;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(72, 187, 120, 0.6);
        }
        .back-link {
            display: inline-block;
            margin-top: 2rem;
            padding: 0.75rem 1.5rem;
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        .back-link:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }
        .requirements {
            background: #edf2f7;
            border-radius: 15px;
            padding: 1.5rem;
            margin: 2rem 0;
            text-align: left;
        }
        .requirements h3 {
            color: #2d3748;
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }
        .requirements ul {
            list-style: none;
            padding: 0;
        }
        .requirements li {
            color: #4a5568;
            padding: 0.5rem 0;
            padding-left: 1.5rem;
            position: relative;
        }
        .requirements li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #48bb78;
            font-weight: bold;
        }
        .icon { margin-right: 0.5rem; }
        @media (max-width: 768px) {
            body { padding: 1rem; }
            .upload-card { padding: 2rem 1.5rem; }
            h2 { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="upload-card">
            <h2><i class="fas fa-cloud-upload-alt icon"></i>Batch Upload Timesheet</h2>
            <p class="subtitle">Upload your CSV file to import multiple timesheet entries at once</p>
            
            <div class="requirements">
                <h3><i class="fas fa-info-circle icon"></i>Requirements</h3>
                <ul>
                    <li>CSV file with 11 columns in order</li>
                    <li>Columns: Date, Shift No., Business Unit, Name, Time IN, Time OUT, Hours, Role, Remarks, Deductions, Short/Misload/Bonus/SIL</li>
                    <li>Dates must be in YYYY-MM-DD format</li>
                    <li>Maximum file size: 5MB</li>
                </ul>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="upload-area">
                    <div class="upload-icon">
                        <i class="fas fa-file-csv"></i>
                    </div>
                    <label for="file" class="file-label">
                        <i class="fas fa-folder-open icon"></i>Choose CSV File
                    </label>
                    <input type="file" id="file" name="file" accept=".csv" required onchange="document.getElementById('file-name').textContent = this.files[0]?.name || 'No file chosen'">
                    <p id="file-name" style="margin-top: 1rem; color: #718096;">No file chosen</p>
                </div>
                <button type="submit"><i class="fas fa-upload icon"></i>Upload CSV</button>
            </form>
            
            <a href="index.php" class="back-link">
                <i class="fas fa-arrow-left icon"></i>Back to Dashboard
            </a>
        </div>
    </div>
</body>
</html>
