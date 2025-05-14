<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;
class AlterUsersRemoveBalance extends Migration
{
    public function up() {
        $this->forge->dropColumn('users', 'balance');
    }
    public function down() {
        $this->forge->addColumn('users', [
            'balance' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0.00]
        ]);
    }
}