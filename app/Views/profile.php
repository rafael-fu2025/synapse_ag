<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<!--
  Flash messages are rendered ONCE by the layout (layouts/main.php).
  Per the design system, layouts/main.php is the single source of truth
  for flash message rendering — it shows the persistent alert at the
  top of .page-content AND fires a transient toast in the bottom-right.
  Rendering flashdata here would duplicate those surfaces (we previously
  saw "Profile updated successfully." appear twice as alerts).
-->

<div class="card" style="max-width: 600px;">
    <div class="card-header">
        <i class="fas fa-user-circle" style="color: var(--primary-500); margin-right: 0.5rem;"></i> My Profile
    </div>
    <div class="card-body">
        <form action="/profile/update" method="POST">
            <?= csrf_field() ?>
            
            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.9rem;">Email Address</label>
                <input type="email" value="<?= esc($user['email']) ?>" class="form-control" disabled style="background: var(--gray-100); color: var(--gray-500);">
                <small style="color: var(--gray-400); display: block; margin-top: 0.25rem;">Email cannot be changed.</small>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.9rem;">First Name</label>
                    <input type="text" name="first_name" value="<?= esc(old('first_name', $user['first_name'])) ?>" class="form-control" required>
                </div>
                <div>
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.9rem;">Last Name</label>
                    <input type="text" name="last_name" value="<?= esc(old('last_name', $user['last_name'])) ?>" class="form-control" required>
                </div>
            </div>

            <hr style="border: none; border-top: 1px solid var(--gray-200); margin: 2rem 0;">
            <h4 style="font-size: 1rem; margin-bottom: 1rem;">Change Password</h4>
            <p style="font-size: 0.85rem; color: var(--gray-500); margin-bottom: 1.5rem;">Leave these blank if you do not wish to change your password.</p>

            <div style="margin-bottom: 1.5rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.9rem;">New Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••">
            </div>

            <div style="margin-bottom: 2rem;">
                <label style="display: block; margin-bottom: 0.5rem; font-weight: 500; font-size: 0.9rem;">Confirm New Password</label>
                <input type="password" name="password_confirm" class="form-control" placeholder="••••••••">
            </div>

            <div style="display: flex; justify-content: flex-end;">
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
