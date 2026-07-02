<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 1.25rem;">
    <!-- Patient Card -->
    <div class="card">
        <div class="card-header"><i class="fas fa-user" style="margin-right: 0.5rem; color: var(--primary-500);"></i> Student</div>
        <div class="card-body" style="text-align: center;">
            <div style="width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, #8B5CF6, #A78BFA); display: inline-flex; align-items: center; justify-content: center; color: white; font-size: 1.1rem; font-weight: 700; margin-bottom: 0.75rem;">
                <?= strtoupper(substr($student['first_name'], 0, 1)) ?><?= strtoupper(substr($student['last_name'], 0, 1)) ?>
            </div>
            <h3 style="font-size: 1rem; font-weight: 700;"><?= esc($student['full_name']) ?></h3>
            <p style="font-size: 0.8rem; color: var(--primary-500); font-weight: 600;"><?= esc($student['student_number']) ?></p>
        </div>
    </div>

    <!-- Booking Form -->
    <div class="card">
        <div class="card-header"><i class="fas fa-calendar-plus" style="margin-right: 0.5rem; color: #8B5CF6;"></i> Book Appointment</div>
        <div class="card-body">
            <!-- Date Picker -->
            <form method="GET" action="/counselling/appointments/create/<?= $student['id'] ?>" style="display: flex; gap: 0.5rem; margin-bottom: 1.25rem;">
                <input type="text" class="syn-datepicker" name="date" value="<?= esc($date) ?>" placeholder="YYYY-MM-DD" min="<?= date('Y-m-d') ?>" style="flex: 1; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                <button type="submit" style="padding: 0.5rem 1rem; background: var(--primary-600); color: white; border: none; border-radius: 0.375rem; font-size: 0.8rem; cursor: pointer;">Check Slots</button>
            </form>

            <?php $errors = session()->get('errors') ?? []; ?>

            <form method="POST" action="/counselling/appointments/store" novalidate
                  data-synapse-form-dialog
                  data-dialog-title="Book Counselling Appointment"
                  data-dialog-icon="fas fa-calendar-plus"
                  data-dialog-submit-label="Book Appointment"
                  data-dialog-cancel-label="Cancel"
                  data-dialog-width>
                <?= csrf_field() ?>
                <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                <input type="hidden" name="appointment_date" value="<?= esc($date) ?>">

                <?php if (! empty($errors)): ?>
                    <div role="alert" id="appt-errors" class="syn-alert syn-alert--danger" style="margin-bottom: 1.25rem;">
                        <i class="fas fa-triangle-exclamation"></i>
                        <div>
                            <strong>Please fix the following before booking:</strong>
                            <ul style="margin: 0.5rem 0 0; padding-left: 1.25rem;">
                                <?php foreach ($errors as $field => $msg): ?>
                                    <li><?= esc($msg) ?></li>
                                <?php endforeach ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Available Slots -->
                <h4 style="font-size: 0.8rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">Available Slots for <?= date('l, M d, Y', strtotime($date)) ?></h4>

                <?php if (empty($slots)): ?>
                    <div style="padding: 1.5rem; text-align: center; color: #9CA3AF; background: #F9FAFB; border-radius: 0.5rem;">
                        <i class="fas fa-calendar-xmark" style="font-size: 1.25rem; margin-bottom: 0.25rem;"></i>
                        <p style="font-size: 0.8rem;">No available slots on this date. Try another day.</p>
                    </div>
                <?php else: ?>
                    <fieldset style="margin: 0 0 1.25rem 0; padding: 0; border: 0;">
                        <legend class="sr-only" style="position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden;">Available time slots</legend>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.5rem;">
                            <?php foreach ($slots as $s): ?>
                                <label style="display: block; padding: 0.6rem; border: 2px solid <?= $s['available'] ? '#E5E7EB' : '#F3F4F6' ?>; border-radius: 0.5rem; cursor: <?= $s['available'] ? 'pointer' : 'not-allowed' ?>; opacity: <?= $s['available'] ? '1' : '0.5' ?>; transition: all 150ms;">
                                    <input type="radio" name="slot_index" value="<?= $s['id'] ?>" <?= !$s['available'] ? 'disabled' : '' ?> aria-label="Slot <?= date('h:i A', strtotime($s['start_time'])) ?> with Dr. <?= esc($s['last_name']) ?> — <?= $s['available'] ? 'Available' : 'Full' ?>" style="display: none;"
                                        data-counsellor="<?= $s['counsellor_id'] ?>"
                                        data-start="<?= $s['start_time'] ?>"
                                        data-end="<?= $s['end_time'] ?>"
                                        onchange="selectSlot(this)">
                                    <div style="font-size: 0.8rem; font-weight: 600; color: #111827;"><?= date('h:i A', strtotime($s['start_time'])) ?> â€“ <?= date('h:i A', strtotime($s['end_time'])) ?></div>
                                    <div style="font-size: 0.7rem; color: #6B7280;">Dr. <?= esc($s['first_name'] . ' ' . $s['last_name']) ?></div>
                                    <div style="font-size: 0.65rem; color: <?= $s['available'] ? '#059669' : '#DC2626' ?>; font-weight: 500; margin-top: 0.15rem;"><?= $s['available'] ? 'Available' : 'Full' ?></div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>
                <?php endif; ?>

                <input type="hidden" name="counsellor_id" id="selectedCounsellor">
                <input type="hidden" name="start_time" id="selectedStart">
                <input type="hidden" name="end_time" id="selectedEnd">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div>
                        <label for="appt_type" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Appointment Type</label>
                        <select id="appt_type" name="type" data-synapse-dropdown>
                            <option value="initial">Initial</option>
                            <option value="follow_up">Follow-Up</option>
                            <option value="crisis">Crisis</option>
                            <option value="referral_based">Referral-Based</option>
                        </select>
                    </div>
                    <div>
                        <label for="appt_reason" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Reason (Optional)</label>
                        <input id="appt_reason" type="text" name="reason" placeholder="Brief reason for visit" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                    </div>
                </div>

                <div style="display: flex; gap: 0.75rem; justify-content: flex-end; padding-top: 1rem; border-top: 1px solid #E5E7EB;">
                    <a href="/counselling" style="padding: 0.6rem 1.25rem; background: #F3F4F6; color: #374151; border-radius: 0.5rem; font-size: 0.85rem; text-decoration: none;">Cancel</a>
                    <button type="submit" style="padding: 0.6rem 1.5rem; background: #8B5CF6; color: white; border: none; border-radius: 0.5rem; font-family: 'Inter', sans-serif; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                        <i class="fas fa-calendar-check"></i> Book Appointment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function selectSlot(radio) {
    document.getElementById('selectedCounsellor').value = radio.dataset.counsellor;
    document.getElementById('selectedStart').value = radio.dataset.start;
    document.getElementById('selectedEnd').value = radio.dataset.end;

    document.querySelectorAll('input[name="slot_index"]').forEach(r => {
        r.parentElement.style.borderColor = '#E5E7EB';
        r.parentElement.style.background = 'white';
    });
    radio.parentElement.style.borderColor = '#8B5CF6';
    radio.parentElement.style.background = '#F5F3FF';
}
</script>
<?= $this->endSection() ?>
