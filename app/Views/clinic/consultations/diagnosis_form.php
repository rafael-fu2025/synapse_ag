<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div class="card" style="max-width: 700px;">
    <div class="card-header"><i class="fas fa-stethoscope" style="margin-right: 0.5rem; color: #8B5CF6;"></i> Diagnosis & Clinical Notes</div>
    <div class="card-body">
        <form method="POST" action="/clinic/consultations/diagnosis/<?= $consult['id'] ?>">
            <?= csrf_field() ?>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Diagnosis *</label>
                <input type="text" name="diagnosis" value="<?= old('diagnosis', $consult['diagnosis'] ?? '') ?>" required placeholder="e.g. Acute pharyngitis, Tension headache" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Clinical Notes</label>
                <textarea name="notes" rows="5" placeholder="Additional observations, instructions, or clinical notes..." style="width: 100%; padding: 0.6rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif; resize: vertical;"><?= old('notes', $consult['notes'] ?? '') ?></textarea>
            </div>

            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; padding-top: 1rem; border-top: 1px solid #E5E7EB;">
                <a href="/clinic/consultations/<?= $consult['id'] ?>" style="padding: 0.6rem 1.25rem; background: #F3F4F6; color: #374151; border-radius: 0.5rem; font-size: 0.85rem; text-decoration: none;">Cancel</a>
                <button type="submit" style="padding: 0.6rem 1.5rem; background: #8B5CF6; color: white; border: none; border-radius: 0.5rem; font-family: 'Inter', sans-serif; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-save" style="margin-right: 0.25rem;"></i> Save Diagnosis
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
