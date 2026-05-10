<?php
require_once 'db.php';
global $conn;
requireLogin();

$user   = currentUser();
$userId = $user['id'];

// ── ADD ──────────────────────────────────────
if (isset($_POST['add'])) {
    $desc   = trim($_POST['description']);
    $amount = floatval($_POST['amount']);
    if ($desc && $amount > 0) {
        $stmt = $conn->prepare("INSERT INTO expenses (user_id, description, amount) VALUES (?, ?, ?)");
        $stmt->bind_param('isd', $userId, $desc, $amount);
        $stmt->execute();
        $stmt->close();
    }
    redirect(strtok($_SERVER['REQUEST_URI'], '?'));
}

// ── DELETE ───────────────────────────────────
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM expenses WHERE id=? AND user_id=?");
    $stmt->bind_param('ii', $id, $userId);
    $stmt->execute();
    $stmt->close();
    redirect(strtok($_SERVER['REQUEST_URI'], '?'));
}

// ── UPDATE ───────────────────────────────────
if (isset($_POST['update'])) {
    $id     = intval($_POST['id']);
    $desc   = trim($_POST['description']);
    $amount = floatval($_POST['amount']);
    if ($desc && $amount > 0) {
        $stmt = $conn->prepare("UPDATE expenses SET description=?, amount=? WHERE id=? AND user_id=?");
        $stmt->bind_param('sdii', $desc, $amount, $id, $userId);
        $stmt->execute();
        $stmt->close();
    }
    redirect(strtok($_SERVER['REQUEST_URI'], '?'));
}

// ── FETCH EDIT ROW ────────────────────────────
$editData = null;
if (isset($_GET['edit'])) {
    $id   = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM expenses WHERE id=? AND user_id=?");
    $stmt->bind_param('ii', $id, $userId);
    $stmt->execute();
    $editData = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ── FETCH ALL (this user) ─────────────────────
$stmt = $conn->prepare("SELECT * FROM expenses WHERE user_id=? ORDER BY id DESC");
$stmt->bind_param('i', $userId);
$stmt->execute();
$res      = $stmt->get_result();
$expenses = [];
$total    = 0;
while ($row = $res->fetch_assoc()) {
    $expenses[] = $row;
    $total += $row['amount'];
}
$stmt->close();
$count = count($expenses);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Expense Ledger</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        /* ── Tokens ── */
        :root {
            --ink:           #0f0e0c;
            --ink-2:         #5a564f;
            --ink-3:         #9c9890;
            --paper:         #faf8f4;
            --surface:       #ffffff;
            --border:        #e8e4dc;
            --accent:        #c8873a;
            --accent-light:  #fdf1e4;
            --accent-border: #f0d8b8;
            --danger:        #d94f3d;
            --danger-light:  #fdf1f0;
            --success:       #3a9c6e;
            --blue:          #4f7fe8;
            --blue-light:    #edf2fd;
            --radius:        10px;
            --shadow:        0 2px 12px rgba(15,14,12,.07);
            --shadow-lg:     0 8px 32px rgba(15,14,12,.1);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--paper);
            color: var(--ink);
            min-height: 100vh;
            padding: 0 0 4rem;
        }

        /* ── Top bar ── */
        .topbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: .85rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            position: sticky;
            top: 0;
            z-index: 10;
            box-shadow: var(--shadow);
        }
        .topbar-brand {
            font-family: 'Instrument Serif', serif;
            font-size: 1.35rem;
            font-weight: 400;
            letter-spacing: -.02em;
            text-decoration: none;
            color: var(--ink);
        }
        .topbar-brand em { font-style: italic; color: var(--accent); }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: .75rem;
        }
        .topbar-user {
            font-size: .82rem;
            color: var(--ink-3);
            font-weight: 500;
        }
        .topbar-user span {
            color: var(--ink-2);
            font-weight: 600;
        }
        .btn-logout {
            font-family: 'DM Sans', sans-serif;
            font-size: .78rem;
            font-weight: 600;
            background: var(--paper);
            color: var(--ink-2);
            border: 1.5px solid var(--border);
            border-radius: 8px;
            padding: .38rem .85rem;
            cursor: pointer;
            text-decoration: none;
            transition: all .15s;
        }
        .btn-logout:hover {
            background: var(--danger-light);
            color: var(--danger);
            border-color: #f5ccc9;
        }

        /* ── Page ── */
        .page {
            max-width: 720px;
            margin: 0 auto;
            padding: 2.5rem 1rem 0;
        }

        /* ── Header ── */
        .header {
            margin-bottom: 2.5rem;
            border-bottom: 1px solid var(--border);
            padding-bottom: 1.5rem;
        }
        .header-top {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .header h1 {
            font-family: 'Instrument Serif', serif;
            font-size: clamp(2rem, 5vw, 2.8rem);
            font-weight: 400;
            line-height: 1.1;
            letter-spacing: -.02em;
        }
        .header h1 em { font-style: italic; color: var(--accent); }
        .header-meta {
            font-size: .8rem;
            color: var(--ink-3);
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .stats {
            display: flex;
            gap: .75rem;
            margin-top: 1.25rem;
            flex-wrap: wrap;
        }
        .stat {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 100px;
            padding: .45rem 1.1rem;
            font-size: .82rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: .5rem;
            box-shadow: var(--shadow);
        }
        .stat-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--accent); flex-shrink: 0; }
        .stat-dot.green { background: var(--success); }

        /* ── Form card ── */
        .form-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.75rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            animation: fadeUp .4s ease both;
        }
        .form-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent), #e8a95a);
        }

        .form-label {
            font-size: .72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--ink-2);
            display: block;
            margin-bottom: .45rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 180px auto;
            gap: .75rem;
            align-items: end;
        }
        @media (max-width: 540px) { .form-row { grid-template-columns: 1fr; } }

        .form-group { display: flex; flex-direction: column; }

        input[type="text"], input[type="number"] {
            font-family: 'DM Sans', sans-serif;
            font-size: .925rem;
            color: var(--ink);
            background: var(--paper);
            border: 1.5px solid var(--border);
            border-radius: var(--radius);
            padding: .65rem .9rem;
            outline: none;
            transition: border-color .2s, box-shadow .2s, background .2s;
            width: 100%;
        }
        input[type="text"]:focus, input[type="number"]:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(200,135,58,.14);
            background: #fff;
        }
        .edit-mode input:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(79,127,232,.14);
        }

        .amount-wrap { position: relative; }
        .amount-wrap::before {
            content: '$';
            position: absolute;
            left: .9rem; top: 50%;
            transform: translateY(-50%);
            color: var(--ink-3);
            font-size: .9rem;
            pointer-events: none;
        }
        .amount-wrap input { padding-left: 1.7rem; }

        /* ── Buttons ── */
        .btn {
            font-family: 'DM Sans', sans-serif;
            font-size: .875rem;
            font-weight: 600;
            border: none;
            border-radius: var(--radius);
            padding: .68rem 1.3rem;
            cursor: pointer;
            transition: all .18s;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            text-decoration: none;
        }
        .btn-primary { background: var(--ink); color: #fff; }
        .btn-primary:hover {
            background: #2a2720;
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(15,14,12,.2);
        }
        .btn-update { background: var(--blue); color: #fff; }
        .btn-update:hover {
            background: #3a6dd4;
            transform: translateY(-1px);
            box-shadow: 0 4px 14px rgba(79,127,232,.3);
        }
        .btn-cancel {
            background: var(--paper);
            color: var(--ink-2);
            border: 1.5px solid var(--border);
            font-size: .8rem;
            padding: .5rem .9rem;
        }
        .btn-cancel:hover { background: var(--border); }

        .edit-hint {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.25rem;
            flex-wrap: wrap;
        }
        .edit-badge {
            background: var(--blue-light);
            color: var(--blue);
            font-size: .75rem;
            font-weight: 600;
            padding: .3rem .8rem;
            border-radius: 6px;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        /* ── Table ── */
        .table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        .table-title {
            font-family: 'Instrument Serif', serif;
            font-size: 1.4rem;
            font-weight: 400;
        }
        .table-count {
            font-size: .78rem;
            color: var(--ink-3);
            letter-spacing: .05em;
            text-transform: uppercase;
            font-weight: 500;
        }

        .expense-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: var(--shadow);
        }
        .expense-table thead tr { background: #f5f3ef; }
        .expense-table th {
            padding: .85rem 1.1rem;
            font-size: .72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: var(--ink-3);
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        .expense-table th:last-child { text-align: right; }
        .expense-table tbody tr { transition: background .15s; }
        .expense-table tbody tr:not(:last-child) td { border-bottom: 1px solid var(--border); }
        .expense-table tbody tr:hover { background: #fdfcf9; }
        .expense-table td {
            padding: .95rem 1.1rem;
            font-size: .9rem;
            vertical-align: middle;
        }

        .td-id { color: var(--ink-3); font-size: .78rem; font-weight: 600; width: 48px; }
        .td-desc { font-weight: 500; }
        .td-amount { font-weight: 600; color: var(--success); white-space: nowrap; }
        .td-actions { text-align: right; white-space: nowrap; }

        .btn-edit, .btn-delete {
            font-family: 'DM Sans', sans-serif;
            font-size: .78rem;
            font-weight: 600;
            padding: .35rem .75rem;
            border-radius: 7px;
            border: 1.5px solid transparent;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            transition: all .15s;
        }
        .btn-edit {
            background: var(--accent-light);
            color: var(--accent);
            border-color: var(--accent-border);
        }
        .btn-edit:hover { background: var(--accent); color: #fff; border-color: var(--accent); }
        .btn-delete {
            background: var(--danger-light);
            color: var(--danger);
            border-color: #f5ccc9;
            margin-left: .4rem;
        }
        .btn-delete:hover { background: var(--danger); color: #fff; border-color: var(--danger); }

        .total-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .9rem 1.1rem;
            margin-top: .5rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-size: .88rem;
            font-weight: 500;
        }
        .total-label { color: var(--ink-2); }
        .total-value {
            font-family: 'Instrument Serif', serif;
            font-size: 1.35rem;
            color: var(--ink);
        }

        .empty {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--ink-3);
        }
        .empty-icon { font-size: 2.5rem; margin-bottom: .75rem; }
        .empty p { font-size: .88rem; }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .form-card, .expense-table, .total-row { animation: fadeUp .4s ease both; }
    </style>
</head>
<body>

<!-- ══ Top bar ══ -->
<nav class="topbar">
    <a href="index.php" class="topbar-brand">Expense <em>Ledger</em></a>
    <div class="topbar-right">
        <span class="topbar-user">👤 <span><?= htmlspecialchars($user['name']) ?></span></span>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<!-- ══ Content ══ -->
<div class="page">

    <!-- Header -->
    <header class="header">
        <div class="header-top">
            <h1>Expense <em>Ledger</em></h1>
            <span class="header-meta">Personal Finance</span>
        </div>
        <div class="stats">
            <div class="stat">
                <span class="stat-dot green"></span>
                <?= $count ?> <?= $count === 1 ? 'record' : 'records' ?>
            </div>
            <div class="stat">
                <span class="stat-dot"></span>
                Total: $<?= number_format($total, 2) ?>
            </div>
        </div>
    </header>

    <!-- Form -->
    <div class="form-card <?= $editData ? 'edit-mode' : '' ?>">
        <?php if ($editData): ?>
            <div class="edit-hint">
                <span class="edit-badge">✏️ Editing record #<?= $editData['id'] ?></span>
                <a href="?" class="btn btn-cancel">✕ Cancel</a>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="id" value="<?= $editData['id'] ?? '' ?>">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <label>
                        <input type="text" name="description"
                               placeholder="e.g. Coffee, Groceries…" required
                               value="<?= htmlspecialchars($editData['description'] ?? '') ?>">
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-label">Amount</label>
                    <div class="amount-wrap">
                        <label>
                            <input type="number" name="amount" step="0.01" min="0.01"
                                   placeholder="0.00" required
                                   value="<?= htmlspecialchars($editData['amount'] ?? '') ?>">
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">&nbsp;</label>
                    <?php if ($editData): ?>
                        <button type="submit" name="update" class="btn btn-update">💾 Update</button>
                    <?php else: ?>
                        <button type="submit" name="add" class="btn btn-primary">+ Add</button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="table-section">
        <div class="table-header">
            <h2 class="table-title">All Expenses</h2>
            <span class="table-count"><?= $count ?> entries</span>
        </div>

        <?php if (empty($expenses)): ?>
            <div class="empty">
                <div class="empty-icon">🧾</div>
                <p>No expenses yet. Add your first one above.</p>
            </div>
        <?php else: ?>
            <table class="expense-table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($expenses as $row): ?>
                    <tr>
                        <td class="td-id"><?= $row['id'] ?></td>
                        <td class="td-desc"><?= htmlspecialchars($row['description']) ?></td>
                        <td class="td-amount">$<?= number_format($row['amount'], 2) ?></td>
                        <td class="td-actions">
                            <a href="?edit=<?= $row['id'] ?>" class="btn-edit">✏️ Edit</a>
                            <a href="?delete=<?= $row['id'] ?>"
                               class="btn-delete"
                               onclick="return confirm('Remove this expense?')">🗑 Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <div class="total-row">
                <span class="total-label">Total spent</span>
                <span class="total-value">$<?= number_format($total, 2) ?></span>
            </div>
        <?php endif; ?>
    </div>

</div><!-- /page -->
</body>
</html>