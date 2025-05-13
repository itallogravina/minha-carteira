<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        $session = \Config\Services::session();

        // Verifica se o usuário está logado (se existe 'logged_in' na sessão)
        if (!$session->get('logged_in')) {
            // Se não estiver logado, redireciona para a rota de login
            return redirect()->to('/auth/login');
        }

        // Se estiver logado, carrega os dados do usuário da sessão
        $data['userName'] = $session->get('user_name');
        $data['userEmail'] = $session->get('user_email');

        // Carrega a view que mostra a mensagem de "logado"
        return view('logged_in', $data);
    }
}