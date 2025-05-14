<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\TransactionModel;

class WalletController extends BaseController
{
    protected $userModel;
    protected $transactionModel;
    protected $session;
    protected $currentUserId;
    protected $currentUserName;

    public function __construct()
    {
        $this->session = \Config\Services::session();
        helper(['form', 'url', 'number']);

        $this->currentUserId = $this->session->get('user_id');
        $this->currentUserName = $this->session->get('user_name');

        if ($this->currentUserId) {
            $this->userModel = new UserModel();
            $this->transactionModel = new TransactionModel();
        } else {
            if ($this->session->get('is_logged_in')) {
                log_message('error', 'WalletController constructor: User is_logged_in but currentUserId is null. Session data: ' . json_encode($this->session->get()));
            }
        }
    }

    private function _ensureModelsAndUser() {
        if (!$this->currentUserId || !$this->userModel || !$this->transactionModel) {
            log_message('critical', 'WalletController: Attempted action without valid user session or models initialized. currentUserId: '. ($this->currentUserId ?? 'null'));
            $this->session->destroy();
            header('Location: ' . site_url('/auth/login?error=session_error'));
            exit();
        }
    }

    public function dashboard()
    {
        $this->_ensureModelsAndUser();

        $user = $this->userModel->find($this->currentUserId);
        $saldoDaCarteira = $user->balance;
        if (!$user) {
            log_message('error', 'Dashboard: User not found in DB with ID ' . $this->currentUserId . '. Forcing logout.');
            $this->session->destroy();
            return redirect()->to('/auth/login')->with('error', 'Seu usuário não foi encontrado. Por favor, faça login novamente.');
        }

        $transactions = $this->transactionModel
            ->where('user_id', $this->currentUserId)
            ->orWhere('related_user_id', $this->currentUserId)
            ->orderBy('created_at', 'DESC')
            ->findAll(20);

        return view('wallet/dashboard', [
            'user' => $user,
            'transactions' => $transactions,
            'userName' => $this->currentUserName
        ]);
    }

    public function deposit()
    {
        log_message('debug', 'WalletController: deposit() method called. Method: ' . $this->request->getMethod());

        $this->_ensureModelsAndUser();
        $data = [];

        if ($this->request->getMethod() === 'POST') {
            log_message('debug', 'Deposit: POST request received. Data: ' . json_encode($this->request->getPost()));

            $rules = [
                'amount' => 'required|numeric|greater_than[0.00]',
            ];

            if (!$this->validate($rules)) {
                $data['validation'] = $this->validator;
                log_message('error', 'Deposit validation failed: ' . json_encode($this->validator->getErrors()));
            } else {
                log_message('debug', 'Deposit validation successful.');
                $amount = (float) $this->request->getPost('amount');

                $this->userModel->db->transStart();
                log_message('debug', 'Deposit: DB Transaction Started.');

                $balanceUpdated = $this->userModel->updateUserBalance($this->currentUserId, $amount, 'add');
                if (!$balanceUpdated) {
                    $this->userModel->db->transRollback();
                    $this->session->setFlashdata('error', 'Erro ao atualizar seu saldo durante o depósito.');
                    log_message('error', 'Deposit failed for user ' . $this->currentUserId . ': Could not update balance (updateUserBalance returned false).');
                    return redirect()->to('/wallet/deposit')->withInput();
                }
                log_message('debug', 'Deposit: User balance update attempted (Result: ' . ($balanceUpdated ? 'Success' : 'Fail') . ').');

                $transactionData = [
                    'user_id'         => $this->currentUserId,
                    'related_user_id' => $this->currentUserId,
                    'type'            => 'deposit',
                    'amount'          => $amount,
                    'description'     => 'Depósito em conta',
                    'status'          => 'completed'
                ];

                $transactionInserted = $this->transactionModel->insert($transactionData);
                if (!$transactionInserted) {
                    $this->userModel->db->transRollback();
                    $this->session->setFlashdata('error', 'Erro ao registrar a transação do depósito.');
                    log_message('error', 'Deposit failed for user ' . $this->currentUserId . ': Could not insert transaction record (insert returned false/null). Error: ' . json_encode($this->transactionModel->errors()));
                    return redirect()->to('/wallet/deposit')->withInput();
                }
                log_message('debug', 'Deposit: Transaction record insertion attempted (Result: ' . ($transactionInserted ? 'Success, ID: '.$this->transactionModel->getInsertID() : 'Fail') . ').');

                if ($this->userModel->db->transStatus() === false) {
                    $this->userModel->db->transRollback();
                    $this->session->setFlashdata('error', 'Falha crítica na transação do depósito.');
                    log_message('critical', 'Deposit DB transaction status returned false for user ' . $this->currentUserId . '. Rolled back.');
                    return redirect()->to('/wallet/deposit')->withInput()->with('error', 'Falha crítica na transação do depósito.');
                } else {
                    $this->userModel->db->transComplete();
                    $this->session->setFlashdata('success', 'Depósito de R$ ' . number_format($amount, 2, ',', '.') . ' realizado com sucesso!');
                    log_message('info', 'Deposit of ' . $amount . ' successful for user ' . $this->currentUserId . '. DB Transaction Completed.');
                    return redirect()->to('/wallet/dashboard');
                }
            }
        }

        log_message('debug', 'Deposit: Rendering deposit view. Validation errors (if any): ' . (isset($data['validation']) ? json_encode($data['validation']->getErrors()) : 'None'));
        return view('wallet/deposit', $data);
    }

    public function transfer()
    {
        $this->_ensureModelsAndUser();
        $data = [];

        log_message('debug', 'Transfer Method Entry: User in session - ID: ' . ($this->currentUserId ?? 'N/A') . ', Email: ' . ($this->session->get('user_email') ?? 'N/A'));

        $receiverEmailFromGet = trim((string) $this->request->getGet('receiver_email'));
        if ($this->request->getMethod() === 'GET' && $receiverEmailFromGet && filter_var($receiverEmailFromGet, FILTER_VALIDATE_EMAIL)) {
            $_POST['receiver_email'] = $receiverEmailFromGet;
            log_message('debug', 'Transfer: Pre-filling receiver_email from GET parameter: ' . $receiverEmailFromGet);
        }

        if ($this->request->getMethod() === 'POST') {
            log_message('debug', 'Transfer: POST request received. Data: ' . json_encode($this->request->getPost()));

            $currentUserEmail = $this->session->get('user_email');
            if (!$currentUserEmail) {
                log_message('error', 'Transfer: Current user email not found in session for user ID ' . ($this->currentUserId ?? 'N/A'));
                $this->session->setFlashdata('error', 'Sua sessão parece estar incompleta. Por favor, faça login novamente.');
                return redirect()->to('/auth/login');
            }
            log_message('debug', 'Transfer: Current user email for validation: ' . $currentUserEmail);

            $postData = $this->request->getPost();
            $postData['current_user_email_field_for_validation'] = $currentUserEmail;

            $rules = [
                'receiver_email' => "required|valid_email|differs[current_user_email_field_for_validation]",
                'amount'         => 'required|numeric|greater_than[0.00]'
            ];
            $validationMessages = [
                'receiver_email' => [
                    'differs' => 'Você não pode transferir para o seu próprio email.'
                ]
            ];

            if (!$this->validateData($postData, $rules, $validationMessages)) {
                $data['validation'] = $this->validator;
                log_message('error', 'Transfer validation failed: ' . json_encode($this->validator->getErrors()));
            } else {
                log_message('debug', 'Transfer validation successful.');
                $amount = (float) $postData['amount'];
                $receiverEmail = trim($postData['receiver_email']);

                $sender = $this->userModel->find($this->currentUserId);
                if (!$sender) {
                    $this->session->setFlashdata('error', 'Sua conta não foi encontrada. Por favor, faça login novamente.');
                    log_message('critical', 'Transfer: Sender (current user ID ' . $this->currentUserId . ') not found in DB.');
                    return redirect()->to('/auth/login');
                }

                $receiver = $this->userModel->where('email', $receiverEmail)->first();

                if (!$receiver) {
                    $this->session->setFlashdata('error', 'Usuário destinatário com email "' . esc($receiverEmail) . '" não encontrado na plataforma.');
                    log_message('warning', 'Transfer attempt to non-existent email: ' . $receiverEmail . ' by user ID ' . $this->currentUserId);
                    return redirect()->to('/wallet/transfer')->withInput();
                }

                if ((float)$sender->balance < $amount) {
                    $this->session->setFlashdata('error', 'Saldo insuficiente (R$ ' . number_format($sender->balance, 2, ',', '.') . ') para realizar a transferência de R$ ' . number_format($amount, 2, ',', '.'). '.');
                    log_message('warning', 'Transfer: Insufficient balance for user ID ' . $sender->id . '. Has: ' . $sender->balance . ', Needs: ' . $amount);
                    return redirect()->to('/wallet/transfer')->withInput();
                }

                $this->userModel->db->transStart();
                log_message('debug', 'Transfer: DB Transaction Started for transfer from user ' . $sender->id . ' to user ' . $receiver->id . ' amount ' . $amount);

                $senderBalanceUpdated = $this->userModel->updateUserBalance($sender->id, $amount, 'subtract');
                if (!$senderBalanceUpdated) {
                    $this->userModel->db->transRollback();
                    $this->session->setFlashdata('error', 'Erro ao debitar o valor da sua conta.');
                    log_message('error', 'Transfer failed: Could not subtract balance from sender ID ' . $sender->id);
                    return redirect()->to('/wallet/transfer')->withInput();
                }

                $receiverBalanceUpdated = $this->userModel->updateUserBalance($receiver->id, $amount, 'add');
                if (!$receiverBalanceUpdated) {
                    $this->userModel->db->transRollback();
                    $this->session->setFlashdata('error', 'Erro ao creditar o valor na conta do destinatário.');
                    log_message('error', 'Transfer failed: Could not add balance to receiver ID ' . $receiver->id);
                    return redirect()->to('/wallet/transfer')->withInput();
                }

                $transactionData = [
                    'user_id'         => $sender->id,
                    'related_user_id' => $receiver->id,
                    'type'            => 'transfer',
                    'amount'          => $amount,
                    'description'     => "Transferência para " . esc($receiver->name ?: $receiver->email) . " (de " . esc($sender->name ?: $sender->email) . ")",
                    'status'          => 'completed'
                ];
                $transactionId = $this->transactionModel->insert($transactionData);
                if (!$transactionId) {
                    $this->userModel->db->transRollback();
                    $this->session->setFlashdata('error', 'Erro ao registrar a transação da transferência.');
                    log_message('error', 'Transfer failed: Could not insert transaction record. Errors: ' . json_encode($this->transactionModel->errors()));
                    return redirect()->to('/wallet/transfer')->withInput();
                }

                if ($this->userModel->db->transStatus() === false) {
                    $this->userModel->db->transRollback();
                    $this->session->setFlashdata('error', 'Falha crítica na transação da transferência.');
                    log_message('critical', 'Transfer DB transaction status returned false. Rolled back. Sender: ' . $sender->id . ' Receiver: ' . $receiver->id . ' Amount: ' . $amount);
                    return redirect()->to('/wallet/transfer')->withInput();
                } else {
                    $this->userModel->db->transComplete();
                    $this->session->setFlashdata('success', 'Transferência de R$ ' . number_format($amount, 2, ',', '.') . ' para ' . esc($receiver->name ?: $receiver->email) . ' realizada com sucesso!');
                    log_message('info', 'Transfer successful. Tx ID: ' . $transactionId . '. From: ' . $sender->id . ' To: ' . $receiver->id . ' Amount: ' . $amount);
                    return redirect()->to('/wallet/dashboard');
                }
            }
        }

        return view('wallet/transfer', $data);
    }
}
