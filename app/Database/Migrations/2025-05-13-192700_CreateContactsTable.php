<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateContactsTable extends Migration 
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
                'type'       => 'BIGINT',
                'unsigned'   => true,
            ],
            'contact_user_id' => [ 
                'type'       => 'BIGINT',
                'unsigned'   => true,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE'); 
        $this->forge->addForeignKey('contact_user_id', 'users', 'id', 'CASCADE', 'CASCADE'); 
        $this->forge->addUniqueKey(['user_id', 'contact_user_id'], 'idx_user_contact_unique'); 
        $this->forge->createTable('contacts');
    }

    public function down()
    {
        $this->forge->dropTable('contacts');
    }
}