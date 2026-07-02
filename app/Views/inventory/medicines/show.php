<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.25rem;">
    <!-- Left: Medicine Info -->
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <span><i class="fas fa-capsules" style="margin-right: 0.5rem; color: #10B981;"></i> Medicine Details</span>
            <a href="/inventory/medicines/edit/<?= $medicine['id'] ?>" data-synapse-form-link data-dialog-title="Edit <?= esc($medicine['generic_name']) ?>" data-dialog-width="700" style="padding: 0.25rem 0.5rem; background: #F3F4F6; color: #374151; border-radius: 0.375rem; font-size: 0.7rem; text-decoration: none;">Edit</a>
        </div>
        <div class="card-body">
            <h2 style="font-size: 1.1rem; font-weight: 700; color: #111827; margin-bottom: 0.25rem;"><?= esc($medicine['generic_name']) ?></h2>
            <?php if ($medicine['brand_name']): ?>
                <p style="font-size: 0.85rem; color: #6B7280;"><?= esc($medicine['brand_name']) ?></p>
            <?php endif; ?>

            <div style="margin-top: 1rem; display: grid; gap: 0.5rem; font-size: 0.8rem;">
                <div style="display: flex; justify-content: space-between; padding: 0.4rem 0; border-bottom: 1px solid #F3F4F6;">
                    <span style="color: #9CA3AF;">Category</span>
                    <span style="font-weight: 500;"><?= esc($medicine['category'] ?? '—') ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.4rem 0; border-bottom: 1px solid #F3F4F6;">
                    <span style="color: #9CA3AF;">Dosage Form</span>
                    <span style="font-weight: 500;"><?= esc($medicine['dosage_form'] ?? '—') ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.4rem 0; border-bottom: 1px solid #F3F4F6;">
                    <span style="color: #9CA3AF;">Strength</span>
                    <span style="font-weight: 500;"><?= esc($medicine['dosage_strength'] ?? '—') ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.4rem 0; border-bottom: 1px solid #F3F4F6;">
                    <span style="color: #9CA3AF;">Unit</span>
                    <span style="font-weight: 500;"><?= esc($medicine['unit']) ?></span>
                </div>
                <div style="display: flex; justify-content: space-between; padding: 0.4rem 0; border-bottom: 1px solid #F3F4F6;">
                    <span style="color: #9CA3AF;">Reorder At</span>
                    <span style="font-weight: 500;"><?= $medicine['reorder_threshold'] ?> <?= esc($medicine['unit']) ?></span>
                </div>
            </div>

            <div style="margin-top: 1.25rem; text-align: center; padding: 1rem; border-radius: 0.5rem; <?= $medicine['total_stock'] <= $medicine['reorder_threshold'] ? 'background: #FEF2F2; border: 1px solid #FECACA;' : 'background: #ECFDF5; border: 1px solid #A7F3D0;' ?>">
                <p style="font-size: 2rem; font-weight: 800; <?= $medicine['total_stock'] <= $medicine['reorder_threshold'] ? 'color: #DC2626;' : 'color: #059669;' ?>"><?= $medicine['total_stock'] ?></p>
                <p style="font-size: 0.75rem; <?= $medicine['total_stock'] <= $medicine['reorder_threshold'] ? 'color: #991B1B;' : 'color: #065F46;' ?>"><?= esc($medicine['unit']) ?> Total Stock</p>
            </div>

            <?php if (isset($forecast)): ?>
                <div style="margin-top: 1.25rem; padding: 1rem; border-radius: 0.5rem; background: var(--primary-50); border: 1px solid #C7D2FE;">
                    <h4 style="font-size: 0.8rem; font-weight: 700; color: var(--primary-600); margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.25rem;">
                        <i class="fas fa-robot"></i> Predictive AI Forecast
                    </h4>
                    <div style="font-size: 0.75rem; color: #374151; display: grid; gap: 0.4rem;">
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #C7D2FE; padding-bottom: 0.25rem;">
                            <span>Est. Consumption:</span>
                            <span style="font-weight: 600;"><?= number_format($forecast['predicted_daily_usage'], 2) ?> <?= esc($medicine['unit']) ?>/day</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #C7D2FE; padding-bottom: 0.25rem;">
                            <span>Projected Stockout:</span>
                            <span style="font-weight: 700; color: #DC2626;"><?= date('M d, Y', strtotime($forecast['predicted_stockout_date'])) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #C7D2FE; padding-bottom: 0.25rem;">
                            <span>Reorder Prompt:</span>
                            <span style="font-weight: 700; color: #D97706;"><?= date('M d, Y', strtotime($forecast['predicted_reorder_date'])) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Seasonality Weight:</span>
                            <span style="font-weight: 600;"><?= number_format($forecast['seasonality_factor'] * 100, 0) ?>%</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <a href="/inventory/medicines/<?= $medicine['id'] ?>/batch" data-synapse-form-link data-dialog-title="Receive Batch — <?= esc($medicine['generic_name']) ?>" data-dialog-width="650" style="display: block; margin-top: 1rem; padding: 0.6rem; background: var(--primary-600); color: white; border-radius: 0.5rem; text-align: center; text-decoration: none; font-size: 0.85rem; font-weight: 600;">
                <i class="fas fa-plus"></i> Receive New Batch
            </a>
        </div>
    </div>

    <!-- Right: Batches -->
    <div class="card">
        <div class="card-header"><i class="fas fa-boxes-stacked" style="margin-right: 0.5rem; color: #3B82F6;"></i> Active Batches (FEFO Order)</div>
        <div class="card-body" style="padding: 0;">
            <?php if (empty($medicine['batches'])): ?>
                <div style="padding: 2rem; text-align: center; color: #9CA3AF;">
                    <i class="fas fa-box-open" style="font-size: 1.5rem; display: block; margin-bottom: 0.5rem;"></i>
                    No active batches. Receive a new batch to start tracking.
                </div>
            <?php else: ?>
                <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                    <thead>
                        <tr style="background: #F9FAFB; border-bottom: 1px solid #E5E7EB;">
                            <th scope="col" style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: #374151;">Batch #</th>
                            <th scope="col" style="padding: 0.6rem 1rem; text-align: center; font-weight: 600; color: #374151;">Received</th>
                            <th scope="col" style="padding: 0.6rem 1rem; text-align: center; font-weight: 600; color: #374151;">Expires</th>
                            <th scope="col" style="padding: 0.6rem 1rem; text-align: center; font-weight: 600; color: #374151;">Remaining</th>
                            <th scope="col" style="padding: 0.6rem 1rem; text-align: left; font-weight: 600; color: #374151;">Supplier</th>
                            <th scope="col" style="padding: 0.6rem 1rem; text-align: center; font-weight: 600; color: #374151;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($medicine['batches'] as $i => $b): ?>
                            <?php
                            $daysToExpiry = (int) ((strtotime($b['expiration_date']) - time()) / 86400);
                            ?>
                            <tr style="border-bottom: 1px solid #F3F4F6; <?= $i === 0 ? 'background: #FFFBEB;' : '' ?>">
                                <td style="padding: 0.6rem 1rem; font-weight: 600;">
                                    <?= esc($b['batch_number']) ?>
                                    <?php if ($i === 0): ?>
                                        <span style="padding: 0.1rem 0.35rem; background: #FEF3C7; color: #92400E; border-radius: 999px; font-size: 0.55rem; font-weight: 700; margin-left: 0.25rem;">FEFO NEXT</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 0.6rem 1rem; text-align: center;"><?= date('M d, Y', strtotime($b['received_date'])) ?></td>
                                <td style="padding: 0.6rem 1rem; text-align: center; <?= $daysToExpiry <= 30 ? 'color: #DC2626; font-weight: 600;' : '' ?>">
                                    <?= date('M d, Y', strtotime($b['expiration_date'])) ?>
                                    <?php if ($daysToExpiry <= 30 && $daysToExpiry > 0): ?>
                                        <br><span style="font-size: 0.65rem;">(<?= $daysToExpiry ?>d left)</span>
                                    <?php elseif ($daysToExpiry <= 0): ?>
                                        <br><span style="font-size: 0.65rem; color: #DC2626;">EXPIRED</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 0.6rem 1rem; text-align: center; font-weight: 700;">
                                    <?= $b['quantity_remaining'] ?> / <?= $b['quantity_received'] ?>
                                </td>
                                <td style="padding: 0.6rem 1rem; color: #6B7280;"><?= esc($b['supplier'] ?? '—') ?></td>
                                <td style="padding: 0.6rem 1rem; text-align: center;">
                                    <span style="padding: 0.15rem 0.5rem; border-radius: 999px; font-size: 0.65rem; font-weight: 600; <?php
                                        echo match($b['status']) {
                                            'depleted' => 'background: #F3F4F6; color: #6B7280;',
                                            'expired'  => 'background: #FEF2F2; color: #DC2626;',
                                            'recalled' => 'background: #FEF2F2; color: #DC2626;',
                                            default    => 'background: #ECFDF5; color: #059669;',
                                        };
                                    ?>"><?= ucfirst($b['status']) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
