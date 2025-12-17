<?php
require_once 'functions.php';
redirectIfNotLoggedIn();

// HANDLE SETTLE-UP POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'settle') {
    $user_id = $_SESSION['user_id'];
    $group_id = intval($_POST['group_id'] ?? 0);
    $settle_with_id = intval($_POST['settle_with_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $payer = $_POST['payer'] ?? '';

    if ($group_id <= 0 || $settle_with_id <= 0 || $amount <= 0 || !in_array($payer, ['me','them'], true)) {
        header("Location: expenses.php?group_id=" . $group_id);
        exit;
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM group_members WHERE group_id = ? AND user_id IN (?, ?)");
    $stmt->execute([$group_id, $user_id, $settle_with_id]);
    if ($stmt->fetchColumn() != 2) {
        header("Location: expenses.php?group_id=" . $group_id);
        exit;
    }

    $description = "Settle up payment";

    if ($payer === 'me') {
        $paid_by = $user_id;
        $share_user = $settle_with_id;
    } else {
        $paid_by = $settle_with_id;
        $share_user = $user_id;
    }

    $stmt = $pdo->prepare("INSERT INTO expenses (group_id, amount, paid_by, description, expense_date) 
                           VALUES (?, ?, ?, ?, CURDATE())");
    $stmt->execute([$group_id, $amount, $paid_by, $description]);
    $expense_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO expense_shares (expense_id, user_id, share_amount) VALUES (?, ?, ?)");
    $stmt->execute([$expense_id, $share_user, $amount]);

    header("Location: expenses.php?group_id=" . $group_id);
    exit;
}

// NORMAL PAGE FLOW
if (!isset($_GET['group_id'])) {
    header('Location: index.php');
    exit;
}

$group_id = intval($_GET['group_id']);
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->execute([$group_id, $user_id]);
if ($stmt->rowCount() === 0) {
    die("Access denied.");
}

$stmt = $pdo->prepare("SELECT group_name FROM groups WHERE id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch();

if (!$group) {
    die("Group not found.");
}

$stmt = $pdo->prepare("
    SELECT e.id as expense_id, e.description, e.amount, e.paid_by, e.expense_date, u.first_name, u.last_name
    FROM expenses e
    JOIN users u ON e.paid_by = u.id
    WHERE e.group_id = ?
    ORDER BY e.expense_date DESC
");
$stmt->execute([$group_id]);
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$shares = [];
if ($expenses) {
    $expense_ids = array_column($expenses, 'expense_id');
    $placeholders = implode(',', array_fill(0, count($expense_ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM expense_shares WHERE expense_id IN ($placeholders)");
    $stmt->execute($expense_ids);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $share) {
        $shares[$share['expense_id']][] = $share;
    }
}

$stmt = $pdo->prepare("SELECT u.id, u.first_name, u.last_name 
                       FROM users u 
                       JOIN group_members gm ON u.id = gm.user_id 
                       WHERE gm.group_id = ?");
$stmt->execute([$group_id]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

$balances = [];
foreach ($members as $m) {
    $balances[$m['id']] = 0;
}
foreach ($expenses as $exp) {
    $expense_id = $exp['expense_id'];
    $paid_by_id = $exp['paid_by'];
    $total_amount = $exp['amount'];
    if (isset($shares[$expense_id])) {
        foreach ($shares[$expense_id] as $share) {
            $balances[$share['user_id']] -= $share['share_amount'];
        }
    }
    $balances[$paid_by_id] += $total_amount;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Group Expenses - <?= htmlspecialchars($group['group_name']) ?></title>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <link rel="stylesheet" href="cssFiles/expenses.css" />

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>
        .search-box {
            margin-bottom: 20px;
        }

        .filter-controls {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .no-results {
            text-align: center;
            padding: 20px;
            color: #999;
            display: none;
        }

        .expense-row-highlight {
            animation: highlight 0.5s;
        }

        @keyframes highlight {
            0% { background-color: #fff3cd; }
            100% { background-color: transparent; }
        }

        .success-message {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #28a745;
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            z-index: 9999;
            animation: slideIn 0.3s;
        }

        @keyframes slideIn {
            from { right: -300px; }
            to { right: 20px; }
        }

        .expense-table tbody tr {
            transition: all 0.3s ease;
        }

        .expense-table tbody tr:hover {
            background-color: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="expense-wrapper">
        <div class="page-title">
            <i class="fa fa-users"></i>
            Expenses for: <span style="color:#36e0c2"><?= htmlspecialchars($group['group_name']); ?></span>
        </div>
        <div class="btn-row">
            <a href="index.php" class="my-btn-secondary"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
            <a href="add_expense.php?group_id=<?= $group_id; ?>" class="my-btn-primary"><i class="fa fa-plus"></i> Add Expense</a>
            <a href="delete_group.php?group_id=<?= $group_id; ?>"
               class="btn btn-danger"
               onclick="return confirm('Are you sure you want to delete this group and ALL its expenses?');">
                <i class="fa fa-trash"></i> Delete Group
            </a>
        </div>

        <!-- Search Box -->
        <div class="search-box">
            <input type="text" id="searchBox" class="form-control" placeholder="🔍 Search expenses by description...">
        </div>

        <!-- Filter Controls -->
        <div class="filter-controls">
            <button class="btn btn-outline-secondary btn-sm" id="filterAll">All Expenses</button>
            <button class="btn btn-outline-secondary btn-sm" id="filterToday">Today</button>
            <button class="btn btn-outline-secondary btn-sm" id="filterThisWeek">This Week</button>
            <button class="btn btn-outline-secondary btn-sm" id="filterThisMonth">This Month</button>
            <button class="btn btn-outline-secondary btn-sm" id="sortByAmount">Sort by Amount</button>
        </div>

        <div class="section-label" style="font-size:1.18rem;margin-bottom:12px;color:#165248;">
            <i class="fa fa-piggy-bank me-2"></i>Current Balances
        </div>

        <div class="balances-row">
            <?php foreach ($members as $m): 
                $amt = round($balances[$m['id']],2);
                $badge = $amt > 0 ? 'success' : ($amt < 0 ? 'danger' : 'neutral');
                $badgeText = $amt > 0 ? 'Gets Back' : ($amt < 0 ? 'Owes' : 'Settled');
            ?>
                <div class="balance-card">
                    <div class="balance-title">
                        <i class="fa fa-user-circle me-1"></i>
                        <?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name']) ?>
                    </div>
                    <div class="balance-amt">$<?= number_format($amt,2) ?></div>
                    <span class="badge badge-<?= $badge ?>" style="font-size:0.97rem;"><?= $badgeText ?></span>

                    <?php if ($m['id'] != $user_id): ?>
                        <button 
                            class="btn btn-sm btn-outline-dark mt-2"
                            onclick="openSettleModal(<?= $group_id; ?>, <?= $m['id']; ?>, '<?= htmlspecialchars($m['first_name'].' '.$m['last_name']); ?>', '<?= number_format(abs($amt),2); ?>')">
                            Settle
                        </button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="section-label" style="font-size:1.18rem;">
            <i class="fa fa-history me-2"></i>Expense History
        </div>

        <?php if (count($expenses) > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered expense-table" id="expenseTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Payer</th>
                        <th>Shares</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="expenseBody">
                <?php foreach ($expenses as $exp): ?>
                    <tr data-expense-id="<?= $exp['expense_id']; ?>" data-date="<?= $exp['expense_date']; ?>" data-amount="<?= $exp['amount']; ?>" data-description="<?= strtolower($exp['description']); ?>">
                        <td><?= htmlspecialchars($exp['expense_date']); ?><span class="expense-id">#<?= $exp['expense_id']; ?></span></td>
                        <td><?= htmlspecialchars($exp['description']); ?></td>
                        <td><span style="font-weight:700;">$<?= number_format($exp['amount'], 2); ?></span></td>
                        <td><span class="share-member"><i class="fa fa-user"></i> <?= htmlspecialchars($exp['first_name'] . ' ' . $exp['last_name']); ?></span></td>
                        <td>
                            <ul style="list-style:none;margin:0;padding:0;">
                            <?php
                            if (isset($shares[$exp['expense_id']])) {
                                foreach ($shares[$exp['expense_id']] as $share) {
                                    $sn = '';
                                    foreach ($members as $m) {
                                        if ($m['id'] == $share['user_id']) {
                                            $sn = $m['first_name'] . ' ' . $m['last_name'];
                                            break;
                                        }
                                    }
                                    echo "<li><span class='share-member'>" . htmlspecialchars($sn) . "</span>: <span class='share-amt'>$" . number_format($share['share_amount'],2) . "</span></li>";
                                }
                            }
                            ?>
                            </ul>
                        </td>
                        <td class="action-btns">
                            <a href="edit_expense.php?id=<?= $exp['expense_id']; ?>&group_id=<?= $group_id; ?>" class="btn btn-sm btn-outline-primary"><i class="fa fa-edit"></i> Edit</a>
                            <button class="btn btn-sm btn-outline-danger delete-expense" data-id="<?= $exp['expense_id']; ?>" data-group="<?= $group_id; ?>"><i class="fa fa-trash"></i> Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="no-results" id="noResults">
            <p><i class="fa fa-search"></i> No expenses found.</p>
        </div>
        <?php else: ?>
            <p>No expenses recorded yet.</p>
        <?php endif; ?>
    </div>

    <!-- Success Message -->
    <div class="success-message" id="successMessage">
        <i class="fa fa-check"></i> Expense deleted successfully!
    </div>

    <!-- SETTLE MODAL -->
    <div id="settleBackdrop" class="settle-modal-backdrop">
        <div class="settle-modal">
            <h5 class="mb-3">Settle Up</h5>
            <form method="POST" action="" id="settleForm">
                <input type="hidden" name="action" value="settle">
                <input type="hidden" name="group_id" id="settle_group_id">
                <input type="hidden" name="settle_with_id" id="settle_with_id">

                <div class="mb-2">
                    <label class="form-label">With</label>
                    <input type="text" id="settle_with_name" class="form-control" readonly>
                </div>

                <div class="mb-2">
                    <label class="form-label">Who pays?</label><br>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="payer" id="payer_me" value="me" checked>
                        <label class="form-check-label" for="payer_me">I pay</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="payer" id="payer_them" value="them">
                        <label class="form-check-label" for="payer_them">They pay me</label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Amount</label>
                    <input type="number" step="0.01" min="0.01" name="amount" id="settle_amount" class="form-control" required>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-secondary" onclick="closeSettleModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Record Settlement</button>
                </div>
            </form>
        </div>
    </div>

<script>
$(document).ready(function() {

    // SEARCH FUNCTIONALITY
    $('#searchBox').on('keyup', function() {
        let searchValue = $(this).val().toLowerCase();
        let hasVisible = false;

        $('#expenseTable tbody tr').each(function() {
            let description = $(this).data('description');
            if (description.includes(searchValue) || searchValue === '') {
                $(this).show();
                hasVisible = true;
            } else {
                $(this).hide();
            }
        });

        $('#noResults').toggle(!hasVisible && searchValue !== '');
    });

    // FILTER BY DATE
    $('#filterToday').click(function() {
        filterByDate('today');
    });

    $('#filterThisWeek').click(function() {
        filterByDate('week');
    });

    $('#filterThisMonth').click(function() {
        filterByDate('month');
    });

    $('#filterAll').click(function() {
        $('#expenseTable tbody tr').show();
        $('#noResults').hide();
    });

    function filterByDate(type) {
        let today = new Date();
        let startDate = new Date();

        if (type === 'today') {
            startDate.setHours(0, 0, 0, 0);
        } else if (type === 'week') {
            startDate.setDate(today.getDate() - today.getDay());
        } else if (type === 'month') {
            startDate.setDate(1);
        }

        let hasVisible = false;
        $('#expenseTable tbody tr').each(function() {
            let rowDate = new Date($(this).data('date'));
            if (rowDate >= startDate) {
                $(this).show();
                hasVisible = true;
            } else {
                $(this).hide();
            }
        });

        $('#noResults').toggle(!hasVisible);
    }

    // SORT BY AMOUNT
    $('#sortByAmount').click(function() {
        let tbody = $('#expenseBody');
        let rows = tbody.find('tr').get();
        
        rows.sort(function(a, b) {
            let amountA = parseFloat($(a).data('amount'));
            let amountB = parseFloat($(b).data('amount'));
            return amountB - amountA; // Descending order
        });

        $.each(rows, function(index, row) {
            tbody.append(row);
        });

        $(this).toggleClass('btn-outline-secondary btn-secondary');
    });

    // DELETE EXPENSE WITH AJAX
    $('.delete-expense').on('click', function(e) {
        e.preventDefault();
        let expenseId = $(this).data('id');
        let groupId = $(this).data('group');
        let row = $(this).closest('tr');

        if (confirm('Are you sure you want to delete this expense?')) {
            $.ajax({
                url: 'delete_expense.php',
                type: 'GET',
                data: { id: expenseId, group_id: groupId },
                success: function() {
                    row.fadeOut('slow', function() {
                        $(this).remove();
                        $('#successMessage').fadeIn().delay(2000).fadeOut();
                        
                        // Check if any rows left
                        if ($('#expenseTable tbody tr').length === 0) {
                            $('#noResults').show().text('No expenses recorded yet.');
                        }
                    });
                },
                error: function() {
                    alert('Error deleting expense');
                }
            });
        }
    });

    // SETTLE MODAL CLOSE ON ESC
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            closeSettleModal();
        }
    });

    // SETTLE MODAL CLOSE ON BACKDROP CLICK
    $('#settleBackdrop').on('click', function(e) {
        if (e.target === this) {
            closeSettleModal();
        }
    });

    // SETTLE FORM SUBMIT
    $('#settleForm').on('submit', function() {
        let amount = parseFloat($('#settle_amount').val());
        if (amount <= 0) {
            alert('Amount must be greater than 0');
            return false;
        }
    });

});

// Modal Functions
function openSettleModal(groupId, otherId, otherName, suggestion) {
    document.getElementById('settle_group_id').value = groupId;
    document.getElementById('settle_with_id').value = otherId;
    document.getElementById('settle_with_name').value = otherName;
    document.getElementById('settle_amount').value = suggestion;
    document.getElementById('settleBackdrop').style.display = 'flex';
}

function closeSettleModal() {
    document.getElementById('settleBackdrop').style.display = 'none';
}
</script>
</body>
</html>
