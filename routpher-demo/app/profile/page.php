<?php
// Require authentication
if (!isset($GLOBALS['auth_user'])) {
    redirect('/login');
}

$user = $GLOBALS['auth_user'];
$title = 'Profile';
?>

<h1>Profile</h1>

<div style="padding: 1.5rem; background: #f9f9f9; border-radius: 8px; margin-top: 1rem;">
    <p><strong>Name:</strong> <?= e($user['name']) ?></p>
    <p><strong>Email:</strong> <?= e($user['email']) ?></p>
    <p><strong>Role:</strong> <?= e($user['role']) ?></p>
    <p><strong>Member since:</strong> <?= date('F j, Y', $user['created_at']) ?></p>
</div>

<p style="margin-top: 2rem;">
    <a href="/logout" class="btn">Logout</a>
</p>
