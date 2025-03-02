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

$case_id = $_GET['case_id'] ?? $_SESSION['case_id'] ?? null;
if (!$case_id) {
    die("Invalid case ID.");
}

// Fetch case details
$sql = "SELECT * FROM cases WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $case_id);
$stmt->execute();
$result = $stmt->get_result();
$case = $result->fetch_assoc();
$stmt->close();

if (!$case) {
    die("Case not found.");
}

// Questions sequence
$questions = [
    "permanent_home" => "Do you have a permanent home in Country A, Country B, both, or neither?",
    "vital_interests" => "Do you have vital interests in Country A, Country B, both, or neither?",
    "habitual_abode" => "Do you have a habitual abode in Country A, Country B, both, or neither?",
    "citizenship" => "Are you a citizen of Country A, Country B, both, or neither?"
];

// Get current step
$sql = "SELECT * FROM tax_residency_questions WHERE case_id = ? ORDER BY id DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $case_id);
$stmt->execute();
$result = $stmt->get_result();
$last_question = $result->fetch_assoc();
$stmt->close();

$current_step = $last_question ? array_search($last_question['question_key'], array_keys($questions)) + 1 : 0;

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $question_key = array_keys($questions)[$current_step];
    $answer = $_POST['answer'];

    // Save the answer
    $sql = "INSERT INTO tax_residency_questions (case_id, question_key, answer) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $case_id, $question_key, $answer);
    $stmt->execute();
    $stmt->close();

    // Determine residency if possible
    if ($answer === "Country A" || $answer === "Country B") {
        $sql = "UPDATE cases SET double_tax_residency = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $answer, $case_id);
        $stmt->execute();
        $stmt->close();
        header("Location: result.php?case_id=$case_id");
        exit();
    } elseif ($current_step + 1 < count($questions)) {
        header("Location: double_tax_treaty.php?case_id=$case_id");
        exit();
    } else {
        // If all questions answered without determination, fallback conclusion
        $sql = "UPDATE cases SET double_tax_residency = 'Undetermined, requires manual review' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $case_id);
        $stmt->execute();
        $stmt->close();
        header("Location: result.php?case_id=$case_id");
        exit();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Double Tax Treaty Evaluation</title>
</head>
<body>
    <h1>Determine Tax Residency for <?php echo htmlspecialchars($case['case_name']); ?></h1>
    
    <form method="post">
        <p><?php echo $questions[array_keys($questions)[$current_step]]; ?></p>
        <select name="answer" required>
            <option value="Country A">Country A</option>
            <option value="Country B">Country B</option>
            <option value="both">Both Countries</option>
            <option value="neither">Neither</option>
        </select>
        <button type="submit">Next</button>
    </form>
</body>
</html>
<?php $conn->close(); ?>
