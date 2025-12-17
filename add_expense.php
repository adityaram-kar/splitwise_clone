<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'functions.php';
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];

if (!isset($_GET['group_id'])) {
    header('Location: index.php');
    exit;
}
$group_id = intval($_GET['group_id']);

// Validate membership
$stmt = $pdo->prepare("SELECT * FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->execute([$group_id, $user_id]);
if ($stmt->rowCount() === 0) {
    die("You are not a member of this group.");
}

$errors = [];

// Fetch group members
$membersStmt = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name 
    FROM users u 
    JOIN group_members gm ON u.id = gm.user_id 
    WHERE gm.group_id = ?
");
$membersStmt->execute([$group_id]);
$group_members = $membersStmt->fetchAll(PDO::FETCH_ASSOC);
$member_count = count($group_members);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $amount = floatval($_POST['amount'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $paid_by = intval($_POST['paid_by'] ?? 0);
    $expense_date = $_POST['expense_date'] ?? '';

    if ($amount <= 0 || empty($description) || empty($expense_date)) {
        $errors[] = "Please fill all fields correctly.";
    } elseif ($member_count === 0) {
        $errors[] = "No group members found.";
    } else {
        $isValidPayer = false;
        foreach ($group_members as $m) {
            if ($m['id'] == $paid_by) {
                $isValidPayer = true;
                break;
            }
        }
        if (!$isValidPayer) {
            $errors[] = "Selected payer is not a member of this group.";
        }
    }

    if (empty($errors)) {
        // Insert the expense
        $stmt = $pdo->prepare("
            INSERT INTO expenses (group_id, amount, paid_by, description, expense_date) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$group_id, $amount, $paid_by, $description, $expense_date]);
        $expense_id = $pdo->lastInsertId();

        // Insert shares
        $share_each = round($amount / $member_count, 2);
        $stmt2 = $pdo->prepare("
            INSERT INTO expense_shares (expense_id, user_id, share_amount) 
            VALUES (?, ?, ?)
        ");

        foreach ($group_members as $member) {
            $stmt2->execute([$expense_id, $member['id'], $share_each]);
        }

        header("Location: expenses.php?group_id=$group_id");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add Expense to Group</title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Font Awesome for Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Your Separate CSS File -->
    <link rel="stylesheet" href="cssFiles/add_expense.css" />

    <style>
        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            display: none;
            margin-top: 5px;
        }

        .success-message {
            color: #28a745;
            font-size: 0.875rem;
            display: none;
            margin-top: 5px;
        }

        .form-control.error {
            border-color: #dc3545;
            background-color: #fff5f5;
        }

        .form-control.success {
            border-color: #28a745;
            background-color: #f5fff5;
        }

        .split-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            display: none;
        }

        .split-info.show {
            display: block;
        }
    </style>
</head>
<body>

<div class="expense-container">
    <div class="title">Add Expense to Group</div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger rounded-3 p-3 mb-4 text-center">
            <?= implode("<br>", $errors); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="" id="expenseForm">
        
        <div class="mb-3">
            <label class="form-label">Amount ($):</label>
            <input type="number" step="0.01" name="amount" id="amount" class="form-control" required />
            <div class="error-message" id="amountError">Amount must be greater than 0</div>
            <div class="success-message" id="amountSuccess">✓ Amount is valid</div>
        </div>

        <div class="mb-3">
            <label class="form-label">Description:</label>
            <input type="text" name="description" id="description" class="form-control" required />
            <div class="error-message" id="descriptionError">Description cannot be empty</div>
            <div class="success-message" id="descriptionSuccess">✓ Description is valid</div>
        </div>

        <div class="mb-3">
            <label class="form-label">Expense Date:</label>
            <input type="date" name="expense_date" id="expense_date" class="form-control"
                   value="<?= date('Y-m-d'); ?>" required />
            <div class="error-message" id="dateError">Please select a date</div>
        </div>

        <div class="mb-3">
            <label class="form-label">Paid By:</label>
            <select name="paid_by" id="paid_by" class="form-select" required>
                <option value="">-- Select Member --</option>
                <?php foreach ($group_members as $m): ?>
                    <option value="<?= $m['id']; ?>">
                        <?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="error-message" id="paidByError">Please select who paid</div>
        </div>

        <!-- Split Information Display -->
        <div class="split-info" id="splitInfo">
            <h6>Split Calculation:</h6>
            <p><strong>Total Amount:</strong> $<span id="totalAmount">0.00</span></p>
            <p><strong>Number of Members:</strong> <span id="memberCount"><?= $member_count; ?></span></p>
            <p><strong>Per Person Share:</strong> $<span id="perPersonAmount">0.00</span></p>
        </div>

        <div class="btn-row">
            <button type="submit" class="btn-main btn-primary" id="submitBtn">Add Expense</button>
            <a href="expenses.php?group_id=<?= $group_id; ?>" class="btn-main btn-secondary">Back to Expenses</a>
        </div>

    </form>
</div>

<script>
$(document).ready(function() {

    // Real-time Amount Validation
    $('#amount').on('keyup change', function() {
        let amount = parseFloat($(this).val());
        
        if (amount > 0) {
            $(this).removeClass('error').addClass('success');
            $('#amountError').hide();
            $('#amountSuccess').show();
            
            // Update split calculation
            updateSplitCalculation();
        } else {
            $(this).removeClass('success').addClass('error');
            $('#amountError').show();
            $('#amountSuccess').hide();
            $('#splitInfo').removeClass('show');
        }
    });

    // Real-time Description Validation
    $('#description').on('keyup', function() {
        let description = $(this).val().trim();
        
        if (description.length > 0) {
            $(this).removeClass('error').addClass('success');
            $('#descriptionError').hide();
            $('#descriptionSuccess').show();
        } else {
            $(this).removeClass('success').addClass('error');
            $('#descriptionError').show();
            $('#descriptionSuccess').hide();
        }
    });

    // Paid By Validation
    $('#paid_by').on('change', function() {
        let selected = $(this).val();
        
        if (selected) {
            $(this).removeClass('error').addClass('success');
            $('#paidByError').hide();
        } else {
            $(this).removeClass('success').addClass('error');
            $('#paidByError').show();
        }
    });

    // Update split calculation function
    function updateSplitCalculation() {
        let amount = parseFloat($('#amount').val()) || 0;
        let memberCount = parseInt($('#memberCount').text());
        
        if (amount > 0 && memberCount > 0) {
            let perPerson = (amount / memberCount).toFixed(2);
            $('#totalAmount').text(amount.toFixed(2));
            $('#perPersonAmount').text(perPerson);
            $('#splitInfo').addClass('show');
        }
    }

    // Form Submission Validation
    $('#expenseForm').submit(function(e) {
        let isValid = true;
        
        // Validate Amount
        let amount = parseFloat($('#amount').val());
        if (amount <= 0) {
            $('#amount').addClass('error');
            $('#amountError').show();
            isValid = false;
        }
        
        // Validate Description
        let description = $('#description').val().trim();
        if (description.length === 0) {
            $('#description').addClass('error');
            $('#descriptionError').show();
            isValid = false;
        }
        
        // Validate Expense Date
        let date = $('#expense_date').val();
        if (!date) {
            $('#expense_date').addClass('error');
            $('#dateError').show();
            isValid = false;
        }
        
        // Validate Paid By
        let paidBy = $('#paid_by').val();
        if (!paidBy) {
            $('#paid_by').addClass('error');
            $('#paidByError').show();
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            alert('Please fill all fields correctly');
        }
    });

    // Clear error messages on focus
    $('#amount, #description, #expense_date, #paid_by').on('focus', function() {
        $(this).removeClass('error');
    });

});
</script>

</body>
</html>
