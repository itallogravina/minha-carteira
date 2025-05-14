<?= $this->extend('layouts/main') ?>

<?= $this->section('title') ?>Depositar Dinheiro<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="max-w-md mx-auto bg-white shadow-md rounded-lg p-6 mt-10">
    <h1 class="text-2xl font-semibold text-gray-700 mb-6 text-center">Depositar Dinheiro</h1>

    <?= form_open('/wallet/deposit', ['class' => 'space-y-6']) ?>
        <div>
            <label for="amount" class="block text-sm font-medium text-gray-700">Valor do Depósito (R$)</label>
            <div class="mt-1 relative rounded-md shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-gray-500 sm:text-sm"> R$ </span>
                </div>
                <input type="number" name="amount" id="amount" step="0.01" min="0.01" required
                       class="appearance-none block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                       placeholder="50.00"
                       value="<?= old('amount')?>">
            </div>
            <?php if (isset($validation) && $validation->hasError('amount')): ?>
                <p class="mt-2 text-sm text-red-600"><?= esc($validation->getError('amount')) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <button type="submit"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                Confirmar Depósito
            </button>
        </div>
    <?= form_close() ?>

    <div class="mt-6 text-center">
        <a href="<?= site_url('/wallet/dashboard') ?>" class="font-medium text-indigo-600 hover:text-indigo-500">
            ← Voltar para o Dashboard
        </a>
    </div>
</div>
<?= $this->endSection() ?>
