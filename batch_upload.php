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
            background: linear-gradient(135deg, #e3f2fd 0%, #e1f5fe 100%);
            border-left: 4px solid #2196f3;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .info-banner h3 {
            color: #1565c0;
            font-size: 1.2rem;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-banner ul {
            margin-left: 25px;
            color: #495057;
        }

        .info-banner li {
            margin: 8px 0;
        }

        .warning-banner {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            border-left: 4px solid #ff9800;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .warning-banner h3 {
            color: #e65100;
            font-size: 1.1rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .warning-banner p {
            color: #495057;
            margin: 5px 0;
        }

        .upload-section {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .upload-section h2 {
            color: #495057;
            font-size: 1.5rem;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .file-upload-area {
            background: white;
            border: 3px dashed #dee2e6;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .file-upload-area:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }

        .file-upload-area i {
            font-size: 4rem;
            color: #667eea;
            margin-bottom: 20px;
        }

        .file-upload-area p {
            font-size: 1.1rem;
            color: #6c757d;
            margin-bottom: 15px;
        }

        .file-upload-area .file-info {
            font-size: 0.9rem;
            color: #adb5bd;
        }

        input[type="file"] {
            display: none;
        }

        .file-selected {
            background: #e8f5e9;
            border-color: #4caf50;
            padding: 20px;
            border-radius: 10px;
            margin-top: 15px;
            display: none;
        }

        .file-selected.show {
            display: block;
        }

        .file-selected i {
            color: #4caf50;
            margin-right: 10px;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: center;
        }

        .btn {
            padding: 15px 35px;
            font-size: 1.1rem;
            font-weight: 600;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:disabled {
            background: #dee2e6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-secondary:hover {
            background: #667eea;
            color: white;
        }

        .csv-format {
            background: white;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }

        .csv-format h4 {
            color: #495057;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .csv-format code {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            display: block;
            font-size: 0.9rem;
            color: #495057;
            overflow-x: auto;
            white-space: nowrap;
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.8rem;
            }

            .content {
                padding: 20px;
            }

            .button-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <i class="fas fa-file-upload"></i>
                Batch Upload Timesheet
            </h1>
            <p>Upload CSV file to import employee timesheet data</p>
        </div>

        <div class="content">
            <div class="info-banner">
                <h3><i class="fas fa-info-circle"></i> Upload Instructions</h3>
                <ul>
                    <li>Upload a CSV file containing employee timesheet data</li>
                    <li>File must include all 11 required columns in the correct order</li>
                    <li>Dates must be in YYYY-MM-DD format</li>
                    <li>Maximum file size: 5MB</li>
                </ul>
            </div>

            <div class="warning-banner">
                <h3><i class="fas fa-exclamation-triangle"></i> Important Note</h3>
                <p>If data already exists in the database, you will be prompted to clear it before uploading new data.</p>
            </div>

            <div class="upload-section">
                <h2><i class="fas fa-cloud-upload-alt"></i> Select CSV File</h2>
                
                <form method="POST" enctype="multipart/form-data" id="uploadForm">
                    <label for="csvFile">
                        <div class="file-upload-area" id="uploadArea">
                            <i class="fas fa-file-csv"></i>
                            <p><strong>Click to select CSV file</strong></p>
                            <p class="file-info">or drag and drop here</p>
                            <p class="file-info">Supported format: .csv (max 5MB)</p>
                        </div>
                    </label>
                    <input type="file" name="file" id="csvFile" accept=".csv" required>
                    
                    <div class="file-selected" id="fileSelected">
                        <i class="fas fa-check-circle"></i>
                        <strong>File selected:</strong> <span id="fileName"></span>
                    </div>

                    <div class="csv-format">
                        <h4><i class="fas fa-table"></i> Required CSV Format</h4>
                        <code>Date, Shift_No, Business_Unit, Name, Time_IN, Time_OUT, Hours, Role, Remarks, Deductions, Short_Misload_Bonus_SIL</code>
                        <p style="margin-top: 10px; font-size: 0.9rem; color: #6c757d;">Example: 2024-01-15, 1, Unit A, John Doe, 08:00, 17:00, 8, Cashier, , 0, 0</p>
                    </div>

                    <div class="button-group">
                        <button type="submit" class="btn btn-primary" id="uploadBtn" disabled>
                            <i class="fas fa-upload"></i>
                            Upload CSV File
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const fileInput = document.getElementById('csvFile');
        const uploadArea = document.getElementById('uploadArea');
        const fileSelected = document.getElementById('fileSelected');
        const fileName = document.getElementById('fileName');
        const uploadBtn = document.getElementById('uploadBtn');

        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                fileName.textContent = this.files[0].name;
                fileSelected.classList.add('show');
                uploadBtn.disabled = false;
                uploadArea.style.borderColor = '#4caf50';
                uploadArea.style.background = '#e8f5e9';
            }
        });

        // Drag and drop functionality
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#667eea';
            this.style.background = '#f8f9ff';
        });

        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = '#dee2e6';
            this.style.background = 'white';
        });

        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            const files = e.dataTransfer.files;
            if (files.length > 0 && files[0].name.endsWith('.csv')) {
                fileInput.files = files;
                fileName.textContent = files[0].name;
                fileSelected.classList.add('show');
                uploadBtn.disabled = false;
                this.style.borderColor = '#4caf50';
                this.style.background = '#e8f5e9';
            }
        });
    </script>
</body>
</html>