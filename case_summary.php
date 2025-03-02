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

// Fetch case details including answers
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

// Decode submitted answers from JSON
$submitted_answers = json_decode($case['answers'], true);

// Fetch double tax treaty questions and answers
$sql = "SELECT question_key, answer FROM tax_residency_questions WHERE case_id = ? ORDER BY id ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $case_id);
$stmt->execute();
$result = $stmt->get_result();
$tax_treaty_answers = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Questions mapping
$questions = [
    "permanent_home" => "Do you have a permanent home in Country A, Country B, both, or neither?",
    "vital_interests" => "Do you have vital interests in Country A, Country B, both, or neither?",
    "habitual_abode" => "Do you have a habitual abode in Country A, Country B, both, or neither?",
    "citizenship" => "Are you a citizen of Country A, Country B, both, or neither?"
];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Case Summary</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            color: #333;
        }
        
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        h2 {
            color: #2980b9;
            margin-top: 30px;
            border-left: 4px solid #3498db;
            padding-left: 10px;
        }
        
        .case-info {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
        }
        
        .conclusion {
            background-color: #e8f4fc;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #2ecc71;
        }
        
        .data-section {
            background-color: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        ul {
            list-style-type: none;
            padding-left: 0;
        }
        
        li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        
        strong {
            color: #555;
            display: inline-block;
            width: 200px;
        }
        
        .answer {
            display: inline-block;
            font-weight: normal;
        }
    </style>
</head>
<body>
    <?php include('navbar.php'); ?>
    
    <h1>Case Summary for <?php echo htmlspecialchars($case['case_name']); ?></h1>
    
    <div class="case-info">
        <p><strong>Case ID:</strong> <span class="answer"><?php echo htmlspecialchars($case['id']); ?></span></p>
        <p><strong>Initial Tax Liability:</strong> <span class="answer"><?php echo htmlspecialchars($case['initial_liability']); ?></span></p>
        <p><strong>Tax Question:</strong> <span class="answer"><?php echo htmlspecialchars($case['tax_question']); ?></span></p>
        <p><strong>Created At:</strong> <span class="answer"><?php echo htmlspecialchars($case['created_at']); ?></span></p>
    </div>

    <h2>Conclusion</h2>
    <div class="conclusion">
        <p><strong>Tax Residency:</strong> <span class="answer"><?php echo ($case['conclusion'] ?? 'Pending'); ?></span></p>
        <p><strong>Double Tax Residency:</strong> <span class="answer"><?php echo htmlspecialchars($case['double_tax_residency'] ?? 'Pending'); ?></span></p>
    </div>
    
    <h2>Submitted Answers</h2>
    <div class="data-section">
        <ul>
            <?php 
            if (!empty($submitted_answers)) {
                foreach ($submitted_answers as $question => $answer) {
                    echo "<li><strong>" . htmlspecialchars(ucwords(str_replace('_', ' ', $question))) . ":</strong> <span class='answer'>" . htmlspecialchars($answer) . "</span></li>";
                }
            } else {
                echo "<li>No answers submitted yet.</li>";
            }
            ?>
        </ul>
    </div>
    
    <h2>Double Tax Treaty Answers</h2>
    <div class="data-section">
        <ul>
            <?php foreach ($tax_treaty_answers as $entry) { ?>
                <li><strong><?php echo htmlspecialchars($questions[$entry['question_key']] ?? $entry['question_key']); ?>:</strong> <span class="answer"><?php echo htmlspecialchars($entry['answer']); ?></span></li>
            <?php } ?>
        </ul>
    </div>
</body>
</html>
<?php $conn->close(); ?>
