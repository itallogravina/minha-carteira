<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Meus Contatos<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Coluna para Adicionar Contato -->
    <div class="md:col-span-1 bg-white shadow-md rounded-lg p-6">
        <h2 class="text-xl font-semibold text-gray-700 mb-4">Adicionar Novo Contato</h2>

        <?php if (session()->getFlashdata('error_contact_add')): ?>
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded" role="alert">
                <?= session()->getFlashdata('error_contact_add') ?>
            </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('info_contact_add')): ?>
            <div class="mb-4 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded" role="alert">
                <?= session()->getFlashdata('info_contact_add') ?>
            </div>
        <?php endif; ?>
         <?php if (session()->getFlashdata('success_contact_add')): ?>
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded" role="alert">
                <?= session()->getFlashdata('success_contact_add') ?>
            </div>
        <?php endif; ?>

        <?= form_open('/contacts/add', ['class' => 'space-y-4']) ?>
            <div>
                <label for="contact_email" class="block text-sm font-medium text-gray-700">Email do Contato</label>
                <input type="email" name="contact_email" id="contact_email" required
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                       placeholder="contato@exemplo.com" value="<?= old('contact_email') ?>">
                <?php if (isset($validation) && $validation->hasError('contact_email')): ?>
                    <p class="mt-1 text-xs text-red-500"><?= esc($validation->getError('contact_email')) ?></p>
                <?php endif; ?>
            </div>
            <div>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-md transition duration-300">
                    Adicionar Contato
                </button>
            </div>
        <?= form_close() ?>
    </div>

    <!-- Coluna para Listar Contatos -->
    <div class="md:col-span-2 bg-white shadow-md rounded-lg p-6">
        <h1 class="text-2xl font-semibold text-gray-700 mb-4">Meus Contatos</h1>

        <?php if (session()->getFlashdata('error_contact_remove')): ?>
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded" role="alert">
                <?= session()->getFlashdata('error_contact_remove') ?>
            </div>
        <?php endif; ?>
        <?php if (session()->getFlashdata('success_contact_remove')): ?>
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded" role="alert">
                <?= session()->getFlashdata('success_contact_remove') ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($contacts)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Adicionado em</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($contacts as $contact): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= esc($contact->contact_name ?: 'N/A') ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= esc($contact->contact_email) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= \CodeIgniter\I18n\Time::parse($contact->contact_added_at)->toLocalizedString('dd MMM, yyyy') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                    <a href="<?= site_url('/wallet/transfer?receiver_email=' . urlencode($contact->contact_email)) ?>" class="text-indigo-600 hover:text-indigo-900" title="Transferir para este contato">Transferir</a>
                                    <a href="<?= site_url('/contacts/remove/' . $contact->contact_relation_id) ?>" class="text-red-600 hover:text-red-900" title="Remover contato" onclick="return confirm('Tem certeza que deseja remover este contato?')">Remover</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-gray-500">Você ainda não adicionou nenhum contato.</p>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>