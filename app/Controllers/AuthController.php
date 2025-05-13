<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Stevenmaguire\OAuth2\Client\Provider\Keycloak;

class AuthController extends Controller
{
    private $provider;
    private $session;

    public function __construct()
    {
        $this->session = \Config\Services::session();

        $this->provider = new Keycloak([
            'authServerUrl' => getenv('KEYCLOAK_AUTH_SERVER_URL'),
            'realm'         => getenv('KEYCLOAK_REALM'),
            'clientId'      => getenv('KEYCLOAK_CLIENT_ID'),
            'clientSecret'  => getenv('KEYCLOAK_CLIENT_SECRET'),
            'redirectUri'   => getenv('KEYCLOAK_REDIRECT_URI'),
        ]);
    }

    public function login()
    {
        if ($this->session->get('logged_in')) {
            return redirect()->to('/');
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
            log_message('error', 'Invalid OAuth state');
            $this->session->remove('oauth2state');
            exit('Invalid state');
        }

        if (!empty($this->request->getGet('code'))) {
            try {
                log_message('debug', 'Callback: State verified, attempting to get token with code: ' . $this->request->getGet('code'));

                $token = $this->provider->getAccessToken('authorization_code', [
                    'code' => $this->request->getGet('code')
                ]);

                log_message('debug', 'Callback: Token received successfully. Expires: ' . $token->getExpires());

                $user = $this->provider->getResourceOwner($token);
                log_message('debug', 'Callback: User info received successfully. User ID: ' . $user->getId());

                $this->session->set([
                    'logged_in'      => true,
                    'access_token'   => $token->getToken(),
                    'refresh_token'  => $token->getRefreshToken(),
                    'token_expires'  => $token->getExpires(),
                    'user_id'        => $user->getId(),
                    'user_name'      => $user->getName() ?? $user->getPreferredUsername(),
                    'user_email'     => $user->getEmail(),
                ]);

                $this->session->remove('oauth2state');

                return redirect()->to('/');

            } catch (IdentityProviderException $e) {
                log_message('error', 'Keycloak Provider Exception: ' . $e->getMessage());
                exit('Something went wrong: ' . $e->getMessage());
            } catch (\Exception $e) {
                log_message('error', 'General Exception during Keycloak callback: ' . $e->getMessage());
                exit('An unexpected error occurred.');
            }
        } else {
            $error = $this->request->getGet('error');
            $errorDesc = $this->request->getGet('error_description');
            log_message('error', "Keycloak login failed or cancelled. Error: {$error}, Description: {$errorDesc}");
            return redirect()->to('/auth/login')->with('error', 'Login failed or was cancelled.');
        }
    }

    public function logout()
    {
        $this->session->destroy();

        $logoutUrl = $this->provider->getLogoutUrl([
            'redirect_uri' => base_url('/')
        ]);

        return redirect()->to($logoutUrl);
    }
}
