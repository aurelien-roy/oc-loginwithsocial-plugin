<?php namespace Tlokuus\LoginWithSocial\Components;

use Auth;
use Event;
use Flash;
use Input;
use Lang;
use Redirect;
use ApplicationException;
use Session;
use URL;

use Carbon\Carbon;
use Cms\Classes\Page;
use RainLab\User\Components\Account as AccountComponent;
use RainLab\User\Models\User as UserModel;
use ReflectionClass;
use Tlokuus\LoginWithSocial\Classes\AuthProviderManager;
use Tlokuus\LoginWithSocial\Models\LinkedSocialAccount;
use Tlokuus\LoginWithSocial\Classes\ProviderButtonLibrary;
use Tlokuus\LoginWithSocial\Classes\SocialAuthManager;
use Tlokuus\LoginWithSocial\Models\Settings as SocialSettings;

class SocialLogin extends AccountComponent
{

    const SESSION_PREFIX = 'tlokuus_loginwithsocial::';

    private $provider;

    public function componentDetails()
    {
        return [
            'name' => 'Social Login',
            'description' => 'Adds login with social capability to the page.'
        ];
    }

    public function defineProperties()
    {
        return [
            'redirect' => [
                'title'       => /*Redirect to*/'rainlab.user::lang.account.redirect_to',
                'description' => /*Page name to redirect to after update, sign in or registration.*/'rainlab.user::lang.account.redirect_to_desc',
                'type'        => 'dropdown',
                'default'     => '/'
            ],

            'registrationPage' => [
                'title'       => 'Continue registration page',
                'description' => '"Continue registration" page to redirect when additional details are required after social login. The selected page should have the "Continue Register Form" component.',
                'type'        => 'dropdown',
                'default'     => ''
            ]
        ];
    }

    public function init()
    {
        $this->page['socialButtons'] = $this->socialButtons();
        $this->page['currentUser'] = Auth::user();
    }

    public function onRun()
    {
        if(($action = Input::get('social_action')) && ($provider_name = Input::get('provider'))){
            try {
                if($action == 'login') {
                    return $this->loginWith($provider_name, true);
                } elseif($action == 'link') {
                    return $this->link($provider_name, true);
                }
            } catch (ApplicationException $ex) {
                Flash::error($ex->getMessage());
                return $this->redirectFailure();
            }
        }
    }

    public function getRedirectOptions()
    {
        return $this->getPageOptions();
    }

    public function getRegistrationPageOptions()
    {
        return $this->getPageOptions();
    }

    private function getPageOptions()
    {
        $pages = Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
        return array_prepend($pages, '# Do not redirect', null);
    }

    public function loginWith($provider_name, $is_callback = false) {
        Session::put(self::session_key('ga_reset_referrer'), 1);

        if(Auth::getUser() && !Auth::getUser()->is_guest) {
            Flash::success(Lang::get('tlokuus.loginwithsocial::frontend.already_logged_in', ['provider' => ucfirst($provider_name)]));
            return $this->redirectFailure();
        }

        if(!$is_callback) {
            Session::put(self::session_key('after_callback_url'), [
                'action' => 'login',
                'url' => $this->pageUrl($this->page->id)
            ]);
        } else {
            Session::forget(self::session_key('after_callback_url'));
        }

        return $this->authenticateWith($provider_name, function($provider) {
            return $this->onAuthSuccess($provider);
        });
    }

    public function link($provider_name, $is_callback = false) {
        if(!($user = Auth::getUser()) || $user->is_guest) {
            return $this->redirectFailure();
        }

        if ($is_callback) {
            $session_data = Session::get(self::session_key('after_callback_url'));
            Session::forget(self::session_key('after_callback_url'));
            
            if ($session_data['user'] !== $user->id) {
                throw new ApplicationException(Lang::get('tlokuus.loginwithsocial::frontend.auth_error', ['provider' => $provider_name]));
            }
        }

        $this->failIfHasLinkedAccount($user, $provider_name);

        if(!$is_callback) {
            Session::put(self::session_key('after_callback_url'), [
                'action' => 'link',
                'url' => $this->pageUrl($this->page->id),
                'user' => $user->id
            ]);
        }
        
        return $this->authenticateWith($provider_name, function($provider) use ($provider_name, $user){
            $provider->disconnect();
            $this->failIfHasLinkedAccount($user, $provider_name);
            LinkedSocialAccount::link($user, $provider_name, $provider->getUserProfile());
            
            return Redirect::to($this->pageUrl($this->page->id));
        });
    }

    public function unlink($provider_name) {
        if(!($user = Auth::getUser()) || $user->is_guest) {
            return $this->redirectFailure();
        }

        $this->failIfNoLinkedAccount($user, $provider_name);

        LinkedSocialAccount::unlink($user, $provider_name);
    }
    
    private function failIfHasLinkedAccount($user, $provider_name) {
        if(LinkedSocialAccount::hasLinkedAccount($user, $provider_name)){
            throw new ApplicationException(Lang::get('tlokuus.loginwithsocial::frontend.already_linked', ['provider' => $provider_name]));
        }
    }

    private function failIfNoLinkedAccount($user, $provider_name) {
        if(!LinkedSocialAccount::hasLinkedAccount($user, $provider_name)){
            throw new ApplicationException(Lang::get('tlokuus.loginwithsocial::frontend.no_account_linked', ['provider' => $provider_name]));
        }
    }

    public function authenticateWith($provider_name, $callback){

        $apm = AuthProviderManager::instance();
        $provider_class = $apm->resolveProvider($provider_name);

        $this->provider = new $provider_class([
            'keys' => $apm->getProviderCredentials($provider_name),
            'callback' => URL::route('tlokuus_loginwithsocial_callback', ['provider' => $provider_name])
        ]);

        try {
            $redirect_response = null;
            \Hybridauth\HttpClient\Util::setRedirectHandler(function($url) use (&$redirect_response){
                $redirect_response = Redirect::to($url);
            });

            $this->provider->authenticate();
            if($redirect_response){
                return $redirect_response;
            }else{
                return $callback($this->provider);
            }
        } catch (\Hybridauth\Exception\ExceptionInterface $ex) {
            $user_cancelled = filter_input(INPUT_GET, 'error', FILTER_SANITIZE_SPECIAL_CHARS) == 'access_denied';

            if(!$user_cancelled){
                Flash::error(Lang::get('tlokuus.loginwithsocial::frontend.auth_error', ['provider' => ucfirst($provider_name)]));
            }

            return $this->redirectFailure();
        }
    }

    public function onLoginwith() {
        
        if (!Input::get('loginwith')) {
            return;
        }

        return $this->loginWith(Input::get('loginwith'));
    }

    public function onLink() {
        if (!Input::get('provider')) {
            return;
        }

        return $this->link(Input::get('provider'));
    }

    public function onUnlink() {
        if (!Input::get('provider')) {
            return;
        }

        return $this->unlink(Input::get('provider'));
    }

    private function onAuthSuccess($provider)
    {
        $provider_name = $this->getProviderName($provider);
        $social_profile = $provider->getUserProfile();
        $email = $social_profile->emailVerified ?: $social_profile->email;
        $provider->disconnect();

        try {
            $user = $this->findUser($email, $provider_name, $social_profile);
        } catch (ApplicationException $ex) {
            Flash::error($ex->getMessage());
            return $this->redirectFailure();
        }
        
        // User found from social account. Logging in.
        if ($user) {
            SocialAuthManager::instance()->loginUser($user);
            return $this->redirectSuccess();
        }

        $loginAttribute = (new AccountComponent())->loginAttribute();
        $registration_automatic = $loginAttribute == 'email' && !SocialSettings::get('always_show_registration');

        $data = [
            'email' => $email,
            'name' => $social_profile->displayName
        ];

        // Opportunity for extensions to add/edit/remove partial registration data
        if(Event::fire('tlokuus.loginwithsocial.beforeAttemptRegister', [&$data, $social_profile, $provider_name], true) === false) {
            $registration_automatic = false;
        }

        // Try to register without asking additional data to the user
        if ($registration_automatic) {
            $activationCallback = function($user) { $this->sendActivationEmail($user); };
            $throttleCallback = function() { $this->isRegisterThrottled(); };
            if(SocialAuthManager::instance()->attemptRegisterUser($data, $social_profile, $provider_name, $activationCallback, $throttleCallback)) {
                return $this->redirectSuccess();
            }
        }

        // Fallback: Redirect to a partial registration page to ask corrections or additionnal information
        Session::put(
            self::session_key('continue_registration'),
            [
                'provider' => $provider_name,
                'identifier' => $social_profile->identifier,
                'profile' => $social_profile,
                'reg_data' => $data,
                'verified' => $data['email'] == $social_profile->emailVerified,
                'expires' => Carbon::now()->addMinutes(30)
            ]
        );

        return $this->redirectRegistration();
    }

    protected function findUser($email, $provider_name, $social_profile)
    {
        // Social account has already been linked to a user account
        if ($profile = LinkedSocialAccount::findFromProfile($provider_name, $social_profile->identifier)) {
            return $profile->user;
        }
        
        // Social account e-mail matches account on the website: Try auto-link
        if ($email && $user = UserModel::findByEmail($email)) {

            // Account on the website is already linked to another account of the same social provider
            if (LinkedSocialAccount::hasLinkedAccount($user, $provider_name)) {
                throw new ApplicationException(
                    Lang::get('tlokuus.loginwithsocial::frontend.autolink_duplicate_error', [
                        'provider' => $provider_name,
                        'email' => $email
                    ])
                );
            }

            // Auto-link is disabled or social provider do not guarantee e-mail ownership
            if (!SocialSettings::get('autolink') || $email != $social_profile->emailVerified) {
                $provider_alt = $this->getUsableProvider($user);
                throw new ApplicationException(
                    Lang::get('tlokuus.loginwithsocial::frontend.' . ($provider_alt ? 'autolink_disabled_error' : 'autolink_no_alternative_error'), [
                        'provider' => $provider_name,
                        'alt_provider' => $provider_alt,
                        'email' => $email
                    ])
                );
            }
            
            // Auto-link
            LinkedSocialAccount::link($user, $provider_name, $social_profile);
            return $user;
        }
        
        return null;
    }

    public function redirectRegistration()
    {
        return Redirect::to(
            $this->pageUrl(
                $this->property('registrationPage')
            )
        );   
    }

    public function redirectFailure()
    {
        return Redirect::to(
            $this->pageUrl(null)
        );
    }

    public function redirectSuccess()
    {
        $property = trim((string) $this->property('redirect'));

        // No redirect
        if (!$property) {
            return;
        }

        $redirectUrl = post('redirect', $this->pageUrl($property) ?: $property);
        return Redirect::intended($redirectUrl);
    }

    private function getUsableProvider($user)
    {
        if (!$user->tlokuus_disablepassword_is_disabled) {
            return 'e-mail';
        }

        $linked = $user->linked_social_accounts->first();

        if ($linked) {
            return $linked->provider;
        }

        return null;
    }

    protected function getProviderName($provider)
    {
        return (new ReflectionClass($provider))->getShortName();
    }

    public function socialButtons()
    {
        $library = ProviderButtonLibrary::instance();
        return $this->enabledProviders()->mapWithKeys(function($k) use($library){
            return [$k => $library->get($k) ?: [
                'name' => $k
            ]];
        });
    }

    public function enabledProviders()
    {
        return collect(AuthProviderManager::instance()->getEnabledProviders());
    }

    public static function session_key($key)
    {
        return self::SESSION_PREFIX . $key;
    }
}