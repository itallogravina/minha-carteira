<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['keycloak_sub', 'name', 'email', 'balance'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'name'          => 'permit_empty|max_length[100]',
        'email'         => 'required|valid_email|is_unique[users.email,id,{id}]',
        'keycloak_sub'  => 'required|is_unique[users.keycloak_sub,id,{id}]',
        // 'balance' não precisa de validação aqui, pois é definido programaticamente.
    ];
    protected $validationMessages   = [];
    protected $skipValidation       = false; // Certifique-se que a validação não está sendo pulada
    protected $cleanValidationRules = true;


    public function findOrCreateByKeycloakSub(string $keycloakSub, array $userDataFromKeycloak = [])
    {
        log_message('debug', "UserModel: Attempting to find user by keycloak_sub: {$keycloakSub}");
        $user = $this->where('keycloak_sub', $keycloakSub)->first();

        if (!$user) {
            log_message('info', "UserModel: User with keycloak_sub {$keycloakSub} not found. Attempting to create new user.");

            // Construir os dados para inserção
            $dataToInsert = [
                'keycloak_sub' => $keycloakSub,
                'email'        => $userDataFromKeycloak['email'] ?? null,
                'name'         => $userDataFromKeycloak['name'] ?? null,
                'balance'      => 0.00, // Saldo inicial padrão
            ];
            log_message('debug', "UserModel: Data for new user insertion: " . json_encode($dataToInsert));

            // Verificar se os campos NOT NULL estão presentes
            if (empty($dataToInsert['keycloak_sub'])) {
                log_message('error', "UserModel: keycloak_sub is empty. Cannot create user.");
                $this->validation->setError('keycloak_sub', 'Keycloak SUB é obrigatório.'); // Adiciona erro manual
                return null;
            }
            if (empty($dataToInsert['email'])) {
                log_message('error', "UserModel: email is empty. Cannot create user.");
                $this->validation->setError('email', 'Email é obrigatório.'); // Adiciona erro manual
                return null;
            }

            // Tentar inserir o usuário.
            // O método save() do Model tentará validar os dados antes de inserir,
            // se $skipValidation for false (que é o padrão).
            // Se a validação falhar, save() retornará false e $this->errors() será populado.
            // Se a validação passar mas a inserção no BD falhar por outro motivo (ex: constraint),
            // save() também retornará false, e $this->db->error() pode ter informações.

            try {
                // Usar $this->insert() é mais direto se a validação já foi feita ou é gerenciada aqui.
                // $this->save($dataToInsert) também funciona e aciona validação.
                // Vamos tentar com insert e depois checar $this->db->error() se falhar.

                $userId = $this->insert($dataToInsert, true); // O segundo parâmetro 'true' faz retornar o ID inserido

                if ($userId === false) {
                    // A inserção falhou.
                    $modelErrors = $this->errors(); // Erros de validação do Model
                    $dbError = $this->db->error();   // Erro direto do banco de dados

                    log_message('error', "UserModel: Failed to insert new user. Model Validation Errors: " . json_encode($modelErrors) . ". DB Error: " . json_encode($dbError));
                    
                    // Se $modelErrors estiver vazio mas $dbError tiver algo, o problema é no BD.
                    // Ex: UNIQUE constraint no email ou keycloak_sub, ou um campo NOT NULL faltando.
                    // Mesmo que a migration tenha 'null' => false, e você não envie o campo, o BD pode reclamar.

                    // Popular manualmente os erros se o $this->errors() estiver vazio mas o DB deu erro
                    if (empty($modelErrors) && !empty($dbError['message'])) {
                         if (strpos($dbError['message'], 'users_email_unique') !== false) {
                            $this->validation->setError('email', 'Este email já está em uso.');
                        } elseif (strpos($dbError['message'], 'users_keycloak_sub_unique') !== false) {
                             $this->validation->setError('keycloak_sub', 'Este identificador Keycloak já está em uso.');
                        } else {
                            // Erro genérico do BD
                             $this->validation->setError('database', 'Erro no banco de dados ao criar usuário: ' . $dbError['message']);
                        }
                    }
                    return null; // Retorna null indicando falha
                }

                log_message('info', "UserModel: New user created successfully. Local User ID: {$userId}");
                $user = $this->find($userId); // Busca o usuário recém-criado para retornar o objeto completo

            } catch (\Exception $e) {
                // Captura qualquer exceção que possa ocorrer durante a inserção
                log_message('error', "UserModel: Exception during user insertion: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
                $this->validation->setError('exception', 'Ocorreu uma exceção ao criar o usuário: ' . $e->getMessage());
                return null;
            }

        } else {
            log_message('debug', "UserModel: User found with keycloak_sub {$keycloakSub}. Local User ID: {$user->id}");
            // Usuário encontrado, opcionalmente atualizar dados (ex: nome, email)
            $dataToUpdate = [];
            if (isset($userDataFromKeycloak['email']) && $user->email !== $userDataFromKeycloak['email']) {
                $dataToUpdate['email'] = $userDataFromKeycloak['email'];
            }
            if (isset($userDataFromKeycloak['name']) && $user->name !== $userDataFromKeycloak['name']) {
                $dataToUpdate['name'] = $userDataFromKeycloak['name'];
            }

            if (!empty($dataToUpdate)) {
                log_message('debug', "UserModel: Updating user data for Local User ID {$user->id}: " . json_encode($dataToUpdate));
                if (!$this->update($user->id, $dataToUpdate)) {
                    log_message('error', "UserModel: Failed to update user data for Local User ID {$user->id}. Model Errors: " . json_encode($this->errors()) . ". DB Error: " . json_encode($this->db->error()));
                    // Não retornar null aqui, pois o usuário já existe, apenas a atualização falhou.
                    // Mas é importante logar o erro.
                } else {
                     $user = $this->find($user->id); // Recarrega para pegar os dados atualizados
                }
            }
        }
        return $user;
    }
    public function updateUserBalance(int $userId, float $amount, string $operation = 'add')
    {
        $this->db->transStart();

        $user = $this->find($userId); // Busca o usuário, que inclui o campo 'balance'
        if (!$user) {
            $this->db->transRollback();
            log_message('error', "updateUserBalance: User ID {$userId} not found.");
            return false;
        }

        $currentBalance = (float) $user->balance;
        $transactionAmount = (float) $amount;
        $newBalance = 0.00;

        if ($operation === 'add') {
            $newBalance = $currentBalance + $transactionAmount;
        } elseif ($operation === 'subtract') {
            // Você pode adicionar uma verificação aqui se não quiser permitir saldo negativo
            // if ($currentBalance < $transactionAmount) {
            //     $this->db->transRollback();
            //     log_message('error', "updateUserBalance: Insufficient balance for user ID {$userId}. Has: {$currentBalance}, needs: {$transactionAmount}");
            //     return false; // Ou lançar uma exceção específica
            // }
            $newBalance = $currentBalance - $transactionAmount;
        } else {
            $this->db->transRollback();
            log_message('error', "updateUserBalance: Invalid operation '{$operation}' for user ID {$userId}.");
            return false;
        }

        // Atualiza apenas o campo 'balance'
        if (!$this->update($userId, ['balance' => $newBalance])) {
            $this->db->transRollback();
            log_message('error', "updateUserBalance: Failed to update balance for user ID {$userId}. DB Error: " . json_encode($this->db->error()));
            return false;
        }

        if ($this->db->transStatus() === false) {
            $this->db->transRollback();
            log_message('error', "updateUserBalance: Transaction failed for user ID {$userId}.");
            return false;
        }

        $this->db->transComplete();
        log_message('info', "updateUserBalance: Balance updated for user ID {$userId}. New balance: {$newBalance}");
        return true;
    }
}