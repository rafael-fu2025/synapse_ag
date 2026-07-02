<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <span><i class="fas fa-triangle-exclamation" style="margin-right: 0.5rem; color: #DC2626;"></i> <?= esc($heading) ?></span>
        <a href="/inventory" style="padding: 0.3rem 0.6rem; background: #F3F4F6; color: #374151; border-radius: 0.375rem; font-size: 0.7rem; text-decoration: none;">← Back to Inventory</a>
    </div>
    <div class="card-body" style="padding: 0;">
        <?php if (empty($medicines)): ?>
            <div style="padding: 3rem; text-align: center; color: #10B981;">
                <i class="fas fa-check-circle" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                <p style="font-weight: 600;">All stock levels are healthy!</p>
                <p style="font-size: 0.8rem; color: #6B7280; margin-top: 0.25rem;">No medicines are below their reorder threshold.</p>
            </div>
        <?php else: ?>
            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                <thead>
                    <tr style="background: #FEF2F2; border-bottom: 1px solid #FECACA;">
                        <th scope="col" style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: #991B1B;">Medicine</th>
                        <th scope="col" style="padding: 0.6rem 1rem; text-align: center; font-weight: 600; color: #991B1B;">Current Stock</th>
                        <th scope="col" style="padding: 0.6rem 1rem; text-align: center; font-weight: 600; color: #991B1B;">Threshold</th>
                        <th scope="col" style="padding: 0.6rem 1rem; text-align: center; font-weight: 600; color: #991B1B;">Deficit</th>
                        <th scope="col" style="padding: 0.6rem 1rem; text-align: center; font-weight: 600; color: #991B1B;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($medicines as $m): ?>
                        <?php $stock = (int)($m['total_stock'] ?? 0); ?>
                        <tr style="border-bottom: 1px solid #F3F4F6;">
                            <td style="padding: 0.6rem 1rem;">
                                <div style="font-weight: 600;"><?= esc($m['generic_name']) ?></div>
                                <div style="font-size: 0.75rem; color: #6B7280;"><?= esc($m['brand_name'] ?? '') ?></div>
                            </td>
                            <td style="padding: 0.6rem 1rem; text-align: center; font-weight: 700; color: #DC2626;"><?= $stock ?> <?= esc($m['unit']) ?></td>
                            <td style="padding: 0.6rem 1rem; text-align: center; color: #6B7280;"><?= $m['reorder_threshold'] ?></td>
                            <td style="padding: 0.6rem 1rem; text-align: center; font-weight: 600; color: #DC2626;">âˆ’<?= $m['reorder_threshold'] - $stock ?></td>
                            <td style="padding: 0.6rem 1rem; text-align: center;">
                                <a href="/inventory/medicines/<?= $m['id'] ?>/batch" data-synapse-form-link data-dialog-title="Receive Batch — <?= esc($m['generic_name']) ?>" data-dialog-width="650" aria-label="Reorder <?= esc($m['generic_name']) ?>" style="padding: 0.25rem 0.5rem; background: var(--primary-600); color: white; border-radius: 0.375rem; font-size: 0.7rem; text-decoration: none; font-weight: 500;">Reorder</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>
