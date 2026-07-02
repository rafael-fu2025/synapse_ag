<?php

namespace App\Controllers;

use App\Models\UserModel;

class ProfileController extends BaseController
{
    public function index()
    {
        $userModel = new UserModel();
        $user = $userModel->find(session()->get('user_id'));

        return view('profile', [
            'title' => 'My Profile — SYNAPSE',
            'heading' => 'Account Settings',
            'user' => $user
        ]);
    }

    public function update()
    {
        $userId = session()->get('user_id');
        $userModel = new UserModel();

        $rules = [
            'first_name' => 'required|min_length[2]|max_length[50]',
            'last_name'  => 'required|min_length[2]|max_length[50]',
        ];

        // Check if password change is requested
        $password = $this->request->getPost('password');
        if (!empty($password)) {
            $rules['password'] = 'min_length[8]';
            $rules['password_confirm'] = 'matches[password]';
        }

        if (!$this->validate($rules)) {
            return $this->validationFailure($this->validator->getErrors());
        }

        $data = [
            'first_name' => $this->request->getPost('first_name'),
            'last_name'  => $this->request->getPost('last_name'),
        ];

        if (!empty($password)) {
            // Delegate hashing to the model so the bcrypt cost/round logic
            // lives in one place (UserModel::setPassword / hashPassword).
            try {
                $userModel->setPassword($userId, $password);
            } catch (\InvalidArgumentException $e) {
                return $this->errorResponse($e->getMessage());
            }
        }

        $userModel->update($userId, $data);

        // Update session info
        session()->set([
            'first_name' => $data['first_name'],
            'last_name'  => $data['last_name'],
            'full_name'  => $data['first_name'] . ' ' . $data['last_name']
        ]);

        return $this->successResponse('Profile updated successfully.');
    }

    /**
     * Return a JSON success envelope for AJAX (XHR) requests, or a
     * standard redirect with flash for native form submits. This lets
     * the same controller back BOTH the standalone `/profile` page
     * AND dialog submissions that stay in-page.
     */
    private function successResponse(string $message)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status'    => 'success',
                'message'   => $message,
                'title'     => 'Profile',
                'csrf_hash' => csrf_hash(),
            ]);
        }
        return redirect()->to('/profile')->with('success', $message);
    }

    private function errorResponse(string $message)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'    => 'error',
                'message'   => $message,
                'csrf_hash' => csrf_hash(),
            ]);
        }
        return redirect()->back()->withInput()->with('error', $message);
    }

    private function validationFailure(array $errors)
    {
        if ($this->request->isAJAX()) {
            $flat = [];
            foreach ($errors as $field => $msg) {
                $flat[] = is_array($msg) ? implode(' · ', $msg) : $msg;
            }
            return $this->response->setStatusCode(422)->setJSON([
                'status'    => 'error',
                'message'   => implode(' · ', $flat),
                'errors'    => $errors,
                'csrf_hash' => csrf_hash(),
            ]);
        }
        return redirect()->back()->withInput()->with('errors', $errors);
    }
}
