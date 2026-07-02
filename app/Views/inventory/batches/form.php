<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php $errors = session()->get('errors') ?? []; ?>

<div class="card" style="max-width: 650px;">
    <div class="card-header"><i class="fas fa-box" style="margin-right: 0.5rem; color: #3B82F6;"></i> Receive Batch: <?= esc($medicine['generic_name']) ?></div>
    <div class="card-body">
        <?php if (! empty($errors)): ?>
            <div role="alert" style="padding: 0.75rem 1rem; margin-bottom: 1.25rem; background: #FEF2F2; border: 1px solid #FECACA; color: #991B1B; border-radius: 0.375rem; font-size: 0.85rem;">
                <strong><i class="fas fa-triangle-exclamation" style="margin-right: 0.35rem;"></i>Please correct the following:</strong>
                <ul style="margin: 0.5rem 0 0 1.25rem; padding: 0;">
                    <?php foreach ($errors as $field => $msg): ?>
                        <li><?= esc($msg) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="/inventory/medicines/<?= $medicine['id'] ?>/batch" data-synapse-form-dialog>
            <?= csrf_field() ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <label for="batch_number" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Batch Number <span aria-hidden="true">*</span><span class="sr-only">required</span></label>
                    <input type="text" id="batch_number" name="batch_number" required aria-required="true" value="<?= old('batch_number') ?>" placeholder="e.g. BN-2027-001" <?= isset($errors['batch_number']) ? 'aria-invalid="true" aria-describedby="err-batch_number"' : '' ?> style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid <?= isset($errors['batch_number']) ? '#DC2626' : '#E5E7EB' ?>; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                    <?php if (isset($errors['batch_number'])): ?>
                        <p id="err-batch_number" role="alert" style="margin: 0.3rem 0 0; color: #DC2626; font-size: 0.75rem;"><?= esc($errors['batch_number']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="quantity_received" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Quantity Received <span aria-hidden="true">*</span><span class="sr-only">required</span></label>
                    <input type="number" id="quantity_received" name="quantity_received" required aria-required="true" min="1" value="<?= old('quantity_received') ?>" placeholder="e.g. 100" <?= isset($errors['quantity_received']) ? 'aria-invalid="true" aria-describedby="err-quantity_received"' : '' ?> style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid <?= isset($errors['quantity_received']) ? '#DC2626' : '#E5E7EB' ?>; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                    <?php if (isset($errors['quantity_received'])): ?>
                        <p id="err-quantity_received" role="alert" style="margin: 0.3rem 0 0; color: #DC2626; font-size: 0.75rem;"><?= esc($errors['quantity_received']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                <div>
                    <label for="received_date" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Received Date <span aria-hidden="true">*</span><span class="sr-only">required</span></label>
                    <input type="text" id="received_date" class="syn-datepicker" name="received_date" required aria-required="true" value="<?= old('received_date', date('Y-m-d')) ?>" placeholder="YYYY-MM-DD" <?= isset($errors['received_date']) ? 'aria-invalid="true" aria-describedby="err-received_date"' : '' ?> style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid <?= isset($errors['received_date']) ? '#DC2626' : '#E5E7EB' ?>; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                    <?php if (isset($errors['received_date'])): ?>
                        <p id="err-received_date" role="alert" style="margin: 0.3rem 0 0; color: #DC2626; font-size: 0.75rem;"><?= esc($errors['received_date']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="expiration_date" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Expiration Date <span aria-hidden="true">*</span><span class="sr-only">required</span></label>
                    <input type="text" id="expiration_date" class="syn-datepicker" name="expiration_date" required aria-required="true" value="<?= old('expiration_date') ?>" placeholder="YYYY-MM-DD" <?= isset($errors['expiration_date']) ? 'aria-invalid="true" aria-describedby="err-expiration_date"' : '' ?> style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid <?= isset($errors['expiration_date']) ? '#DC2626' : '#E5E7EB' ?>; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                    <?php if (isset($errors['expiration_date'])): ?>
                        <p id="err-expiration_date" role="alert" style="margin: 0.3rem 0 0; color: #DC2626; font-size: 0.75rem;"><?= esc($errors['expiration_date']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label for="supplier" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Supplier</label>
                <input type="text" id="supplier" name="supplier" value="<?= old('supplier') ?>" placeholder="e.g. Metro Drug, Zuellig Pharma" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label for="notes" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Notes</label>
                <textarea id="notes" name="notes" rows="2" placeholder="Any remarks about this batch..." style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif; resize: vertical;"><?= old('notes') ?></textarea>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; padding-top: 1rem; border-top: 1px solid #E5E7EB;">
                <button type="submit" style="padding: 0.6rem 1.5rem; background: #3B82F6; color: white; border: none; border-radius: 0.5rem; font-family: 'Inter', sans-serif; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-box" style="margin-right: 0.25rem;"></i> Receive Batch
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>