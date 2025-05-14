<?php

namespace App\Filters; // Certifique-se que o namespace está correto

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class AuthGuard implements FilterInterface // Certifique-se que o nome da classe está correto
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        if (!$session->get('is_logged_in')) {
            log_message('info', 'AuthGuard: User not logged in. Redirecting to /auth/login.');
            return redirect()->to('/auth/login')->with('error', 'Você precisa estar logado para acessar esta página.');
        }
        log_message('debug', 'AuthGuard: User logged in. Allowing access.');
        // Não precisa retornar $request, não retornar nada já permite a continuação
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Geralmente nada a fazer aqui para um filtro de autenticação
    }
}