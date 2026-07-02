<?php

namespace App\Models;

use CodeIgniter\Model;

class ConsultationModel extends Model
{
    protected $table            = 'consultations';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'student_id', 'attending_user_id', 'chief_complaint',
        'diagnosis', 'notes', 'status', 'check_in_method',
        'triage_priority', 'triage_override', 'consultation_date',
        'queue_position', 'called_at', 'called_by_user_id',
        'started_at', 'completed_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'student_id'        => 'required|is_natural_no_zero',
        'attending_user_id' => 'required|is_natural_no_zero',
        'chief_complaint'   => 'required|min_length[3]',
        'consultation_date' => 'required|valid_date',
    ];

    /**
     * Return today's date as a SQL fragment, evaluated on the MySQL
     * server (CURDATE()). PHP's date() can disagree with MySQL's when
     * the web and DB servers are in different timezones — and since
     * consultation_date is written by MySQL (NOW()), we MUST use the
     * server's "today" so the WHERE clause matches.
     */
    private function todayExpr(): string
    {
        return 'CURDATE()';
    }

    /**
     * Get today's consultation queue with student + staff info.
     */
    public function getTodayQueue(): array
    {
        $today = $this->todayExpr();

        return $this->select('consultations.*, students.student_number, users_student.first_name as student_first, users_student.last_name as student_last, users_staff.first_name as staff_first, users_staff.last_name as staff_last')
            ->join('students', 'students.id = consultations.student_id')
            ->join('users as users_student', 'users_student.id = students.user_id')
            ->join('users as users_staff', 'users_staff.id = consultations.attending_user_id')
            ->where('DATE(consultations.consultation_date) = ' . $today, null, false)
            ->orderBy('consultations.consultation_date', 'ASC')
            ->findAll();
    }

    /**
     * Get consultations for a specific student.
     */
    public function getByStudent(int $studentId, int $limit = 10): array
    {
        return $this->select('consultations.*, users.first_name as staff_first, users.last_name as staff_last')
            ->join('users', 'users.id = consultations.attending_user_id')
            ->where('student_id', $studentId)
            ->orderBy('consultation_date', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get a consultation with all related data (vitals, treatments, referrals).
     */
    public function getFullConsultation(int $id): ?array
    {
        $consult = $this->select('consultations.*, students.student_number, students.blood_type, users_student.first_name as student_first, users_student.last_name as student_last, users_staff.first_name as staff_first, users_staff.last_name as staff_last')
            ->join('students', 'students.id = consultations.student_id')
            ->join('users as users_student', 'users_student.id = students.user_id')
            ->join('users as users_staff', 'users_staff.id = consultations.attending_user_id')
            ->find($id);

        if ($consult === null) {
            return null;
        }

        $consult['vitals']     = (new ConsultationVitalsModel())->getByConsultation($id);
        $consult['treatments'] = (new TreatmentModel())->getByConsultation($id);
        $consult['allergies']  = (new AllergyModel())->getByStudent((int) $consult['student_id']);

        // Get referrals linked to this consultation
        $referralModel = new ReferralModel();
        $consult['referrals'] = $referralModel
            ->where('source_consultation_id', $id)
            ->findAll();

        return $consult;
    }

    /**
     * Get recent consultations (last N days).
     */
    public function getRecent(int $days = 7, int $limit = 50): array
    {
        $since = date('Y-m-d', strtotime("-{$days} days"));

        return $this->select('consultations.*, students.student_number, users_student.first_name as student_first, users_student.last_name as student_last')
            ->join('students', 'students.id = consultations.student_id')
            ->join('users as users_student', 'users_student.id = students.user_id')
            ->where('DATE(consultation_date) >=', $since)
            ->orderBy('consultation_date', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Count today's consultations by status.
     *
     * Uses MySQL's CURDATE() so the date matches the consultation_date
     * stamps, which are written by MySQL (NOW()). PHP's date() can be a
     * day off when the web server timezone differs from MySQL's.
     */
    public function getTodayStats(): array
    {
        $db = \Config\Database::connect();

        $rows = $db->table('consultations')
            ->select('status, COUNT(*) AS n')
            ->where('DATE(consultation_date) = CURDATE()', null, false)
            ->groupBy('status')
            ->get()
            ->getResultArray();

        $counts = ['in_progress' => 0, 'called' => 0, 'in_session' => 0, 'completed' => 0, 'follow_up' => 0];
        $total = 0;
        foreach ($rows as $r) {
            $counts[$r['status']] = (int) $r['n'];
            $total += (int) $r['n'];
        }

        return [
            'total'       => $total,
            'completed'   => $counts['completed'],
            'in_progress' => $counts['in_progress'],
            /* "waiting" = patients still in line, not yet called. Used by
               the staff queue page header card. */
            'waiting'     => $counts['in_progress'],
            'called'      => $counts['called'],
            'in_session'  => $counts['in_session'],
            'follow_up'   => $counts['follow_up'],
        ];
    }

    /**
     * Today's full queue, ordered by queue_position (lowest first).
     * Filters out completed/follow_up so the lobby display only
     * shows patients who still need to be seen.
     *
     * @return array
     */
    public function getTodayActiveQueue(): array
    {
        $today = $this->todayExpr();
        return $this->select('consultations.*, students.student_number, students.blood_type,
                              users_student.first_name AS student_first,
                              users_student.last_name AS student_last,
                              users_staff.first_name AS staff_first,
                              users_staff.last_name AS staff_last,
                              users_called.first_name AS called_first,
                              users_called.last_name AS called_last')
            ->join('students', 'students.id = consultations.student_id')
            ->join('users AS users_student', 'users_student.id = students.user_id')
            ->join('users AS users_staff', 'users_staff.id = consultations.attending_user_id')
            ->join('users AS users_called', 'users_called.id = consultations.called_by_user_id', 'left')
            ->where('DATE(consultations.consultation_date) = ' . $today, null, false)
            ->whereIn('consultations.status', ['in_progress', 'called', 'in_session'])
            ->orderBy('CASE WHEN consultations.status = "called" THEN 0
                              WHEN consultations.status = "in_session" THEN 1
                              ELSE 2 END', '', false)
            ->orderBy('consultations.queue_position', 'ASC')
            ->orderBy('consultations.consultation_date', 'ASC')
            ->findAll();
    }

    /**
     * Today's queue with the next-up patient first. The "now serving"
     * is the patient currently in `in_session`. Then `called`. Then
     * the `in_progress` queue in queue_position order.
     */
    public function getTodayQueueForDisplay(): array
    {
        $today = $this->todayExpr();
        return $this->select('consultations.*, students.student_number,
                              users_student.first_name AS student_first,
                              users_student.last_name AS student_last')
            ->join('students', 'students.id = consultations.student_id')
            ->join('users AS users_student', 'users_student.id = students.user_id')
            ->where('DATE(consultations.consultation_date) = ' . $today, null, false)
            ->whereIn('consultations.status', ['in_progress', 'called', 'in_session'])
            ->orderBy('consultations.queue_position', 'ASC')
            ->orderBy('consultations.consultation_date', 'ASC')
            ->findAll();
    }

    /**
     * Find the next patient to call. Picks by priority (urgent > high
     * > medium > low), then by queue_position. Only patients still in
     * `in_progress` (not already called) are eligible.
     *
     * @return array|null
     */
    public function findNextToCall(): ?array
    {
        $today = $this->todayExpr();
        return $this->select('consultations.*, students.student_number,
                              users_student.first_name AS student_first,
                              users_student.last_name AS student_last')
            ->join('students', 'students.id = consultations.student_id')
            ->join('users AS users_student', 'users_student.id = students.user_id')
            ->where('DATE(consultations.consultation_date) = ' . $today, null, false)
            ->where('consultations.status', 'in_progress')
            ->orderBy("CASE consultations.triage_priority
                          WHEN 'urgent' THEN 0
                          WHEN 'high'   THEN 1
                          WHEN 'medium' THEN 2
                          WHEN 'low'    THEN 3
                          ELSE 4 END", '', false)
            ->orderBy('consultations.queue_position', 'ASC')
            ->orderBy('consultations.consultation_date', 'ASC')
            ->first();
    }

    /**
     * The patient currently being served (status = in_session). At
     * most one per day; returns null if nobody is in the room.
     */
    public function findCurrentlyServing(): ?array
    {
        $today = $this->todayExpr();
        return $this->select('consultations.*, students.student_number,
                              users_student.first_name AS student_first,
                              users_student.last_name AS student_last')
            ->join('students', 'students.id = consultations.student_id')
            ->join('users AS users_student', 'users_student.id = students.user_id')
            ->where('DATE(consultations.consultation_date) = ' . $today, null, false)
            ->where('consultations.status', 'in_session')
            ->first();
    }

    /**
     * Recompute queue_position for every row with status
     * `in_progress` today. Called after a new walk-in, a skip, or a
     * delete so positions stay contiguous. Positions are assigned in
     * check-in order (consultation_date ASC) — the order the patient
     * physically arrived in the lobby.
     */
    public function recalculateQueuePositions(): int
    {
        $today = $this->todayExpr();
        $db = \Config\Database::connect();

        $rows = $db->table('consultations')
            ->select('id')
            ->where('DATE(consultation_date) = ' . $today, null, false)
            ->whereIn('status', ['in_progress', 'called', 'in_session'])
            ->orderBy('consultation_date', 'ASC')
            ->get()->getResultArray();

        $updated = 0;
        $pos = 1;
        foreach ($rows as $row) {
            $db->table('consultations')
                ->where('id', $row['id'])
                ->update(['queue_position' => $pos]);
            $pos++;
            $updated++;
        }
        return $updated;
    }

    /**
     * Staff pressed "Call Next" — pick the highest-priority waiting
     * patient, mark them as `called`, stamp the timestamp. Returns the
     * row that was called (or null if nobody is waiting).
     */
    public function callNext(int $staffUserId): ?array
    {
        $next = $this->findNextToCall();
        if (! $next) {
            return null;
        }
        $this->update($next['id'], [
            'status'             => 'called',
            'called_at'          => date('Y-m-d H:i:s'),
            'called_by_user_id'  => $staffUserId,
        ]);
        /* Repack so any newly-empty slots in the in_progress list
           (since the high-priority patients are now called/in_session)
           close up. Done here instead of on insert because the
           position of the *waiting* list changes when someone leaves
           the queue. */
        $this->recalculateQueuePositions();
        // Re-read so the caller gets the new timestamps + joins.
        return $this->findWithJoins($next['id']);
    }

    /**
     * Staff pressed "Start" on a called patient. Moves them into
     * `in_session`, closes out any other patient currently in
     * session (they were missed). Also recomputes positions so the
     * remaining queue stays tight.
     */
    public function startSession(int $consultId): ?array
    {
        $consult = $this->find($consultId);
        if (! $consult) {
            return null;
        }
        // Demote any other in_session row — only one patient in the room at a time.
        $today = $this->todayExpr();
        $this->where('DATE(consultation_date) = ' . $today, null, false)
            ->where('status', 'in_session')
            ->where('id !=', $consultId)
            ->set(['status' => 'in_progress'])
            ->update();

        $this->update($consultId, [
            'status'      => 'in_session',
            'started_at'  => date('Y-m-d H:i:s'),
        ]);
        $this->recalculateQueuePositions();
        return $this->findWithJoins($consultId);
    }

    /**
     * Staff pressed "Skip" — patient didn't show up. Mark as
     * completed (no diagnosis saved) and re-pack the queue.
     */
    public function skip(int $consultId, int $staffUserId): ?array
    {
        $consult = $this->find($consultId);
        if (! $consult) {
            return null;
        }
        $this->update($consultId, [
            'status'        => 'completed',
            'completed_at'  => date('Y-m-d H:i:s'),
            'notes'         => '[Skipped — no show] ' . ($consult['notes'] ?? ''),
        ]);
        $this->recalculateQueuePositions();
        return $this->findWithJoins($consultId);
    }

    /**
     * Wrapper around complete() that also stamps completed_at. The
     * older complete() method on the controller calls update() directly
     * — keep this richer version for new endpoints.
     */
    public function finishConsultation(int $consultId, string $finalStatus = 'completed'): ?array
    {
        $this->update($consultId, [
            'status'        => $finalStatus,
            'completed_at'  => date('Y-m-d H:i:s'),
        ]);
        $this->recalculateQueuePositions();
        return $this->find($consultId);
    }
    /**
     * Fetch a single consultation by id with the joined columns the
     * queue UI needs (student_number, first/last names, etc.). Model's
     * plain find() only returns the consultations.* columns.
     */
    public function findWithJoins(int $id): ?array
    {
        return $this->select('consultations.*, students.student_number,
                              users_student.first_name AS student_first,
                              users_student.last_name AS student_last,
                              users_staff.first_name AS staff_first,
                              users_staff.last_name AS staff_last,
                              users_called.first_name AS called_first,
                              users_called.last_name AS called_last')
            ->join('students', 'students.id = consultations.student_id')
            ->join('users AS users_student', 'users_student.id = students.user_id')
            ->join('users AS users_staff', 'users_staff.id = consultations.attending_user_id')
            ->join('users AS users_called', 'users_called.id = consultations.called_by_user_id', 'left')
            ->where('consultations.id', $id)
            ->first();
    }}
