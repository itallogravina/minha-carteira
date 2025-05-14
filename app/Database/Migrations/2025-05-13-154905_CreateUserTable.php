<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsersTable extends Migration // O nome da classe pode variar com o timestamp
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGSERIAL', // Para PostgreSQL
                'unsigned'       => true, // Não estritamente necessário para SERIAL no PG, mas inofensivo
                'auto_increment' => true,
            ],
            'keycloak_sub' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'unique'     => true,
                'null'       => false,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'unique'     => true,
                'null'       => false,
            ],
            'balance' => [ // <--- ESTE É O CAMPO DA "CARTEIRA"
                'type'       => 'DECIMAL',
                'constraint' => '12,2', // 12 dígitos no total, 2 após a vírgula. Ajuste conforme necessário.
                'default'    => 0.00,
                'null'       => false, // Um saldo sempre deve existir, mesmo que seja 0.
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('users');

        // Se você usa PostgreSQL e quer que 'updated_at' atualize automaticamente:
        // $this->db->query("
        //     CREATE OR REPLACE FUNCTION update_updated_at_column()
        //     RETURNS TRIGGER AS $$
        //     BEGIN
        //        NEW.updated_at = NOW();
        //        RETURN NEW;
        //     END;
        //     $$ language 'plpgsql';
        // ");
        // $this->db->query("
        //     CREATE TRIGGER set_timestamp_users
        //     BEFORE UPDATE ON users
        //     FOR EACH ROW
        //     EXECUTE PROCEDURE update_updated_at_column();
        // ");
    }

    public function down()
    {
        // Se adicionou o trigger:
        // $this->db->query("DROP TRIGGER IF EXISTS set_timestamp_users ON users;");
        // $this->db->query("DROP FUNCTION IF EXISTS update_updated_at_column();");
        $this->forge->dropTable('users');
    }
}