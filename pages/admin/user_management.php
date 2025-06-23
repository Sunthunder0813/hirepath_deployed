<?php
require_once '../../db_connection/connection.php';
$conn = OpenConnection();

// Filtering logic
$filter_status = '';
$where_status = '';
$statuses = ['' => 'All', 'processing' => 'Processing', 'active' => 'Active', 'blocked' => 'Blocked'];
if (isset($_GET['filter']) && in_array($_GET['filter'], array_keys($statuses))) {
    $filter_status = $_GET['filter'];
    if ($filter_status !== '') {
        $where_status = "AND status = '" . $conn->real_escape_string($filter_status) . "'";
    }
}

if (isset($_GET['action'], $_GET['user_id'])) {
    $user_id = intval($_GET['user_id']);
    if ($_GET['action'] === 'block') {
        $conn->query("UPDATE users SET status='blocked' WHERE user_id=$user_id");
    } elseif ($_GET['action'] === 'unblock') {
        $conn->query("UPDATE users SET status='active' WHERE user_id=$user_id");
    }
    // Redirect to avoid resubmission
    echo "<script>window.location.href='admin_dashboard.php?tab=users';</script>";
    exit();
}

// Fetch company_name as well, allow filtering by status
$query = "SELECT user_id, username, email, status, company_name FROM users WHERE user_type != 'admin' $where_status ORDER BY username ASC";
$result = $conn->query($query);
?>
<style>
.user-mgmt-toolbar {
    margin-bottom: 18px;
    display: flex;
    align-items: center;
    gap: 18px;
    flex-wrap: wrap;
}
.user-mgmt-toolbar label {
    font-weight: 600;
    color: #22223b;
    font-size: 1.08em;
    margin-right: 10px;
}
.user-mgmt-toolbar select {
    padding: 7px 16px;
    border-radius: 6px;
    border: 1.5px solid #e3eafc;
    background: #f4f7f9;
    color: #22223b;
    font-size: 1.05em;
    font-weight: 500;
    outline: none;
    transition: border 0.18s, background 0.18s;
}
.user-mgmt-toolbar select:focus {
    border: 1.5px solid #c9ada7;
    background: #fff;
}
.user-mgmt-table-wrapper {
    max-height: 585px;
    overflow-y: auto;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(34,34,59,0.06);
    background: #fff;
}
.user-mgmt-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0 8px;
    background: transparent;
}
.user-mgmt-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #f4f7f9;
}
.user-mgmt-table th, .user-mgmt-table td {
    padding: 12px 10px;
    text-align: left;
}
.user-mgmt-table th {
    background: #f4f7f9;
    color: #22223b;
    font-size: 1.07em;
    font-weight: 600;
    border-bottom: 2px solid #e3eafc;
}
.user-mgmt-table tr {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(34,34,59,0.06);
    transition: box-shadow 0.18s;
}
.user-mgmt-table tr:hover {
    box-shadow: 0 4px 18px rgba(34,34,59,0.10);
}
.user-mgmt-table td {
    font-size: 1.03em;
    color: #22223b;
    vertical-align: middle;
}
.user-mgmt-table .btn {
    text-decoration: none;
    padding: 6px 16px;
    border-radius: 6px;
    font-size: 0.98em;
    font-weight: 500;
    border: none;
    outline: none;
    cursor: pointer;
    transition: background 0.18s, color 0.18s;
    margin-right: 2px;
}
.user-mgmt-table .btn-approve {
    background: #28a745;
    color: #fff;
}
.user-mgmt-table .btn-approve:hover {
    background: #218838;
}
.user-mgmt-table .btn-reject {
    background: #dc3545;
    color: #fff;
}
.user-mgmt-table .btn-reject:hover {
    background: #c82333;
}
.user-mgmt-table .btn-company {
    background: #f2e9e4;
    color: #22223b;
    border: 1px solid #c9ada7;
    padding: 4px 10px;
    font-size: 0.97em;
    border-radius: 5px;
    transition: background 0.18s, color 0.18s;
}
.user-mgmt-table .btn-company:hover {
    background: #c9ada7;
    color: #fff;
}
.user-mgmt-table .status-blocked {
    color: #dc3545;
    font-weight: bold;
}
.user-mgmt-table .status-processing {
    color: #ffc107;
    font-weight: bold;
}
.user-mgmt-table .status-active {
    color: #28a745;
    font-weight: bold;
}
.user-mgmt-table .btn[disabled] {
    background: #bdbdbd !important;
    color: #fff !important;
    cursor: not-allowed;
    border: none;
}
@media (max-width: 900px) {
    .user-mgmt-table-wrapper {
        max-height: 520px; /* increased from 340px */
    }
    .user-mgmt-table th, .user-mgmt-table td {
        padding: 8px 4px;
        font-size: 0.97em;
    }
    .user-mgmt-toolbar {
        flex-direction: column;
        align-items: flex-start;
        gap: 6px;
    }
}
</style>
<h1>User Management</h1>
<div class="user-mgmt-toolbar" style="justify-content: flex-end;">
    <form method="get" action="admin_dashboard.php" style="display:flex;align-items:center;gap:10px;">
        <input type="hidden" name="tab" value="users">
        <label for="statusFilter">Filter by Status:</label>
        <select name="filter" id="statusFilter" onchange="this.form.submit()">
            <?php foreach ($statuses as $key => $label): ?>
                <option value="<?php echo $key; ?>" <?php if ($filter_status === $key) echo 'selected'; ?>>
                    <?php echo $label; ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
</div>
<div class="user-mgmt-table-wrapper">
<table class="user-mgmt-table">
    <thead>
        <tr>
            <th>Username</th>
            <th>Email</th>
            <th>Company</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($user = $result->fetch_assoc()): ?>
        <tr>
            <td><?php echo htmlspecialchars($user['username']); ?></td>
            <td><?php echo htmlspecialchars($user['email']); ?></td>
            <td>
                <?php if (!empty($user['company_name'])): ?>
                    <a href="view_job_details_admin.php?company_id=<?php echo $user['user_id']; ?>" class="btn btn-company">
                        <?php echo htmlspecialchars($user['company_name']); ?>
                    </a>
                <?php else: ?>
                    <span style="color:#888;font-style:italic;">No company</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($user['status'] === 'blocked'): ?>
                    <span class="status-blocked">Blocked</span>
                <?php elseif ($user['status'] === 'processing'): ?>
                    <span class="status-processing">Processing</span>
                <?php else: ?>
                    <span class="status-active">Active</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($user['status'] === 'blocked'): ?>
                    <a href="admin_dashboard.php?tab=users&action=unblock&user_id=<?php echo $user['user_id']; ?>" class="btn btn-approve">Unblock</a>
                <?php elseif ($user['status'] === 'processing'): ?>
                    <button class="btn btn-reject" disabled>Block</button>
                <?php else: ?>
                    <a href="admin_dashboard.php?tab=users&action=block&user_id=<?php echo $user['user_id']; ?>" class="btn btn-reject">Block</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
</div>
