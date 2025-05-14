<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;
use App\Models\UserModel; // Importar o UserModel

class AuthController extends Controller
{
    private $provider;
    private $session;
    private $userModel; // Adicionar propriedade para UserModel

    public function __construct()
    {
        helper(['url', 'form']); // Adicionar helpers, se necessário em outras partes
        $this->session = \Config\Services::session();
        $this->userModel = new UserModel(); // Instanciar o UserModel

        // Configuração do provedor Keycloak
        // Certifique-se que as variáveis de ambiente estão corretamente configuradas no seu .env
        $authServerUrl = getenv('KEYCLOAK_AUTH_SERVER_URL');
        $realm = getenv('KEYCLOAK_REALM');
        $clientId = getenv('KEYCLOAK_CLIENT_ID');
        $clientSecret = getenv('KEYCLOAK_CLIENT_SECRET');
        $redirectUri = getenv('KEYCLOAK_REDIRECT_URI');

        if (!$authServerUrl || !$realm || !$clientId || !$clientSecret || !$redirectUri) {
            log_message('critical', 'Keycloak configuration variables are missing in .env file.');
            // Em um ambiente de produção, você pode querer lançar uma exceção ou lidar de forma mais robusta
            // exit('Keycloak configuration is incomplete. Please check the .env file.');
        }

        $this->provider = new Keycloak([
            'authServerUrl' => $authServerUrl,
            'realm'         => $realm,
            'clientId'      => $clientId,
            'clientSecret'  => $clientSecret,
            'redirectUri'   => $redirectUri,
        ]);
    }

    public function login()
    {
        // Se o usuário já estiver logado (baseado na sessão da nossa aplicação)
        if ($this->session->get('is_logged_in')) { // Mudado de 'logged_in' para 'is_logged_in' para consistência
            return redirect()->to('/wallet/dashboard'); // Redirecionar para o dashboard ou página principal
        }

        $options = [
            'scope' => ['openid', 'profile', 'email'] // Escopos para obter informações do usuário
        ];

        $authUrl = $this->provider->getAuthorizationUrl($options);
        $this->session->set('oauth2state', $this->provider->getState()); // Armazenar o state para validação CSRF

        return redirect()->to($authUrl);
    }

    public function callback()
    {
        // Validar o state para prevenir ataques CSRF
        if (empty($this->request->getGet('state')) || ($this->request->getGet('state') !== $this->session->get('oauth2state'))) {
            log_message('error', 'Invalid OAuth state. CSRF attempt or misconfiguration.');
            $this->session->remove('oauth2state');
            $this->session->setFlashdata('error', 'Falha na autenticação: estado inválido.');
            return redirect()->to('/auth/login');
        }

        // Verificar se o código de autorização foi recebido
        if (!empty($this->request->getGet('code'))) {
            try {
                log_message('debug', 'Callback: State verified, attempting to get token with code: ' . $this->request->getGet('code'));

                // Trocar o código de autorização por um token de acesso
                $token = $this->provider->getAccessToken('authorization_code', [
                    'code' => $this->request->getGet('code')
                ]);

                log_message('debug', 'Callback: Token received successfully. Expires: ' . $token->getExpires());
                // Opcional: Armazenar o ID Token se ele for diferente do Access Token e necessário
                // $idTokenString = $token->getValues()['id_token'] ?? null;

                // Obter os detalhes do usuário do Keycloak usando o token de acesso
                $keycloakUser = $this->provider->getResourceOwner($token);
                log_message('debug', 'Callback: User info received from Keycloak. Keycloak User ID (sub): ' . $keycloakUser->getId());

                // ----- Integração com o UserModel -----
                $keycloakSub = $keycloakUser->getId(); // 'sub' do JWT
                $keycloakEmail = $keycloakUser->getEmail();
                // Tenta pegar o nome completo, senão o 'preferred_username'
                $keycloakName = $keycloakUser->getName() ?? ($keycloakUser->toArray()['preferred_username'] ?? 'Usuário');

                $userDataFromKeycloak = [
                    'email' => $keycloakEmail,
                    'name'  => $keycloakName,
                ];

                // Encontra ou cria o usuário no banco de dados local
                $localUser = $this->userModel->findOrCreateByKeycloakSub($keycloakSub, $userDataFromKeycloak);

                if (!$localUser) {
                    log_message('error', "Failed to find or create local user for Keycloak SUB: {$keycloakSub}. UserModel errors: " . json_encode($this->userModel->errors()));
                    $this->session->setFlashdata('error', 'Não foi possível processar suas informações de usuário. Tente novamente.');
                    return redirect()->to('/auth/login');
                }
                // ----- Fim da Integração com o UserModel -----

                // Definir os dados da sessão da aplicação
                $sessionData = [
                    'is_logged_in'   => true,
                    'access_token'   => $token->getToken(), // Para futuras chamadas de API ao Keycloak ou outras
                    'refresh_token'  => $token->getRefreshToken(), // Para renovar o token de acesso
                    'token_expires'  => $token->getExpires(),      // Quando o token de acesso expira
                    'id_token'       => $token->getValues()['id_token'] ?? null, // Armazena o ID token para logout ou outras necessidades
                    'user_id'        => $localUser->id,             // ID do usuário na SUA tabela 'users'
                    'user_name'      => $localUser->name,
                    'user_email'     => $localUser->email,
                    'keycloak_sub'   => $localUser->keycloak_sub,   // O 'sub' do Keycloak, para referência
                ];
                $this->session->set($sessionData);

                $this->session->remove('oauth2state'); // Limpar o state da sessão

                log_message('info', "User {$localUser->email} (Local ID: {$localUser->id}, Keycloak SUB: {$keycloakSub}) logged in successfully.");
                return redirect()->to('/wallet/dashboard'); // Redirecionar para o dashboard ou página principal

            } catch (IdentityProviderException $e) {
                // Erro ao obter token ou informações do usuário do provedor
                log_message('error', 'Keycloak Provider Exception during callback: ' . $e->getMessage() . ' Response: ' . ($e->getResponseBody() ? json_encode($e->getResponseBody()) : 'N/A'));
                $this->session->setFlashdata('error', 'Ocorreu um erro ao comunicar com o servidor de autenticação: ' . $e->getMessage());
                return redirect()->to('/auth/login');
            } catch (\Exception $e) {
                // Qualquer outra exceção inesperada
                log_message('error', 'General Exception during Keycloak callback: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
                $this->session->setFlashdata('error', 'Ocorreu um erro inesperado durante o login. Por favor, tente novamente.');
                return redirect()->to('/auth/login');
            }
        } else {
            // Se não houver 'code', pode ser que o usuário cancelou o login no Keycloak
            // ou houve um erro antes do redirecionamento com código.
            $error = $this->request->getGet('error');
            $errorDesc = $this->request->getGet('error_description');
            log_message('warn', "Keycloak login failed or was cancelled by user. Error: {$error}, Description: {$errorDesc}");
            $this->session->setFlashdata('error', 'O login falhou ou foi cancelado. ' . ($errorDesc ?: $error));
            return redirect()->to('/auth/login');
        }
    }

    public function logout()
    {
        $idTokenHint = $this->session->get('id_token'); // Pega o ID Token da sessão
        $this->session->destroy(); // Destruir a sessão local da aplicação

        // Configurações para o logout no Keycloak
        // `post_logout_redirect_uri` é para onde o Keycloak deve redirecionar APÓS o logout.
        // `id_token_hint` é recomendado para um logout mais robusto no Keycloak.
        $logoutOptions = [
            'redirect_uri' => base_url('/auth/login') // Para onde o usuário será redirecionado após o logout no Keycloak
        ];
        if ($idTokenHint) {
            $logoutOptions['id_token_hint'] = $idTokenHint;
        }

        $logoutUrl = $this->provider->getLogoutUrl($logoutOptions);

        log_message('info', 'User logged out. Redirecting to Keycloak logout URL.');
        return redirect()->to($logoutUrl);
    }
}