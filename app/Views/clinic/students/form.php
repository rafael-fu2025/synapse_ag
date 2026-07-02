<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php $isEdit = ($mode === 'edit'); ?>

<div class="card" style="max-width: 800px;">
    <div class="card-header">
        <i class="fas fa-<?= $isEdit ? 'edit' : 'user-plus' ?>" style="margin-right: 0.5rem; color: var(--primary-600);"></i>
        <?= $isEdit ? 'Edit Student' : 'Register New Student' ?>
    </div>
    <div class="card-body">
        <?php $errors = session()->get('errors') ?? []; ?>

        <form method="POST" action="<?= $isEdit ? '/clinic/students/update/' . $student['id'] : '/clinic/students/store' ?>" novalidate
              data-synapse-form-dialog
              data-dialog-title="<?= $isEdit ? 'Edit Student' : 'Register New Student' ?>"
              data-dialog-icon="fas fa-<?= $isEdit ? 'edit' : 'user-plus' ?>"
              data-dialog-submit-label="<?= $isEdit ? 'Update Student' : 'Register Student' ?>"
              data-dialog-cancel-label="Cancel"
              data-dialog-width>
            <?= csrf_field() ?>

            <?php if (! empty($errors)): ?>
                <div role="alert" id="student-errors" class="syn-alert syn-alert--danger" style="margin-bottom: 1.25rem;">
                    <i class="fas fa-triangle-exclamation"></i>
                    <div>
                        <strong>Please fix the following before submitting:</strong>
                        <ul style="margin: 0.5rem 0 0; padding-left: 1.25rem;">
                            <?php foreach ($errors as $field => $msg): ?>
                                <li><?= esc($msg) ?></li>
                            <?php endforeach ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Personal Info Section -->
            <h3 style="font-size: 0.85rem; font-weight: 600; color: #374151; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid #E5E7EB;">
                <i class="fas fa-user" style="margin-right: 0.5rem; color: var(--primary-500);"></i> Personal Information
            </h3>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <label for="first_name" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">First Name *</label>
                    <input id="first_name" type="text" name="first_name" value="<?= old('first_name', $student['first_name'] ?? '') ?>" required aria-required="true"
                        <?= isset($errors['first_name']) ? 'aria-invalid="true" aria-describedby="first_name-err"' : '' ?>
                        style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                    <?php if (isset($errors['first_name'])): ?>
                        <p id="first_name-err" style="margin: 0.3rem 0 0; font-size: 0.75rem; color: #DC2626;"><?= esc($errors['first_name']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="middle_name" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Middle Name</label>
                    <input id="middle_name" type="text" name="middle_name" value="<?= old('middle_name', $student['middle_name'] ?? '') ?>" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                </div>
                <div>
                    <label for="last_name" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Last Name *</label>
                    <input id="last_name" type="text" name="last_name" value="<?= old('last_name', $student['last_name'] ?? '') ?>" required aria-required="true"
                        <?= isset($errors['last_name']) ? 'aria-invalid="true" aria-describedby="last_name-err"' : '' ?>
                        style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                    <?php if (isset($errors['last_name'])): ?>
                        <p id="last_name-err" style="margin: 0.3rem 0 0; font-size: 0.75rem; color: #DC2626;"><?= esc($errors['last_name']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <label for="email" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Email *</label>
                    <input id="email" type="email" name="email" value="<?= old('email', $student['email'] ?? '') ?>" <?= $isEdit ? 'disabled' : 'required' ?> <?= $isEdit ? '' : 'aria-required="true"' ?>
                        <?= isset($errors['email']) ? 'aria-invalid="true" aria-describedby="email-err"' : '' ?>
                        style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif; <?= $isEdit ? 'background: #F9FAFB; color: #9CA3AF;' : '' ?>">
                    <?php if (isset($errors['email'])): ?>
                        <p id="email-err" style="margin: 0.3rem 0 0; font-size: 0.75rem; color: #DC2626;"><?= esc($errors['email']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="phone" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Phone</label>
                    <input id="phone" type="tel" name="phone" value="<?= old('phone', $student['phone'] ?? '') ?>" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                </div>
            </div>

            <!-- Academic Info -->
            <h3 style="font-size: 0.85rem; font-weight: 600; color: #374151; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid #E5E7EB;">
                <i class="fas fa-graduation-cap" style="margin-right: 0.5rem; color: #10B981;"></i> Academic Information
            </h3>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <label for="student_number" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Student Number *</label>
                    <input id="student_number" type="text" name="student_number" value="<?= old('student_number', $student['student_number'] ?? '') ?>" <?= $isEdit ? 'disabled' : 'required' ?> <?= $isEdit ? '' : 'aria-required="true"' ?>
                        <?= isset($errors['student_number']) ? 'aria-invalid="true" aria-describedby="student_number-err"' : '' ?>
                        style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif; <?= $isEdit ? 'background: #F9FAFB; color: #9CA3AF;' : '' ?>">
                    <?php if (isset($errors['student_number'])): ?>
                        <p id="student_number-err" style="margin: 0.3rem 0 0; font-size: 0.75rem; color: #DC2626;"><?= esc($errors['student_number']) ?></p>
                    <?php endif; ?>
                </div>
                <div>
                    <label for="course" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Course</label>
                    <input id="course" type="text" name="course" value="<?= old('course', $student['course'] ?? '') ?>" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                </div>
                <div>
                    <label for="year_level" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Year Level</label>
                    <select id="year_level" name="year_level" data-synapse-dropdown>
                        <option value="">â€”</option>
                        <?php for ($i = 1; $i <= 6; $i++): ?>
                            <option value="<?= $i ?>" <?= old('year_level', $student['year_level'] ?? '') == $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div>
                    <label for="section" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Section</label>
                    <input id="section" type="text" name="section" value="<?= old('section', $student['section'] ?? '') ?>" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                </div>
            </div>

            <!-- Medical Info -->
            <h3 style="font-size: 0.85rem; font-weight: 600; color: #374151; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid #E5E7EB;">
                <i class="fas fa-heart-pulse" style="margin-right: 0.5rem; color: #EF4444;"></i> Medical Information
            </h3>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <label for="date_of_birth" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Date of Birth</label>
                    <input id="date_of_birth" type="text" class="syn-datepicker" name="date_of_birth" value="<?= old('date_of_birth', $student['date_of_birth'] ?? '') ?>" placeholder="YYYY-MM-DD" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                </div>
                <div>
                    <label for="gender" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Gender</label>
                    <select id="gender" name="gender" data-synapse-dropdown>
                        <option value="">â€”</option>
                        <option value="male" <?= old('gender', $student['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                        <option value="female" <?= old('gender', $student['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                        <option value="other" <?= old('gender', $student['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div>
                    <label for="blood_type" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Blood Type</label>
                    <input id="blood_type" type="text" name="blood_type" value="<?= old('blood_type', $student['blood_type'] ?? '') ?>" placeholder="e.g. O+" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                </div>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <label for="address" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Address</label>
                <textarea id="address" name="address" rows="2" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif; resize: vertical;"><?= old('address', $student['address'] ?? '') ?></textarea>
            </div>

            <!-- IoT Identifiers -->
            <h3 style="font-size: 0.85rem; font-weight: 600; color: #374151; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid #E5E7EB;">
                <i class="fas fa-qrcode" style="margin-right: 0.5rem; color: #8B5CF6;"></i> IoT Identifiers
            </h3>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <label for="qr_code" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">QR Code</label>
                    <input id="qr_code" type="text" name="qr_code" value="<?= old('qr_code', $student['qr_code'] ?? '') ?>" placeholder="Scan or enter QR code value" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                </div>
                <div>
                    <label for="rfid_tag" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">RFID Tag</label>
                    <input id="rfid_tag" type="text" name="rfid_tag" value="<?= old('rfid_tag', $student['rfid_tag'] ?? '') ?>" placeholder="Scan or enter RFID tag" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                </div>
            </div>

            <?php if (! $isEdit): ?>
            <!-- Emergency Contact (initial) -->
            <h3 style="font-size: 0.85rem; font-weight: 600; color: #374151; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid #E5E7EB;">
                <i class="fas fa-phone" style="margin-right: 0.5rem; color: #10B981;"></i> Emergency Contact (Optional)
            </h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <label for="contact_name" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Contact Name</label>
                    <input id="contact_name" type="text" name="contact_name" value="<?= old('contact_name') ?>" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                </div>
                <div>
                    <label for="contact_relationship" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Relationship</label>
                    <input id="contact_relationship" type="text" name="contact_relationship" value="<?= old('contact_relationship') ?>" placeholder="e.g. Mother" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                </div>
                <div>
                    <label for="contact_phone" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Phone</label>
                    <input id="contact_phone" type="tel" name="contact_phone" value="<?= old('contact_phone') ?>" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                </div>
            </div>

            <!-- Initial Allergy -->
            <h3 style="font-size: 0.85rem; font-weight: 600; color: #374151; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid #E5E7EB;">
                <i class="fas fa-triangle-exclamation" style="margin-right: 0.5rem; color: #F59E0B;"></i> Known Allergy (Optional)
            </h3>
            <div style="display: grid; grid-template-columns: 2fr 1fr 2fr; gap: 1rem; margin-bottom: 1.5rem;">
                <div>
                    <label for="allergen" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Allergen</label>
                    <input id="allergen" type="text" name="allergen" value="<?= old('allergen') ?>" placeholder="e.g. Penicillin" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                </div>
                <div>
                    <label for="allergy_severity" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Severity</label>
                    <select id="allergy_severity" name="allergy_severity" data-synapse-dropdown>
                        <option value="mild">Mild</option>
                        <option value="moderate">Moderate</option>
                        <option value="severe">Severe</option>
                    </select>
                </div>
                <div>
                    <label for="allergy_reaction" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Reaction</label>
                    <input id="allergy_reaction" type="text" name="allergy_reaction" value="<?= old('allergy_reaction') ?>" placeholder="e.g. Rashes, swelling" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                </div>
            </div>
            <?php endif; ?>

            <!-- Submit -->
            <div style="display: flex; gap: 0.75rem; justify-content: flex-end; padding-top: 1rem; border-top: 1px solid #E5E7EB;">
                <a href="/clinic/students" style="padding: 0.6rem 1.25rem; background: #F3F4F6; color: #374151; border-radius: 0.5rem; font-size: 0.85rem; text-decoration: none;">Cancel</a>
                <button type="submit" style="padding: 0.6rem 1.5rem; background: var(--primary-600); color: white; border: none; border-radius: 0.5rem; font-family: 'Inter', sans-serif; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-save" style="margin-right: 0.25rem;"></i> <?= $isEdit ? 'Update Student' : 'Register Student' ?>
                </button>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
