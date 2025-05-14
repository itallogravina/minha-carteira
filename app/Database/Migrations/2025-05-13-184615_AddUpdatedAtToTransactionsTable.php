<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class AddUpdatedAtToTransactionsTable extends Migration
{
    public function up()
    {
        $this->forge->addColumn('transactions', [
            'updated_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => true,
                'default' => new RawSql('CURRENT_TIMESTAMP'), 
                        ],
        ]);

    }

    public function down()
    {
        $this->forge->dropColumn('transactions', 'updated_at');
    }
}