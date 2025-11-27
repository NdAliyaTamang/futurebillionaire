<?php
// Stop if $users is not provided
if (!isset($users)) {
    exit;
}
?>

<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th style="width:5%;">ID</th>
            <th style="width:15%;">Username</th>
            <th style="width:25%;">Email</th>
            <th style="width:10%;">Role</th>
            <th style="width:10%;">Login Count</th>
            <th style="width:20%;">Last Login</th>
            <th style="width:5%;">Status</th>
            <th style="width:10%;">Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($users)): ?>
        <tr>
            <td colspan="8" class="text-center">No users found.</td>
        </tr>
    <?php else: ?>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u['UserID']) ?></td>
                <td><?= htmlspecialchars($u['Username']) ?></td>
                <td><?= htmlspecialchars($u['Email'] ?? '') ?></td>
                <td><?= htmlspecialchars($u['Role']) ?></td>
                <td><?= htmlspecialchars($u['LoginCount']) ?></td>
                <td><?= htmlspecialchars($u['LastLogin']) ?></td>
                <td>
                    <?php if (!empty($u['IsActive'])): ?>
                        <span class="badge-status bg-success text-light">Active</span>
                    <?php else: ?>
                        <span class="badge-status bg-secondary text-light">Inactive</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a class="btn btn-sm btn-primary mb-1" href="edit_user.php?id=<?= $u['UserID'] ?>">
                        Edit
                    </a>
                    <a class="btn btn-sm btn-danger" href="delete_user.php?id=<?= $u['UserID'] ?>">
                        Delete
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
</table>
