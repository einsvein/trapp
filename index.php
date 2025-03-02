<?php
session_start();
$servername = "localhost";
$username = "name";
$password = "pw";
$dbname = "taxdb";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $case_name = $_POST['case_name'] ?? 'utlandLoenn';
    $initial_liability = $_POST['initial_liability'] ?? 'resident';
    $tax_question = $_POST['tax_question'] ?? 'tax liability for salary from Country B';

    $sql = "INSERT INTO cases (case_name, initial_liability, tax_question) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $case_name, $initial_liability, $tax_question);
    $stmt->execute();
    
    $_SESSION['case_id'] = $conn->insert_id;
    
    header("Location: dynamic_form.php?case_id=" . $conn->insert_id);
    exit();
}

// Fetch previous cases
$sql = "SELECT * FROM cases ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<?php include('navbar.php'); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Tax Liability Clarification</title>
</head>
<body>
    <h1>Tax Liability Clarification</h1>
    
    <form method="post">
        <label>Case Name:</label>
        <input type="text" name="case_name" value="utlandLoenn" required><br>
        
        <label>Initial Tax Status:</label>
        <select name="initial_liability">
            <option value="resident">Resident</option>
            <option value="non-resident">Non-Resident</option>
        </select><br>
        
        <label>Tax Question:</label>
        <input type="text" name="tax_question" value="tax liability for salary from Country B" required><br>
        
        <button type="submit">Create Case</button>
    </form>

    <h2>Previous Cases</h2>
    <ul>
        <?php while ($row = $result->fetch_assoc()) { ?>
            <li>
                <strong>Case ID:</strong> <?php echo htmlspecialchars($row['id']); ?> -
                <?php echo htmlspecialchars($row['case_name']) . " - " . htmlspecialchars($row['initial_liability'])
                . " - " . htmlspecialchars($row['tax_question']) . " - " . ($row['conclusion'] ?? 'Pending'); ?> 
                - <a href="case_summary.php?case_id=<?php echo $row['id']; ?>">View Summary</a>
                - <a href="dynamic_form.php?case_id=<?php echo $row['id']; ?>">Continue</a>
                - <a href="double_tax_treaty.php?case_id=<?php echo $row['id']; ?>">Double Tax Treaty</a>
        </li>
        <?php } ?>
    </ul>
</body>
</html>

<?php $conn->close(); ?>
