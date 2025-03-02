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

// Get the case ID
$case_id = $_GET['case_id'] ?? $_SESSION['case_id'] ?? null;
if (!$case_id) {
    die("Invalid case ID.");
}

// Fetch case details to ensure it exists
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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Capture all form responses
    $formData = $_POST;
    $stayPermanent = $formData['stayPermanent'] ?? '';
    $taxResidentA10 = $formData['taxResidentA10'] ?? '';
    $stayA61 = $formData['stayA61'] ?? '';
    $homeAccessA = $formData['homeAccessA'] ?? '';
    $threeYearRule = $formData['threeYearRule'] ?? '';
    $residentTaxB = $formData['residentTaxB'] ?? '';

    $conclusion = '';

    // Comprehensive decision tree logic based on our flowchart
    if ($stayPermanent === 'yes') {
        if ($taxResidentA10 === 'yes') {
            if ($stayA61 === 'yes') {
                $conclusion = 'The person remains a tax resident in Norway.';
                if ($residentTaxB === 'yes') {
                    $conclusion = 'Case type: <a href="double_tax_treaty.php?case_id=' . $case_id . '">Double Tax Treaty</a>';
                } else {
                    $conclusion = 'Claim not accepted: <a href="avoidance_double_tax.php">Avoidance of Double Taxation</a>';
                }
            } else {
                if ($homeAccessA === 'yes') {
                    $conclusion = 'The person remains a tax resident in Norway.';
                    if ($residentTaxB === 'yes') {
                        $conclusion = 'Case type: <a href="double_tax_treaty.php?case_id=' . $case_id . '">Double Tax Treaty</a>';
                    } else {
                        $conclusion = 'Claim not accepted: <a href="avoidance_double_tax.php">Avoidance of Double Taxation</a>';
                    }
                } else {
                    if ($threeYearRule === 'yes') {
                        $conclusion = 'The person remains a tax resident in Norway.';
                        if ($residentTaxB === 'yes') {
                            $conclusion = 'Case type: <a href="double_tax_treaty.php?case_id=' . $case_id . '">Double Tax Treaty</a>';
                        } else {
                            $conclusion = 'Claim not accepted: <a href="avoidance_double_tax.php">Avoidance of Double Taxation</a>';
                        }
                    } else {
                        $conclusion = 'Case type: <a href="termination_tax_residency.php">Termination of Tax Residency</a>';
                    }
                }
            }
        } else { // Not resident for 10+ years
            if ($stayA61 === 'yes') {
                $conclusion = 'The person remains a tax resident in Norway.';
                if ($residentTaxB === 'yes') {
                    $conclusion = 'Case type: <a href="double_tax_treaty.php?case_id=' . $case_id . '">Double Tax Treaty</a>';
                } else {
                    $conclusion = 'Claim not accepted: <a href="avoidance_double_tax.php">Avoidance of Double Taxation</a>';
                }
            } else {
                if ($homeAccessA === 'yes') {
                    $conclusion = 'The person remains a tax resident in Norway.';
                    if ($residentTaxB === 'yes') {
                        $conclusion = 'Case type: <a href="double_tax_treaty.php?case_id=' . $case_id . '">Double Tax Treaty</a>';
                    } else {
                        $conclusion = 'Claim not accepted: <a href="avoidance_double_tax.php">Avoidance of Double Taxation</a>';
                    }
                } else {
                    $conclusion = 'Case type: <a href="termination_tax_residency.php">Termination of Tax Residency</a>';
                    // $conclusion = 'Pending';
                }
            }
        }
    } else { // Not permanent stay
        $conclusion = 'The person remains a tax resident in Norway.';
        if ($residentTaxB === 'yes') {
            $conclusion = 'Case type: <a href="double_tax_treaty.php?case_id=' . $case_id . '">Double Tax Treaty</a>';
        } else {
            $conclusion = 'Claim not accepted: <a href="avoidance_double_tax.php">Avoidance of Double Taxation</a>';
        }
    }

    // Save the case information into the database
    $answers = json_encode($formData);
    $sql = "UPDATE cases SET answers = ?, conclusion = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $answers, $conclusion, $case_id);
    $stmt->execute();
    $stmt->close();

    // Redirect to case summary
    header("Location: case_summary.php?case_id=" . $case_id);
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Norwegian Tax Residency Assessment</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 20px;
            background-color:rgb(75, 111, 157);
        }
        .form-section {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            font-weight: bold;
        }
        .radio-option {
            margin: 5px 0;
        }
        button {
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <h1>Norwegian Tax Residency Assessment</h1>
    <p>Please answer the following questions to determine tax residency status.</p>
    
    <form action="dynamic_form.php?case_id=<?php echo $case_id; ?>" method="POST">
        <div class="form-section">
            <div class="form-group">
                <label>1. Has the person taken permanent residence abroad?</label>
                <div class="radio-option">
                    <input type="radio" id="stayPermanentYes" name="stayPermanent" value="yes" required> 
                    <label for="stayPermanentYes">Yes</label>
                </div>
                <div class="radio-option">
                    <input type="radio" id="stayPermanentNo" name="stayPermanent" value="no"> 
                    <label for="stayPermanentNo">No</label>
                </div>
            </div>
        </div>
        
        <!-- Conditional sections based on permanent stay -->
        <div id="permanentStaySection" class="form-section hidden">
            <div class="form-group">
                <label>2. Has the person been a tax resident in Norway for more than 10 years before moving abroad?</label>
                <div class="radio-option">
                    <input type="radio" id="taxResidentA10Yes" name="taxResidentA10" value="yes"> 
                    <label for="taxResidentA10Yes">Yes</label>
                </div>
                <div class="radio-option">
                    <input type="radio" id="taxResidentA10No" name="taxResidentA10" value="no"> 
                    <label for="taxResidentA10No">No</label>
                </div>
            </div>
            
            <div class="form-group">
                <label>3. Has the person stayed in Norway for more than 61 days in the income year?</label>
                <div class="radio-option">
                    <input type="radio" id="stayA61Yes" name="stayA61" value="yes"> 
                    <label for="stayA61Yes">Yes</label>
                </div>
                <div class="radio-option">
                    <input type="radio" id="stayA61No" name="stayA61" value="no"> 
                    <label for="stayA61No">No</label>
                </div>
            </div>
            
            <div id="homeAccessSection" class="form-group hidden">
                <label>4. Has the person or their close family had access to a home in Norway?</label>
                <div class="radio-option">
                    <input type="radio" id="homeAccessAYes" name="homeAccessA" value="yes"> 
                    <label for="homeAccessAYes">Yes</label>
                </div>
                <div class="radio-option">
                    <input type="radio" id="homeAccessANo" name="homeAccessA" value="no"> 
                    <label for="homeAccessANo">No</label>
                </div>
            </div>
            
            <div id="threeYearSection" class="form-group hidden">
                <label>5. For each of the three years after moving abroad, has the person stayed in Norway for more than 61 days OR had access to a home in Norway in ANY of these years?</label>
                <div class="radio-option">
                    <input type="radio" id="threeYearRuleYes" name="threeYearRule" value="yes"> 
                    <label for="threeYearRuleYes">Yes (in at least one of the three years)</label>
                </div>
                <div class="radio-option">
                    <input type="radio" id="threeYearRuleNo" name="threeYearRule" value="no"> 
                    <label for="threeYearRuleNo">No (meets conditions for ALL three years)</label>
                </div>
            </div>
        </div>
        
        <div id="taxResidentBSection" class="form-section hidden">
            <div class="form-group">
                <label>Is the person a resident for tax purposes in the other country?</label>
                <div class="radio-option">
                    <input type="radio" id="residentTaxBYes" name="residentTaxB" value="yes"> 
                    <label for="residentTaxBYes">Yes</label>
                </div>
                <div class="radio-option">
                    <input type="radio" id="residentTaxBNo" name="residentTaxB" value="no"> 
                    <label for="residentTaxBNo">No</label>
                </div>
            </div>
        </div>
        
        <button type="submit">Submit Assessment</button>
    </form>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get all the form elements
        const stayPermanentRadios = document.querySelectorAll('input[name="stayPermanent"]');
        const taxResidentA10Radios = document.querySelectorAll('input[name="taxResidentA10"]');
        const stayA61Radios = document.querySelectorAll('input[name="stayA61"]');
        const homeAccessRadios = document.querySelectorAll('input[name="homeAccessA"]');
        
        const permanentStaySection = document.getElementById('permanentStaySection');
        const homeAccessSection = document.getElementById('homeAccessSection');
        const threeYearSection = document.getElementById('threeYearSection');
        const taxResidentBSection = document.getElementById('taxResidentBSection');
        
        // Event listeners for stay permanent radios
        stayPermanentRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                if (radio.value === 'yes') {
                    permanentStaySection.classList.remove('hidden');
                    taxResidentBSection.classList.remove('hidden');
                } else {
                    permanentStaySection.classList.add('hidden');
                    homeAccessSection.classList.add('hidden');
                    threeYearSection.classList.add('hidden');
                    taxResidentBSection.classList.remove('hidden');
                }
            });
        });
        
        // Event listeners for tax resident A > 10 years
        taxResidentA10Radios.forEach(radio => {
            radio.addEventListener('change', updateFormVisibility);
        });
        
        // Event listeners for stay A > 61 days
        stayA61Radios.forEach(radio => {
            radio.addEventListener('change', updateFormVisibility);
        });
        
        // Event listeners for home access
        homeAccessRadios.forEach(radio => {
            radio.addEventListener('change', updateFormVisibility);
        });
        
        function updateFormVisibility() {
            const taxResidentA10 = document.querySelector('input[name="taxResidentA10"]:checked')?.value;
            const stayA61 = document.querySelector('input[name="stayA61"]:checked')?.value;
            const homeAccessA = document.querySelector('input[name="homeAccessA"]:checked')?.value;
            
            // Show/hide home access section based on stayA61
            if (stayA61 === 'no') {
                homeAccessSection.classList.remove('hidden');
            } else {
                homeAccessSection.classList.add('hidden');
                threeYearSection.classList.add('hidden');
            }
            
            // Show/hide three year rule section based on 10+ years residency and home access
            if (taxResidentA10 === 'yes' && stayA61 === 'no' && homeAccessA === 'no') {
                threeYearSection.classList.remove('hidden');
            } else {
                threeYearSection.classList.add('hidden');
            }
        }
    });
    </script>
</body>
</html>
