<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Dashboard<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="bg-white shadow-md rounded-lg p-6">
    <h1 class="text-2xl font-semibold text-gray-700 mb-2">Minha Carteira</h1>
    <p class="text-gray-600 mb-4">Bem-vindo(a) de volta, <?= esc($userName ?? 'Usuário') ?>!</p>

    <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-md">
        <p class="text-lg text-gray-700">Seu saldo atual é:</p>
        <p class="text-3xl font-bold text-blue-600">R$ <?= number_format($user->balance, 2, ',', '.') ?></p>
    </div>

    <div class="flex space-x-4 mb-8">
        <a href="<?= site_url('/wallet/deposit') ?>" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded transition duration-300">
            Depositar Dinheiro
        </a>
        <a href="<?= site_url('/wallet/transfer') ?>" class="bg-indigo-500 hover:bg-indigo-600 text-white font-bold py-2 px-4 rounded transition duration-300">
            Transferir Dinheiro
        </a>
    </div>

    <h2 class="text-xl font-semibold text-gray-700 mb-4">Histórico de Transações</h2>
    <?php if (!empty($transactions)): ?>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valor</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descrição</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($transactions as $tx): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?= $tx->id ?></td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"> <?= \CodeIgniter\I18n\Time::parse($tx->created_at, 'UTC') ->setTimezone(session()->get('timezone') ?? 'America/Sao_Paulo')->toLocalizedString('dd MMM, yyyy HH:mm')?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php
                                $typeClass = '';
                                switch ($tx->type) {
                                    case 'Deposito': $typeClass = 'bg-green-100 text-green-800'; break;
                                    case 'Transferencia': $typeClass = 'bg-blue-100 text-blue-800'; break;
                                    // case 'reversal': $typeClass = 'bg-yellow-100 text-yellow-800'; break;
                                    default: $typeClass = 'bg-gray-100 text-gray-800';
                                }
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $typeClass ?>">
                                    <?= ucfirst(esc($tx->type)) ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <?php
                                $amount = (float) $tx->amount;
                                $formattedAmount = number_format($amount, 2, ',', '.');
                                $loggedInUserId = session()->get('user_id');

                                if ($tx->type === 'transfer') {
                                    if ($tx->user_id == $loggedInUserId) { // Enviou
                                        echo "<span class='text-red-600 font-semibold'>- R$ {$formattedAmount}</span>";
                                    } elseif ($tx->related_user_id == $loggedInUserId) { // Recebeu
                                        echo "<span class='text-green-600 font-semibold'>+ R$ {$formattedAmount}</span>";
                                    } else { // Caso estranho, não deveria acontecer se a query estiver correta
                                        echo "R$ {$formattedAmount}";
                                    }
                                } elseif ($tx->type === 'deposit' && $tx->related_user_id == $loggedInUserId) { // Depositou em sua conta
                                    echo "<span class='text-green-600 font-semibold'>+ R$ {$formattedAmount}</span>";
                                } elseif ($tx->type === 'reversal') {
                                    // Se a reversão foi de um débito (transferência enviada), o valor volta positivo.
                                    // Se a reversão foi de um crédito (depósito/transferência recebida), o valor sai negativo.
                                    // Precisamos da transação original para determinar isso corretamente.
                                    // Simplificação por agora:
                                    if ($tx->user_id == $loggedInUserId) { // Reversão iniciada por mim, ou que me credita
                                        echo "<span class='text-green-600 font-semibold'>+ R$ {$formattedAmount}</span>"; // Assumindo que a reversão credita quem a iniciou
                                    } else {
                                        echo "<span class='text-red-600 font-semibold'>- R$ {$formattedAmount}</span>"; // Reversão que me debita
                                    }
                                } else {
                                    echo "R$ {$formattedAmount}";
                                }
                                ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate" title="<?= esc($tx->description) ?>"><?= esc($tx->description) ?: '-' ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php
                                $statusClass = 'bg-gray-100 text-gray-800';
                                if ($tx->status === 'completed') $statusClass = 'bg-green-100 text-green-800';
                                elseif ($tx->status === 'pending') $statusClass = 'bg-yellow-100 text-yellow-800';
                                elseif ($tx->status === 'reversed') $statusClass = 'bg-purple-100 text-purple-800';
                                elseif ($tx->status === 'failed') $statusClass = 'bg-red-100 text-red-800';
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusClass ?>">
                                    <?= ucfirst(esc($tx->status)) ?>
                                </span>
                            </td>
                
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p class="text-gray-500">Nenhuma transação encontrada.</p>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>