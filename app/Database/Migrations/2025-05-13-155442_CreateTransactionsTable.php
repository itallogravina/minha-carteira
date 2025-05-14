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
                'unsigned'       => true, // Mantido por consistência, embora não estritamente necessário para (BIG)SERIAL
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
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'), // Correto
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('related_user_id', 'users', 'id', 'SET NULL', 'SET NULL');
        $this->forge->addForeignKey('original_transaction_id', 'transactions', 'id', 'SET NULL', 'SET NULL');

        $this->forge->createTable('transactions');

        // Adicionar CHECK constraints usando $this->db->query()
        // É importante que a tabela já exista antes de tentar adicionar constraints a ela.
        $this->db->query("ALTER TABLE transactions ADD CONSTRAINT check_transaction_type CHECK (type IN ('deposit', 'transfer', 'reversal'))");
        $this->db->query("ALTER TABLE transactions ADD CONSTRAINT check_transaction_status CHECK (status IN ('completed', 'pending', 'reversed', 'failed'))");
    }

    public function down()
    {
        // A remoção das constraints CHECK não é estritamente necessária aqui,
        // pois elas são removidas quando a tabela é dropada.
        // No entanto, se você quisesse removê-las explicitamente antes de dropar a tabela:
        // $this->db->query("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS check_transaction_type");
        // $this->db->query("ALTER TABLE transactions DROP CONSTRAINT IF EXISTS check_transaction_status");

        $this->forge->dropTable('transactions');
    }
}