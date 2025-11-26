<?php $title = 'Login'; ?>

<h1>Login</h1>

<?php if (isset($_SESSION['error'])): ?>
    <div style="padding: 1rem; background: #fee; border: 1px solid #fcc; border-radius: 4px; margin-bottom: 1rem;">
        <?= e($_SESSION['error']) ?>
        <?php unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<form method="POST" action="/login" style="max-width: 400px;">
    <div style="margin-bottom: 1rem;">
        <label style="display: block; margin-bottom: 0.25rem;">Email:</label>
        <input type="email" name="email" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
    </div>

    <div style="margin-bottom: 1rem;">
        <label style="display: block; margin-bottom: 0.25rem;">Password:</label>
        <input type="password" name="password" required style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px;">
    </div>

    <input type="hidden" name="_csrf" value="<?= \App\Core\CSRF::token() ?>">

    <button type="submit" class="btn">Login</button>

    <p style="margin-top: 1rem;">
        Don't have an account? <a href="/register">Register</a>
    </p>
</form>
