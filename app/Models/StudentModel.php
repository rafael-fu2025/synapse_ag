<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentModel extends Model
{
    protected $table            = 'students';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'user_id', 'student_number', 'qr_code', 'rfid_tag',
        'course', 'year_level', 'section', 'date_of_birth',
        'gender', 'address', 'blood_type', 'consecutive_no_shows',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'user_id'        => 'required|is_natural_no_zero|is_unique[students.user_id,id,{id}]',
        'student_number' => 'required|max_length[50]|is_unique[students.student_number,id,{id}]',
    ];

    /**
     * Find student by student number.
     */
    public function findByStudentNumber(string $number): ?array
    {
        return $this->where('student_number', $number)->first();
    }

    /**
     * Find student by QR code.
     */
    public function findByQR(string $qrCode): ?array
    {
        return $this->where('qr_code', $qrCode)->first();
    }

    /**
     * Find student by RFID tag.
     */
    public function findByRFID(string $rfidTag): ?array
    {
        return $this->where('rfid_tag', $rfidTag)->first();
    }

    /**
     * Get full student profile with user info, allergies, emergency contacts.
     */
    public function getWithProfile(int $studentId): ?array
    {
        $student = $this->select('students.*, users.email, users.first_name, users.last_name, users.middle_name, users.phone, users.avatar_url')
            ->join('users', 'users.id = students.user_id')
            ->find($studentId);

        if ($student === null) {
            return null;
        }

        $student['full_name'] = trim($student['first_name'] . ' ' . ($student['middle_name'] ? $student['middle_name'] . ' ' : '') . $student['last_name']);
        $student['allergies'] = (new AllergyModel())->getByStudent($studentId);
        $student['emergency_contacts'] = (new EmergencyContactModel())->getByStudent($studentId);

        return $student;
    }

    /**
     * Search students by name, student number, or email.
     */
    public function search(string $query, int $limit = 20): array
    {
        // Escape LIKE wildcards in user input so a literal `_` and `%` match
        // themselves rather than acting as SQL wildcards. The query builder's
        // 5th `escape` argument is set to `true` so it also escapes the
        // escape character (backslash) on its end. See escape_like_helper.
        $q = escape_like($query);

        return $this->select('students.*, users.first_name, users.last_name, users.email')
            ->join('users', 'users.id = students.user_id')
            ->groupStart()
                ->like('students.student_number', $q)
                ->orLike('users.first_name', $q)
                ->orLike('users.last_name', $q)
                ->orLike('users.email', $q)
            ->groupEnd()
            ->limit($limit)
            ->findAll();
    }

    /**
     * Lookup student for check-in (QR, RFID, or student number).
     */
    public function checkInLookup(string $value, string $method = 'manual'): ?array
    {
        $student = match ($method) {
            'qr'     => $this->findByQR($value),
            'rfid'   => $this->findByRFID($value),
            default  => $this->findByStudentNumber($value),
        };

        if ($student === null) {
            return null;
        }

        return $this->getWithProfile((int) $student['id']);
    }

    /**
     * Get paginated student list with user info.
     */
    public function getStudentList(int $perPage = 20)
    {
        return $this->select('students.*, users.first_name, users.last_name, users.email')
            ->join('users', 'users.id = students.user_id')
            ->orderBy('users.last_name', 'ASC')
            ->paginate($perPage);
    }
}
