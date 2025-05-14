<?php
namespace App\Models;
use CodeIgniter\Model;
class WalletModel extends Model
{
    protected $table            = 'wallets';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $allowedFields    = ['user_id', 'balance', 'currency', 'is_default'];
    protected $useTimestamps    = true;

    public function getDefaultWalletByUser(int $userId)
    {
        return $this->where('user_id', $userId)->where('is_default', true)->first();
    }

    public function updateWalletBalance(int $walletId, float $amount, string $operation = 'add')
    {
        $this->db->transStart();
        $wallet = $this->find($walletId);
        if (!$wallet) { $this->db->transRollback(); return false; }

        $currentBalance = (float) $wallet->balance;
        $transactionAmount = (float) $amount;
        if ($operation === 'add') $newBalance = $currentBalance + $transactionAmount;
        elseif ($operation === 'subtract') $newBalance = $currentBalance - $transactionAmount;
        else { $this->db->transRollback(); return false; }

        if (!$this->update($walletId, ['balance' => $newBalance])) {
            $this->db->transRollback(); return false;
        }
        if ($this->db->transStatus() === false) {
            $this->db->transRollback(); return false;
        }
        $this->db->transComplete();
        return true;
    }
}