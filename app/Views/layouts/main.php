<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->renderSection('title') ?? 'Minha Carteira Virtual' ?></title>
    <script src="https://cdn.tailwindcss.com"></script> <!-- Tailwind CSS via CDN -->
    <!-- Se usar instalação local: -->
    <!-- <link href="<?= base_url('css/output.css') ?>" rel="stylesheet"> -->
    <style>
        /* Você pode adicionar estilos customizados aqui se necessário */
        body {
            font-family: 'Inter', sans-serif; /* Exemplo de fonte, adicione link no head se não for padrão */
        }
    </style>
    <!-- Adicione um link para uma fonte se desejar, ex: Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100 text-gray-800 min-h-screen flex flex-col">
    <nav class="bg-white shadow-md">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between">
                <div class="flex space-x-7">
                    <!-- Logo -->
                    <div>
                        <a href="<?= site_url('/wallet/dashboard') ?>" class="flex items-center py-4 px-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                            </svg>
                            <span class="font-semibold text-gray-700 text-lg">Carteira Virtual</span>
                        </a>
                    </div>

                    <!-- Navegação Primária (visível em telas maiores) -->
                    <?php if (session()->get('is_logged_in')): ?>
                        <div class="hidden md:flex items-center space-x-1">
                            <a href="<?= site_url('/wallet/dashboard') ?>"
                               class="py-4 px-3 text-gray-600 hover:text-indigo-600 transition duration-300 <?= (uri_string() == 'wallet/dashboard' ? 'border-b-2 border-indigo-600 text-indigo-600 font-semibold' : 'font-medium') ?>">
                                Dashboard
                            </a>
                            <a href="<?= site_url('/contacts') ?>"
                               class="py-4 px-3 text-gray-600 hover:text-indigo-600 transition duration-300 <?= (strpos(uri_string(), 'contacts') === 0 ? 'border-b-2 border-indigo-600 text-indigo-600 font-semibold' : 'font-medium') ?>">
                                Contatos
                            </a>
                            <!-- Adicione mais links de navegação aqui -->
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Navegação Secundária (direita) -->
                <div class="hidden md:flex items-center space-x-3 ">
                    <?php if (session()->get('is_logged_in')): ?>
                        <span class="py-2 px-2 text-gray-600 text-sm">Olá, <?= esc(session()->get('user_name')) ?></span>
                        <a href="<?= site_url('/auth/logout') ?>" class="py-2 px-4 bg-red-500 hover:bg-red-600 text-white text-sm font-semibold rounded-md shadow-sm transition duration-300">Logout</a>
                    <?php else: ?>
                        <a href="<?= site_url('/auth/login') ?>" class="py-2 px-4 bg-indigo-500 hover:bg-indigo-600 text-white text-sm font-semibold rounded-md shadow-sm transition duration-300">Login</a>
                    <?php endif; ?>
                </div>

                <!-- Botão do Menu Mobile (visível em telas menores) -->
                <?php if (session()->get('is_logged_in')): ?>
                <div class="md:hidden flex items-center">
                    <button class="outline-none mobile-menu-button">
                        <svg class=" w-6 h-6 text-gray-500 hover:text-indigo-600 "
                            fill="none" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Menu Mobile -->
        <?php if (session()->get('is_logged_in')): ?>
        <div class="hidden mobile-menu md:hidden">
            <ul class="pt-2 pb-4 space-y-1">
                <li>
                    <a href="<?= site_url('/wallet/dashboard') ?>" class="block py-2 px-4 text-sm hover:bg-indigo-100 <?= (uri_string() == 'wallet/dashboard' ? 'bg-indigo-100 text-indigo-700 font-semibold' : 'text-gray-700') ?>">Dashboard</a>
                </li>
                <li>
                    <a href="<?= site_url('/contacts') ?>" class="block py-2 px-4 text-sm hover:bg-indigo-100 <?= (strpos(uri_string(), 'contacts') === 0 ? 'bg-indigo-100 text-indigo-700 font-semibold' : 'text-gray-700') ?>">Contatos</a>
                </li>
                <!-- Adicione mais links de navegação mobile aqui -->
                 <li class="px-4 pt-2">
                     <span class="block text-xs text-gray-500">Olá, <?= esc(session()->get('user_name')) ?></span>
                 </li>
                 <li>
                    <a href="<?= site_url('/auth/logout') ?>" class="block w-full text-left mt-2 py-2 px-4 text-sm text-red-700 bg-red-100 hover:bg-red-200">Logout</a>
                 </li>
            </ul>
        </div>
        <?php endif; ?>
    </nav>

    <main class="flex-grow max-w-6xl mx-auto mt-6 mb-8 px-4 w-full">
        <!-- Flash Messages -->
        <?php
        $flashTypes = ['success', 'error', 'info', 'warning']; // Adicionado 'warning'
        $flashColors = [
            'success' => ['bg' => 'bg-green-100', 'border' => 'border-green-400', 'text' => 'text-green-700', 'title' => 'Sucesso!'],
            'error'   => ['bg' => 'bg-red-100',   'border' => 'border-red-400',   'text' => 'text-red-700',   'title' => 'Erro!'],
            'info'    => ['bg' => 'bg-blue-100',  'border' => 'border-blue-400',  'text' => 'text-blue-700',  'title' => 'Info:'],
            'warning' => ['bg' => 'bg-yellow-100','border' => 'border-yellow-400','text' => 'text-yellow-700','title' => 'Atenção:']
        ];
        ?>

        <?php foreach ($flashTypes as $type): ?>
            <?php if (session()->getFlashdata($type)): ?>
                <div class="mb-4 <?= $flashColors[$type]['bg'] ?> border <?= $flashColors[$type]['border'] ?> <?= $flashColors[$type]['text'] ?> px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold"><?= $flashColors[$type]['title'] ?></strong>
                    <span class="block sm:inline"><?= session()->getFlashdata($type) ?></span>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
        
        <?php
        // Adicionado para flashdata específicos da adição de contato
        // Poderia ser integrado ao loop acima se padronizarmos os nomes das flashdatas
        $contactFlashTypes = ['success_contact_add', 'error_contact_add', 'info_contact_add', 'success_contact_remove', 'error_contact_remove'];
        foreach ($contactFlashTypes as $type):
            if (session()->getFlashdata($type)):
                $baseType = explode('_', $type)[0]; // success, error, info
                $colors = $flashColors[$baseType] ?? $flashColors['info']; // Padrão para info se não encontrar
                ?>
                <div class="mb-4 <?= $colors['bg'] ?> border <?= $colors['border'] ?> <?= $colors['text'] ?> px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold"><?= ucfirst($baseType) ?>!</strong>
                    <span class="block sm:inline"><?= session()->getFlashdata($type) ?></span>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>


        <?= $this->renderSection('content') ?>
    </main>

    <footer class="text-center py-6 text-sm text-gray-500">
        © <?= date('Y') ?> Minha Carteira Virtual. Todos os direitos reservados.
    </footer>

    <?php if (session()->get('is_logged_in')): ?>
    <script>
        // Script para o menu mobile
        const btn = document.querySelector("button.mobile-menu-button");
        const menu = document.querySelector(".mobile-menu");

        if (btn && menu) {
            btn.addEventListener("click", () => {
                menu.classList.toggle("hidden");
            });
        }
    </script>
    <?php endif; ?>
</body>
</html>