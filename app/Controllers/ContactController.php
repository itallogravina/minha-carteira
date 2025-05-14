<?php

namespace App\Controllers;

use App\Models\ContactModel;
use App\Models\UserModel;

class ContactController extends BaseController
{
    protected $session;
    protected $contactModel;
    protected $userModel;
    protected $currentUserId;

    public function __construct()
    {
        $this->session = \Config\Services::session();
        helper(['form', 'url']);

        $this->currentUserId = $this->session->get('user_id');

        if ($this->currentUserId) {
            $this->contactModel = new ContactModel();
            $this->userModel = new UserModel();
        } else {
            if ($this->session->get('is_logged_in')) {
                log_message('error', 'ContactController constructor: User is_logged_in but currentUserId is null. Session: ' . json_encode($this->session->get()));
            }
        }
    }

    private function _ensureLoggedInAndModels() {
        if (!$this->currentUserId) {
            log_message('warning', 'ContactController: Action attempted without currentUserId. Redirecting to login.');
            log_message('critical', 'ContactController: _ensureLoggedInAndModels failed - currentUserId is missing. Filter AuthGuard might not be working as expected.');
             if (!$this->contactModel) $this->contactModel = new ContactModel();
             if (!$this->userModel) $this->userModel = new UserModel();
        }

        if ( ! $this->contactModel || ! $this->userModel) {
             log_message('critical', 'ContactController: Models not instantiated. currentUserId: '. ($this->currentUserId ?? 'null'));
             if (!$this->contactModel && $this->currentUserId) $this->contactModel = new ContactModel();
             if (!$this->userModel && $this->currentUserId) $this->userModel = new UserModel();

             if (!$this->contactModel || !$this->userModel) {
                $this->session->setFlashdata('error', 'Erro interno ao carregar dados de contatos. Tente novamente.');
                log_message('error', 'ContactController: CRITICAL - Could not instantiate models in _ensureLoggedInAndModels.');
             }
        }
    }

    public function index()
    {
        $this->_ensureLoggedInAndModels();
        log_message('debug', 'ContactController: index() method CALLED by user ID ' . ($this->currentUserId ?? 'UNKNOWN'));

        if (!$this->currentUserId) {
            return redirect()->to('/auth/login')->with('error', 'Por favor, faça login para ver seus contatos.');
        }

        $data = [
            'contacts'   => $this->contactModel->getUserContacts($this->currentUserId),
            'validation' => \Config\Services::validation(),
            'userName'   => $this->session->get('user_name')
        ];

        return view('contacts/index', $data);
    }

    public function add()
    {
        $this->_ensureLoggedInAndModels();
        log_message('debug', 'ContactController: add() method CALLED (POST) by user ID ' . ($this->currentUserId ?? 'UNKNOWN'));

        if (!$this->currentUserId) {
            return redirect()->to('/auth/login')->with('error', 'Sessão inválida para adicionar contato.');
        }

        if ($this->request->getMethod() === 'POST') {
            $rules = [
                'contact_email' => 'required|valid_email'
            ];

            if (!$this->validate($rules)) {
                $this->session->setFlashdata('error_contact_add', $this->validator->listErrors());
                return redirect()->to('/contacts')->withInput();
            }

            $contactEmail = trim($this->request->getPost('contact_email'));
            $currentUserEmail = $this->session->get('user_email');

            if (strtolower($contactEmail) === strtolower($currentUserEmail)) {
                $this->session->setFlashdata('error_contact_add', 'Você não pode adicionar seu próprio email como contato.');
                return redirect()->to('/contacts')->withInput();
            }

            $contactUser = $this->userModel->where('email', $contactEmail)->first();

            if (!$contactUser) {
                $this->session->setFlashdata('error_contact_add', 'Nenhum usuário encontrado com o email: ' . esc($contactEmail));
                return redirect()->to('/contacts')->withInput();
            }

            if ($this->contactModel->contactExists($this->currentUserId, $contactUser->id)) {
                $this->session->setFlashdata('info_contact_add', 'Este usuário já está na sua lista de contatos.');
                return redirect()->to('/contacts');
            }

            $contactData = [
                'user_id'         => $this->currentUserId,
                'contact_user_id' => $contactUser->id
            ];

            if ($this->contactModel->insert($contactData)) {
                $this->session->setFlashdata('success_contact_add', 'Contato "' . esc($contactUser->name ?: $contactUser->email) . '" adicionado com sucesso!');
            } else {
                $this->session->setFlashdata('error_contact_add', 'Erro ao adicionar o contato. Tente novamente.');
                log_message('error', 'Failed to add contact. UserID: ' . $this->currentUserId . ', ContactUserID: ' . $contactUser->id . '. Errors: ' . json_encode($this->contactModel->errors()));
            }
            return redirect()->to('/contacts');
        }

        return redirect()->to('/contacts');
    }

    public function remove($contactRelationId = null)
    {
        $this->_ensureLoggedInAndModels();
        log_message('debug', 'ContactController: remove() method CALLED for relation ID ' . ($contactRelationId ?? 'NULL') . ' by user ID ' . ($this->currentUserId ?? 'UNKNOWN'));


        if (!$this->currentUserId) {
            return redirect()->to('/auth/login')->with('error', 'Sessão inválida para remover contato.');
        }

        if ($contactRelationId === null || !is_numeric($contactRelationId)) {
            $this->session->setFlashdata('error_contact_remove', 'ID do contato inválido ou não fornecido.');
            return redirect()->to('/contacts');
        }

        $contactRelation = $this->contactModel->where('id', (int)$contactRelationId)
                                             ->where('user_id', $this->currentUserId)
                                             ->first();

        if (!$contactRelation) {
            $this->session->setFlashdata('error_contact_remove', 'Contato não encontrado ou você não tem permissão para removê-lo.');
            return redirect()->to('/contacts');
        }

        if ($this->contactModel->delete($contactRelationId)) {
            $this->session->setFlashdata('success_contact_remove', 'Contato removido com sucesso.');
        } else {
            $this->session->setFlashdata('error_contact_remove', 'Erro ao remover o contato.');
            log_message('error', 'Failed to remove contact_relation_id: ' . $contactRelationId . ' for user_id: ' . $this->currentUserId);
        }
        return redirect()->to('/contacts');
    }
}