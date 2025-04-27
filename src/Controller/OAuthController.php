<?php

namespace App\Controller;

use App\Entity\User;
use App\Horaro\Service\ObscurityCodecService;
use App\Repository\ConfigRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Client\Provider\AbstractProvider;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Vertisan\OAuth2\Client\Provider\TwitchHelix as TwitchHelixOAuthProvider;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

final class OAuthController extends BaseController
{
    public function __construct(
        ConfigRepository                        $config,
        Security                                $security,
        EntityManagerInterface                  $entityManager,
        ObscurityCodecService $obscurityCodec,
        private readonly FormLoginAuthenticator $authenticator,
    )
    {
        parent::__construct($config, $security, $entityManager, $obscurityCodec);
    }

    #[Route('/-/oauth/start', name: 'app_oauth_start', methods: ['GET'], priority: 1)]
    public function index(Request $request, #[MapQueryParameter] string $provider): RedirectResponse
    {
        $providerImpl = $this->getProvider($request, $provider);
        $currentUser = $this->getCurrentUser();

        $session = $request->getSession();

        if ($currentUser) {
            // do not allow to re-link
            if ($currentUser->getTwitchOAuth() !== null) {
                return $this->redirect('/-/profile');
            }
        } else {
            $session->start();
        }

        // Call "getAuthorizationUrl" BEFORE we call "getState" as this generates the state
        $authUrl = $providerImpl->getAuthorizationUrl();

        $session->set('oauth2provider', $provider);
        $session->set('oauth2state', $providerImpl->getState());

        return $this->redirect($authUrl);
    }

    #[Route('/-/oauth/callback', name: 'app_oauth_callback', methods: ['GET'], priority: 1)]
    public function callback(
        Request                     $request,
        UserAuthenticatorInterface $authenticatorManager,
        #[MapQueryParameter] string $code,
        #[MapQueryParameter] string $state,
    ): Response
    {
        $currentUser = $this->getCurrentUser();
        $session = $request->getSession();
        $providerName = $session->get('oauth2provider');
        $oldState     = $session->get('oauth2state');
        $provider     = $this->getProvider($request, $providerName);

        if (!$code || !$state || $state !== $oldState || !$provider) {
            return $this->redirect($currentUser ? '/-/profile' : '/');
        }

        try {
            // try to get an access token
            $token       = $provider->getAccessToken('authorization_code', ['code' => $code]);
            /** @var \Vertisan\OAuth2\Client\Provider\TwitchHelixResourceOwner $userDetails */
            $userDetails = $provider->getResourceOwner($token);
        }
        catch (\Exception $e) {
            $message = 'Something unexpected happened when completing your login. Please try again later.';

            if ($currentUser) {
                $this->addErrorMsg($message);
                return $this->redirect('/-/profile');
            }

            $response = $this->render('index/login.twig', [
                'error_message' => $message,
                'error' => $message,
                'last_login' => '',
                'result' => null,
            ]);

            return $this->setCachingHeader($response, 'other');
        }

        $userRepo = $this->entityManager->getRepository(User::class);
        $identity = $userDetails->getId();
        $existing = $userRepo->findOneBy(['twitch_oauth' => $identity]);

        // !$exist === make new user (might flip the statement in the future
        if (!$existing) {
            if ($currentUser) {
                $user = $currentUser;
            } else {
                if ($this->exceedsMaxUsers()) {
                    $response = $this->render('index/login.twig', [
                        'error_message' => 'User registration is not available in this installation.',
                        'error' => 'User registration is not available in this installation.',
                        'last_login' => '',
                        'result' => null,
                    ]);
                    return $this->setCachingHeader($response, 'other');
                }

                $maxEvents = $this->config->getByKey('max_events', 10)->getValue();
                $defaultRole = $this->getParameter('horaro.default_role');

                $user = new User();
                $user->setLogin('oauth:twitch:'.$userDetails->getLogin());
                $user->setPassword(null);
                $user->setDisplayName($userDetails->getDisplayName());
                $user->setRole($defaultRole);
                $user->setMaxEvents($maxEvents);
                $user->setLanguage('en_us');

                $this->entityManager->persist($user);
            }

            // link the current account to the just authenticated Twitch account
            $user->setTwitchOAuth($identity);
            $this->entityManager->flush();

            // we're done for logged-in users
            if ($currentUser) {
                $this->addSuccessMsg('You have successfully linked your accounts.');
                return $this->redirect('/-/profile');
            }
        } else {
            if ($currentUser) {
                $this->addSuccessMsg('This account is already used by another Horaro account.');
                return $this->redirect('/-/profile');
            }

            if ($existing->getRole() === 'ROLE_GHOST') {
                $response = $this->render('index/login.twig', [
                    'error_message' => 'Your account has ben disabled.',
                    'error' => 'Your account has ben disabled.',
                    'last_login' => '',
                    'result' => null,
                ]);
                return $this->setCachingHeader($response, 'other');
            }

            $user = $existing;
        }

        $session->migrate(); // create new session ID (prevents session fixation)

        if (!$existing) {
            $this->addSuccessMsg('Welcome to Horaro, your account has been successfully created.');
        }

        // TODO: some day
        // $this->security->login()

        // auth, not sure if RememberMeBadge works, keep testing
        $authenticatorManager->authenticateUser($user, $this->authenticator, $request, [new RememberMeBadge()]);

        return $this->redirect('/');
    }

    private function getProvider(Request $request, string $providerName): ?AbstractProvider
    {
        $oauthConfig = $this->getParameter('oauth');

        if (!isset($oauthConfig[$providerName])) {
            return null;
        }

        $params = $oauthConfig[$providerName];

        // auto-determine the callback URL
        $params['redirectUri'] = $request->getSchemeAndHttpHost().'/-/oauth/callback';

        switch ($providerName) {
            case 'twitch':
            {
                return new TwitchHelixOAuthProvider($params);
            }
            default:
            {
                throw new \Exception('Invalid provider "'.$providerName.'" configured.');
            }
        }
    }
}
