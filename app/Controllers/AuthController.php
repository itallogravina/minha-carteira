<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;
use App\Models\UserModel;

class AuthController extends Controller
{
    private $provider;
    private $session;
    private $userModel;

    public function __construct()
    {
        helper(['url', 'form']);
        $this->session = \Config\Services::session();
        $this->userModel = new UserModel();

        $authServerUrl = getenv('KEYCLOAK_AUTH_SERVER_URL');
        $realm = getenv('KEYCLOAK_REALM');
        $clientId = getenv('KEYCLOAK_CLIENT_ID');
        $clientSecret = getenv('KEYCLOAK_CLIENT_SECRET');
        $redirectUri = getenv('KEYCLOAK_REDIRECT_URI');

        if (!$authServerUrl || !$realm || !$clientId || !$clientSecret || !$redirectUri) {
            log_message('critical', 'Keycloak configuration variables are missing in .env file.');
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
        if ($this->session->get('is_logged_in')) {
            return redirect()->to('/wallet/dashboard');
        }

        $options = [
            'scope' => ['openid', 'profile', 'email']
        ];

        $authUrl = $this->provider->getAuthorizationUrl($options);
        $this->session->set('oauth2state', $this->provider->getState());

        return redirect()->to($authUrl);
    }

    public function callback()
    {
        if (empty($this->request->getGet('state')) || ($this->request->getGet('state') !== $this->session->get('oauth2state'))) {
            log_message('error', 'Invalid OAuth state. CSRF attempt or misconfiguration.');
            $this->session->remove('oauth2state');
            $this->session->setFlashdata('error', 'Falha na autenticação: estado inválido.');
            return redirect()->to('/auth/login');
        }

        if (!empty($this->request->getGet('code'))) {
            try {
                log_message('debug', 'Callback: State verified, attempting to get token with code: ' . $this->request->getGet('code'));

                $token = $this->provider->getAccessToken('authorization_code', [
                    'code' => $this->request->getGet('code')
                ]);

                log_message('debug', 'Callback: Token received successfully. Expires: ' . $token->getExpires());

                $keycloakUser = $this->provider->getResourceOwner($token);
                log_message('debug', 'Callback: User info received from Keycloak. Keycloak User ID (sub): ' . $keycloakUser->getId());

                $keycloakSub = $keycloakUser->getId();
                $keycloakEmail = $keycloakUser->getEmail();
                $keycloakName = $keycloakUser->getName() ?? ($keycloakUser->toArray()['preferred_username'] ?? 'Usuário');

                $userDataFromKeycloak = [
                    'email' => $keycloakEmail,
                    'name'  => $keycloakName,
                ];

                $localUser = $this->userModel->findOrCreateByKeycloakSub($keycloakSub, $userDataFromKeycloak);

                if (!$localUser) {
                    log_message('error', "Failed to find or create local user for Keycloak SUB: {$keycloakSub}. UserModel errors: " . json_encode($this->userModel->errors()));
                    $this->session->setFlashdata('error', 'Não foi possível processar suas informações de usuário. Tente novamente.');
                    return redirect()->to('/auth/login');
                }

                $sessionData = [
                    'is_logged_in'   => true,
                    'access_token'   => $token->getToken(),
                    'refresh_token'  => $token->getRefreshToken(),
                    'token_expires'  => $token->getExpires(),
                    'id_token'       => $token->getValues()['id_token'] ?? null,
                    'user_id'        => $localUser->id,
                    'user_name'      => $localUser->name,
                    'user_email'     => $localUser->email,
                    'keycloak_sub'   => $localUser->keycloak_sub,
                ];
                $this->session->set($sessionData);

                $this->session->remove('oauth2state');

                log_message('info', "User {$localUser->email} (Local ID: {$localUser->id}, Keycloak SUB: {$keycloakSub}) logged in successfully.");
                return redirect()->to('/wallet/dashboard');

            } catch (IdentityProviderException $e) {
                log_message('error', 'Keycloak Provider Exception during callback: ' . $e->getMessage() . ' Response: ' . ($e->getResponseBody() ? json_encode($e->getResponseBody()) : 'N/A'));
                $this->session->setFlashdata('error', 'Ocorreu um erro ao comunicar com o servidor de autenticação: ' . $e->getMessage());
                return redirect()->to('/auth/login');
            } catch (\Exception $e) {
                log_message('error', 'General Exception during Keycloak callback: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());
                $this->session->setFlashdata('error', 'Ocorreu um erro inesperado durante o login. Por favor, tente novamente.');
                return redirect()->to('/auth/login');
            }
        } else {
            $error = $this->request->getGet('error');
            $errorDesc = $this->request->getGet('error_description');
            log_message('warn', "Keycloak login failed or was cancelled by user. Error: {$error}, Description: {$errorDesc}");
            $this->session->setFlashdata('error', 'O login falhou ou foi cancelado. ' . ($errorDesc ?: $error));
            return redirect()->to('/auth/login');
        }
    }

    public function logout()
    {
        $idTokenHint = $this->session->get('id_token');
        $this->session->destroy();

        $logoutOptions = [
            'redirect_uri' => base_url('/auth/login')
        ];
        if ($idTokenHint) {
            $logoutOptions['id_token_hint'] = $idTokenHint;
        }

        $logoutUrl = $this->provider->getLogoutUrl($logoutOptions);

        log_message('info', 'User logged out. Redirecting to Keycloak logout URL.');
        return redirect()->to($logoutUrl);
    }
}
