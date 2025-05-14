<?php

namespace App\Controllers;

use App\Models\UserModel;

class WalletController extends BaseController
{
    protected $userModel;
    protected $session;
    protected $currentUserId;
    protected $currentUserName;

    public function __construct()
    {
        $this->session = \Config\Services::session();
        helper(['form', 'url', 'number']);

        $this->currentUserId = $this->session->get('user_id');
        $this->currentUserName = $this->session->get('name');

        if ($this->currentUserId) {
            $this->userModel = new UserModel();
        } else {
            log_message('warning', 'WalletController constructor: currentUserId is null. User might not be logged in or session expired.');
        }
    }

    private function _ensureModelsAndUser()
    {
        if (!$this->currentUserId || !$this->userModel) {
            log_message('critical', 'WalletController: Attempted action without valid user session or user model initialized. currentUserId: ' . ($this->currentUserId ?? 'null'));
            $this->session->destroy();
            if (!headers_sent()) {
                 header('Location: ' . site_url('/auth/login?error=session_error_wc'));
            }
            exit();
        }
        return true;
    }

    public function dashboard()
    {
        $this->_ensureModelsAndUser();

        $user = $this->userModel->find($this->currentUserId);

        if (!$user) {
            log_message('error', 'Dashboard: User not found in DB with ID ' . $this->currentUserId . '. Forcing logout.');
            $this->session->destroy();
            return redirect()->to('/auth/login')->with('error', 'Seu usuário não foi encontrado. Por favor, faça login novamente.');
        }

        $transactions = [];

        return view('wallet/dashboard', [
            'user' => $user,
            'transactions' => $transactions,
            'userName' => $this->currentUserName
        ]);
    }
}