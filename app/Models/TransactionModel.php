<?php

namespace App\Models;

use CodeIgniter\Model;

class TransactionModel extends Model
{   
    protected $table            = 'transactions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';

    protected $allowedFields    = [
        'user_id',
        'related_user_id',
        'type',
        'amount',
        'description',
        'status',
        'original_transaction_id'
    ];

    protected $useTimestamps = false;
    protected $createdField  = 'created_at';
    protected $updatedField  = null;

    public function getTransactionsByUser(int $userId, int $limit = 20, int $offset = 0): ?array
    {
        return $this->where('user_id', $userId)
                    ->orWhere('related_user_id', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll($limit, $offset);
    }

    public function findTransactionByIdForUser(int $transactionId, int $userId): ?object
    {
        return $this->where('id', $transactionId)
                    ->groupStart()
                        ->where('user_id', $userId)
                        ->orWhere('related_user_id', $userId)
                    ->groupEnd()
                    ->first();
    }
}
