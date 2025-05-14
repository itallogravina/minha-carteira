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
                'null'    => true, // Ou false com default, dependendo da sua necessidade
                'default' => new RawSql('CURRENT_TIMESTAMP'), // Para PostgreSQL, pode ser só 'CURRENT_TIMESTAMP' na string se o DB suportar na definição
            ],
        ]);

        // Opcional: Se você quer que o PostgreSQL atualize automaticamente no UPDATE
        // (Requer a função e o trigger, como mostrado anteriormente para a tabela users)
        // Se não adicionar o trigger, o CodeIgniter cuidará de preencher no update.
    }

    public function down()
    {
        $this->forge->dropColumn('transactions', 'updated_at');
    }
}