<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem;">
    <!-- Current Schedule -->
    <div class="card">
        <div class="card-header"><i class="fas fa-calendar-week" style="margin-right: 0.5rem; color: #8B5CF6;"></i> My Weekly Schedule</div>
        <div class="card-body" style="padding: 0;">
            <?php
            $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            $todayDow = (int) date('w');
            ?>
            <?php for ($d = 0; $d <= 6; $d++): ?>
                <div style="padding: 0.6rem 1rem; border-bottom: 1px solid #F3F4F6; <?= $d === $todayDow ? 'background: #F5F3FF;' : '' ?>">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.85rem; font-weight: <?= $d === $todayDow ? '700' : '500' ?>; color: <?= $d === $todayDow ? '#7C3AED' : '#374151' ?>;">
                            <?= $dayNames[$d] ?>
                            <?php if ($d === $todayDow): ?>
                                <span style="font-size: 0.6rem; padding: 0.1rem 0.35rem; background: #8B5CF6; color: white; border-radius: 999px; margin-left: 0.25rem;">TODAY</span>
                            <?php endif; ?>
                        </span>
                    </div>

                    <?php if (isset($schedule[$d]) && ! empty($schedule[$d]['slots'])): ?>
                        <div style="margin-top: 0.3rem; display: flex; flex-wrap: wrap; gap: 0.25rem;">
                            <?php foreach ($schedule[$d]['slots'] as $slot): ?>
                                <div style="display: flex; align-items: center; gap: 0.3rem; padding: 0.2rem 0.5rem; background: #ECFDF5; border-radius: 0.375rem; font-size: 0.7rem;">
                                    <span style="font-weight: 500; color: #059669;">
                                        <?= date('h:i A', strtotime($slot['start_time'])) ?> – <?= date('h:i A', strtotime($slot['end_time'])) ?>
                                    </span>
                                    <span style="color: #6B7280;">(<?= $slot['max_slots'] ?> slot<?= $slot['max_slots'] > 1 ? 's' : '' ?>)</span>
                                    <form method="POST" action="/counselling/availability/remove/<?= $slot['id'] ?>" style="display: inline;"><?= csrf_field() ?>
                                        <button type="button"
                                                data-synapse-confirm
                                                data-synapse-confirm-danger
                                                data-synapse-confirm-title="Remove this time slot?"
                                                data-synapse-confirm-body="Any existing appointments booked into this slot will need to be reassigned. This action cannot be undone."
                                                data-synapse-confirm-text="Remove Slot"
                                                aria-label="Remove slot at <?= date('h:i A', strtotime($slot['start_time'])) ?>"
                                                style="background: none; border: none; color: #DC2626; cursor: pointer; font-size: 0.6rem; padding: 0;" title="Remove">×</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="font-size: 0.7rem; color: #9CA3AF; margin-top: 0.15rem;">No slots configured</p>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Add Slot Form -->
    <div class="card">
        <div class="card-header"><i class="fas fa-plus-circle" style="margin-right: 0.5rem; color: #10B981;"></i> Add Time Slot</div>
        <div class="card-body">
            <?php $errors = session()->get('errors') ?? []; ?>

            <form method="POST" action="/counselling/availability/add" novalidate
                  data-synapse-form-dialog
                  data-dialog-title="Add Time Slot"
                  data-dialog-icon="fas fa-plus-circle"
                  data-dialog-submit-label="Add Slot"
                  data-dialog-cancel-label="Cancel">
                <?= csrf_field() ?>

                <?php if (! empty($errors)): ?>
                    <div role="alert" id="avail-errors" class="syn-alert syn-alert--danger" style="margin-bottom: 1.25rem;">
                        <i class="fas fa-triangle-exclamation"></i>
                        <div>
                            <strong>Please fix the following before saving:</strong>
                            <ul style="margin: 0.5rem 0 0; padding-left: 1.25rem;">
                                <?php foreach ($errors as $field => $msg): ?>
                                    <li><?= esc($msg) ?></li>
                                <?php endforeach ?>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>

                <div style="margin-bottom: 1.25rem;">
                    <label for="day_of_week" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Day of Week *</label>
                    <select id="day_of_week" name="day_of_week" required aria-required="true" data-synapse-dropdown>
                        <?php foreach ($dayNames as $i => $name): ?>
                            <option value="<?= $i ?>" <?= $i === 1 ? 'selected' : '' ?>><?= $name ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.25rem;">
                    <div>
                        <label for="start_time" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Start Time *</label>
                        <input id="start_time" type="text" class="syn-datepicker syn-datepicker--time-only" name="start_time" required aria-required="true" value="09:00" placeholder="HH:MM" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                    </div>
                    <div>
                        <label for="end_time" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">End Time *</label>
                        <input id="end_time" type="text" class="syn-datepicker syn-datepicker--time-only" name="end_time" required aria-required="true" value="10:00" placeholder="HH:MM" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                    </div>
                </div>

                <div style="margin-bottom: 1.25rem;">
                    <label for="max_slots" style="display: block; font-size: 0.8rem; font-weight: 500; color: #374151; margin-bottom: 0.3rem;">Max Concurrent Slots</label>
                    <input id="max_slots" type="number" name="max_slots" value="1" min="1" max="5" style="width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #E5E7EB; border-radius: 0.375rem; font-size: 0.85rem; font-family: 'Inter', sans-serif;">
                    <p style="font-size: 0.7rem; color: #9CA3AF; margin-top: 0.2rem;">How many appointments can be booked in this time slot simultaneously.</p>
                </div>

                <div style="margin-bottom: 1rem; padding: 0.6rem; background: #F5F3FF; border: 1px solid #DDD6FE; border-radius: 0.375rem;">
                    <p style="font-size: 0.7rem; color: #5B21B6;"><i class="fas fa-info-circle"></i> System enforces a minimum <strong>15-minute buffer</strong> between consecutive appointments.</p>
                </div>

                <button type="submit" style="width: 100%; padding: 0.6rem; background: #10B981; color: white; border: none; border-radius: 0.5rem; font-family: 'Inter', sans-serif; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                    <i class="fas fa-plus"></i> Add Slot
                </button>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
