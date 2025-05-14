<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTransactionsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGSERIAL',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
            ],
            'related_user_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'amount' => [
                'type'       => 'DECIMAL',
                'constraint' => '12,2',
            ],
            'description' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'default'    => 'completed',
            ],
            'original_transaction_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('related_user_id', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('original_transaction_id', 'transactions', 'id', 'SET NULL', 'SET NULL');

        $this->forge->createTable('transactions');

        $this->db->query("ALTER TABLE transactions ADD CONSTRAINT check_transaction_type CHECK (type IN ('deposit', 'transfer', 'reversal'))");
        $this->db->query("ALTER TABLE transactions ADD CONSTRAINT check_transaction_status CHECK (status IN ('completed', 'pending', 'reversed', 'failed'))");
    }

    public function down()
    {
        $this->forge->dropTable('transactions');
    }
}