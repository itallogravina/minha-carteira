<?php

namespace Config;

$routes = Services::routes();

$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
// $routes->setAutoRoute(false);
$routes->group('wallet', ['filter' => 'authGuard'], static function ($routes) {
    $routes->get('dashboard', 'WalletController::dashboard');
    $routes->get('dashboard', 'WalletController::dashboard'); // ou Wallet::dashboard se o nome do controller for Wallet.php
    $routes->get('deposit', 'WalletController::deposit');     // Rota GET para mostrar o formulário
    $routes->post('deposit', 'WalletController::deposit');    // Rota POST para processar o formulári
    $routes->get('transfer', 'WalletController::transfer');  // Para exibir o formulário
    $routes->post('transfer', 'WalletController::transfer'); // Para processar o formulário
});

$routes->group('contacts', ['filter' => 'authGuard'], static function ($routes) {
    $routes->get('/', 'ContactController::index');        // Listar contatos e formulário de adicionar
    $routes->post('add', 'ContactController::add');         // Processar adição de contato
    $routes->get('remove/(:num)', 'ContactController::remove/$1'); // Remover contato
});

$routes->get('/', 'Home::index');
$routes->get('/auth/login', 'AuthController::login');
$routes->get('/auth/callback', 'AuthController::callback');
$routes->get('/auth/logout', 'AuthController::logout');

if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
