<?php

namespace App\Controllers\Clinic;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\UserModel;
use App\Models\EmergencyContactModel;
use App\Models\AllergyModel;
use App\Models\ConsultationModel;
use App\Models\AuditLogModel;

class StudentController extends BaseController
{
    protected StudentModel $studentModel;
    protected UserModel $userModel;

    public function __construct()
    {
        $this->studentModel = new StudentModel();
        $this->userModel    = new UserModel();
    }

    /**
     * Student list (searchable, paginated).
     */
    public function index()
    {
        $search = $this->request->getGet('q');
        /* Per-page selector: 10 / 25 / 50, default 15. Clamped to keep
           queries cheap even if a user sends a malicious query string. */
        $perPageRaw = (int) ($this->request->getGet('per_page') ?? 15);
        $perPage    = max(10, min(100, $perPageRaw ?: 15));

        if ($search) {
            $students = $this->studentModel->search($search);
            $pager    = null;
        } else {
            $students = $this->studentModel->getStudentList($perPage);
            $pager    = $this->studentModel->pager;
        }

        return view('clinic/students/index', [
            'title'    => 'Students — SYNAPSE',
            'heading'  => 'Student Records',
            'students' => $students,
            'pager'    => $pager,
            'search'   => $search,
            'perPage'  => $perPage,
        ]);
    }

    /**
     * Student profile view.
     */
    public function show(int $id)
    {
        $student = $this->studentModel->getWithProfile($id);

        if ($student === null) {
            return redirect()->to('/clinic/students')->with('error', 'Student not found.');
        }

        // Get recent consultations
        $consultModel = new ConsultationModel();
        $student['recent_consultations'] = $consultModel->getByStudent($id, 5);

        return view('clinic/students/show', [
            'title'   => "{$student['full_name']} — SYNAPSE",
            'heading' => 'Student Profile',
            'student' => $student,
        ]);
    }

    /**
     * Create student form.
     */
    public function create()
    {
        return view('clinic/students/form', [
            'title'   => 'Register Student — SYNAPSE',
            'heading' => 'Register New Student',
            'student' => null,
            'mode'    => 'create',
        ]);
    }

    /**
     * Store new student.
     */
    public function store()
    {
        $rules = [
            'first_name'     => 'required|max_length[100]',
            'last_name'      => 'required|max_length[100]',
            'email'          => 'required|valid_email|is_unique[users.email]',
            'student_number' => 'required|max_length[50]|is_unique[students.student_number]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $db = \Config\Database::connect();
        $db->transStart();

        // 1. Create user account
        $userId = $this->userModel->insert([
            'email'      => $this->request->getPost('email'),
            'password'   => 'Student@' . date('Y'), // Default password
            'first_name' => $this->request->getPost('first_name'),
            'last_name'  => $this->request->getPost('last_name'),
            'middle_name'=> $this->request->getPost('middle_name'),
            'phone'      => $this->request->getPost('phone'),
            'is_active'  => true,
        ]);

        // 2. Assign student role
        $roleModel = new \App\Models\RoleModel();
        $studentRole = $roleModel->findByName('student');
        if ($studentRole) {
            $userRoleModel = new \App\Models\UserRoleModel();
            $userRoleModel->assignRole($userId, (int) $studentRole['id']);
        }

        // 3. Create student profile
        $studentId = $this->studentModel->insert([
            'user_id'        => $userId,
            'student_number' => $this->request->getPost('student_number'),
            'qr_code'        => $this->request->getPost('qr_code') ?: null,
            'rfid_tag'       => $this->request->getPost('rfid_tag') ?: null,
            'course'         => $this->request->getPost('course'),
            'year_level'     => $this->request->getPost('year_level') ?: null,
            'section'        => $this->request->getPost('section'),
            'date_of_birth'  => $this->request->getPost('date_of_birth') ?: null,
            'gender'         => $this->request->getPost('gender') ?: null,
            'address'        => $this->request->getPost('address'),
            'blood_type'     => $this->request->getPost('blood_type'),
        ]);

        // 4. Add emergency contact if provided
        $contactName = $this->request->getPost('contact_name');
        if ($contactName) {
            $ecModel = new EmergencyContactModel();
            $ecModel->insert([
                'student_id'   => $studentId,
                'contact_name' => $contactName,
                'relationship' => $this->request->getPost('contact_relationship'),
                'phone'        => $this->request->getPost('contact_phone'),
                'is_primary'   => true,
            ]);
        }

        // 5. Add allergy if provided
        $allergen = $this->request->getPost('allergen');
        if ($allergen) {
            $allergyModel = new AllergyModel();
            $allergyModel->insert([
                'student_id' => $studentId,
                'allergen'   => $allergen,
                'severity'   => $this->request->getPost('allergy_severity') ?: 'mild',
                'reaction'   => $this->request->getPost('allergy_reaction'),
            ]);
        }

        // 6. Audit log
        $auditModel = new AuditLogModel();
        $auditModel->logAction(session()->get('user_id'), 'create', 'clinic', 'students', $studentId);

        $db->transComplete();

        if ($db->transStatus()) {
            return redirect()->to("/clinic/students/{$studentId}")->with('success', 'Student registered successfully.');
        }

        return redirect()->back()->withInput()->with('error', 'Failed to register student.');
    }

    /**
     * Edit student form.
     */
    public function edit(int $id)
    {
        $student = $this->studentModel->getWithProfile($id);

        if ($student === null) {
            return redirect()->to('/clinic/students')->with('error', 'Student not found.');
        }

        return view('clinic/students/form', [
            'title'   => "Edit {$student['full_name']} — SYNAPSE",
            'heading' => 'Edit Student',
            'student' => $student,
            'mode'    => 'edit',
        ]);
    }

    /**
     * Update student.
     */
    public function update(int $id)
    {
        $student = $this->studentModel->find($id);
        if ($student === null) {
            return redirect()->to('/clinic/students')->with('error', 'Student not found.');
        }

        $this->studentModel->update($id, [
            'course'        => $this->request->getPost('course'),
            'year_level'    => $this->request->getPost('year_level') ?: null,
            'section'       => $this->request->getPost('section'),
            'date_of_birth' => $this->request->getPost('date_of_birth') ?: null,
            'gender'        => $this->request->getPost('gender') ?: null,
            'address'       => $this->request->getPost('address'),
            'blood_type'    => $this->request->getPost('blood_type'),
            'qr_code'       => $this->request->getPost('qr_code') ?: null,
            'rfid_tag'      => $this->request->getPost('rfid_tag') ?: null,
        ]);

        // Update user info
        $this->userModel->update($student['user_id'], [
            'first_name'  => $this->request->getPost('first_name'),
            'last_name'   => $this->request->getPost('last_name'),
            'middle_name' => $this->request->getPost('middle_name'),
            'phone'       => $this->request->getPost('phone'),
        ]);

        $auditModel = new AuditLogModel();
        $auditModel->logAction(session()->get('user_id'), 'update', 'clinic', 'students', $id);

        return redirect()->to("/clinic/students/{$id}")->with('success', 'Student updated successfully.');
    }

    /**
     * AJAX: Search students for check-in.
     */
    public function search()
    {
        $query  = $this->request->getGet('q');
        $method = $this->request->getGet('method') ?? 'manual';

        if (empty($query)) {
            return $this->response->setJSON(['results' => []]);
        }

        if ($method === 'qr' || $method === 'rfid') {
            $student = $this->studentModel->checkInLookup($query, $method);
            return $this->response->setJSON([
                'results' => $student ? [$student] : [],
                'found'   => $student !== null,
            ]);
        }

        $results = $this->studentModel->search($query, 10);
        return $this->response->setJSON(['results' => $results]);
    }
}
