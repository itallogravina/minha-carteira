<?php

namespace App\Models;

use CodeIgniter\Model;

class ContactModel extends Model
{
    protected $table            = 'contacts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'object';
    protected $allowedFields    = ['user_id', 'contact_user_id'];

    protected $useTimestamps = false;

    public function getUserContacts(int $userId): array
    {
        return $this->select('contacts.id as contact_relation_id, users.id as contact_id, users.name as contact_name, users.email as contact_email, contacts.created_at as contact_added_at')
                    ->join('users', 'users.id = contacts.contact_user_id')
                    ->where('contacts.user_id', $userId)
                    ->orderBy('users.name', 'ASC')
                    ->findAll();
    }

    public function contactExists(int $userId, int $contactUserId): bool
    {
        return $this->where('user_id', $userId)
                    ->where('contact_user_id', $contactUserId)
                    ->countAllResults() > 0;
    }
}