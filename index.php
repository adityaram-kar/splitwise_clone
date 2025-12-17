<?php
require_once 'functions.php';
redirectIfNotLoggedIn();

$user_id = $_SESSION['user_id'];

// Fetch user profile
$stmt = $pdo->prepare("SELECT first_name, last_name, email, profile_pic FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch groups user belongs to
$stmt = $pdo->prepare("SELECT g.id, g.group_name FROM groups g 
JOIN group_members gm ON g.id = gm.group_id WHERE gm.user_id = ?");
$stmt->execute([$user_id]);
$user_groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all groups not joined
$stmt2 = $pdo->prepare("SELECT id, group_name FROM groups 
WHERE id NOT IN (SELECT group_id FROM group_members WHERE user_id = ?)");
$stmt2->execute([$user_id]);
$available_groups = $stmt2->fetchAll(PDO::FETCH_ASSOC);

// Calculate balances
$balances = [];
$totals = [];
$total_owed = 0;
$total_gets_back = 0;

foreach ($user_groups as $group) {
    $group_id = $group['id'];
    $stmt = $pdo->prepare("SELECT u.id, u.first_name, u.last_name FROM users u 
    JOIN group_members gm ON u.id = gm.user_id WHERE gm.group_id = ?");
    $stmt->execute([$group_id]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM expenses WHERE group_id = ?");
    $stmt->execute([$group_id]);
    $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $expense_ids = array_column($expenses, 'id');
    $shares = [];
    if (count($expense_ids) > 0) {
        $placeholders = implode(',', array_fill(0, count($expense_ids), '?'));
        $stmt = $pdo->prepare("SELECT * FROM expense_shares WHERE expense_id IN ($placeholders)");
        $stmt->execute($expense_ids);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $share) {
            $shares[$share['expense_id']][] = $share;
        }
    }

    $group_total = 0;
    foreach ($members as $member) {
        if ($member['id'] == $user_id) continue;
        $balance = 0;
        foreach ($expenses as $expense) {
            $eid = $expense['id'];
            if ($expense['paid_by'] == $user_id && isset($shares[$eid])) {
                foreach ($shares[$eid] as $share) {
                    if ($share['user_id'] == $member['id']) $balance += $share['share_amount'];
                }
            }
            if ($expense['paid_by'] == $member['id'] && isset($shares[$eid])) {
                foreach ($shares[$eid] as $share) {
                    if ($share['user_id'] == $user_id) $balance -= $share['share_amount'];
                }
            }
        }
        $group_total += $balance;
        $balances[$group_id][$member['id']] = [
            'name' => $member['first_name'] . ' ' . $member['last_name'],
            'balance' => $balance,
            'group_name' => $group['group_name'],
        ];
    }
    $totals[$group_id] = $group_total;
    $total_owed += max(0, -$group_total);
    $total_gets_back += max(0, $group_total);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Dashboard - Splitwise Clone</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"/>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
<link href="cssFiles/dashboard.css" rel="stylesheet"/>
</head>
<body>
  <div class="page-wrapper">
    <div class="topbar">
      <div>
        <h2 class="welcome-title">Welcome, <?= htmlspecialchars($user['first_name']) ?> 👋</h2>
      </div>
      <div class="actions">
        <button id="openProfile" class="profile-btn" title="Profile"><i class="fa fa-user"></i></button>
        <a href="logout.php" class="logout-btn">Logout</a>
      </div>
    </div>

    <div class="dashboard-container">
      <div class="left-panel">
        <div class="summary-row">
          <div class="summary-card">
            <div style="font-weight:700">Total Groups</div>
            <div style="font-size:18px; margin-top:6px; font-weight:800;"><?= count($user_groups); ?></div>
          </div>
          <div class="summary-card owes">
            <div style="font-weight:700">Owes</div>
            <div style="font-size:18px; margin-top:6px; font-weight:800;">$<?= number_format($total_owed,2); ?></div>
          </div>
          <div class="summary-card gets">
            <div style="font-weight:700">Gets Back</div>
            <div style="font-size:18px; margin-top:6px; font-weight:800;">$<?= number_format($total_gets_back,2); ?></div>
          </div>
        </div>

        <div class="card">
          <div class="section-title">Your Groups</div>
          <?php if ($user_groups): ?>
            <?php foreach ($user_groups as $group):
              $group_total = $totals[$group['id']] ?? 0;
              if ($group_total > 0) {
                $label = "<span style='color:#16a34a;font-weight:700;'>Gets Back: $" . number_format($group_total,2) . "</span>";
              } elseif ($group_total < 0) {
                $label = "<span style='color:#dc2626;font-weight:700;'>Owes: $" . number_format(abs($group_total),2) . "</span>";
              } else {
                $label = "<span style='color:#6b7280;font-weight:700;'>Settled: $0.00</span>";
              }
            ?>
              <a class="group-link" href="expenses.php?group_id=<?= $group['id']; ?>">
                <div><i class="fa fa-users"></i>&nbsp; <?= htmlspecialchars($group['group_name']); ?></div>
                <div class="meta"><?= $label; ?></div>
              </a>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="text-muted">No groups yet. Join or create one.</div>
          <?php endif; ?>
        </div>

        <div class="card">
          <div class="section-title">Join a Group</div>
          <?php if ($available_groups): ?>
            <form method="POST" action="groups.php">
              <select name="join_group_id" class="form-select mb-2" required>
                <option value="" disabled selected>Select a group</option>
                <?php foreach ($available_groups as $grp): ?>
                  <option value="<?= $grp['id']; ?>"><?= htmlspecialchars($grp['group_name']); ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit" name="action" value="join" class="btn-gradient">Join Group</button>
            </form>
          <?php else: ?>
            <div class="text-muted">No available groups to join</div>
          <?php endif; ?>
        </div>

        <div class="card">
          <div class="section-title">Create New Group</div>
          <form method="POST" action="groups.php">
            <input type="text" name="new_group_name" class="form-control mb-2" placeholder="Group name" required>
            <button type="submit" name="action" value="create" class="btn-gradient">Create Group</button>
          </form>
        </div>
      </div>

      <div class="right-panel">
        <div class="right-card">
          <div class="balance-title">Balances</div>

          <?php if ($balances): ?>
            <?php foreach ($balances as $group_id => $membersBalances): ?>
              <h5 class="mt-3 mb-1"><?= htmlspecialchars($membersBalances[array_key_first($membersBalances)]['group_name']); ?></h5>
              <p><strong>Your total:</strong> $<?= number_format($totals[$group_id], 2); ?>
                <?= $totals[$group_id] > 0 ? '(Gets back)' : ($totals[$group_id] < 0 ? '(Owes)' : '(Settled)') ?>
              </p>

              <div class="table-responsive">
              <table class="table mb-3">
                <thead>
                  <tr>
                    <th>Member</th>
                    <th>Status</th>
                    <th>Amount</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($membersBalances as $info):
                    $b = $info['balance'];
                    $cls = $b>0 ? 'text-success' : ($b<0 ? 'text-danger' : 'text-muted'); ?>
                    <tr>
                      <td><?= htmlspecialchars($info['name']); ?></td>
                      <td class="<?= $cls; ?>"><?= $b>0 ? 'Gets back' : ($b<0 ? 'Owes' : 'Settled'); ?></td>
                      <td>$<?= number_format(abs($b),2); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div>No balances yet.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div id="profileModal" class="profile-overlay">
    <div class="profile-card">
      <img src="<?= htmlspecialchars($user['profile_pic'] ?? 'default-avatar.png'); ?>" alt="avatar">
      <h4><?= htmlspecialchars($user['first_name'].' '.$user['last_name']); ?></h4>
      <p><?= htmlspecialchars($user['email']); ?></p>
      <button class="btn-gradient w-100 mb-2" onclick="window.location.href='edit_profile.php'">Edit Profile</button>
      <button class="btn btn-secondary w-100" onclick="closeProfile()">Close</button>
    </div>
  </div>

<script>
  const profileModal = document.getElementById('profileModal');
  document.getElementById('openProfile').onclick = () => { profileModal.style.display = 'flex'; };
  function closeProfile(){ profileModal.style.display='none'; }
  profileModal.onclick = (e) => { if (e.target === profileModal) closeProfile(); };
  document.addEventListener('keydown', (e)=> { if (e.key==='Escape') closeProfile(); });
</script>
</body>
</html>
