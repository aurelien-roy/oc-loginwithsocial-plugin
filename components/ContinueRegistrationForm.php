<?php namespace Tlokuus\LoginWithSocial\Components;

use Auth;
use Flash;
use Input;
use Lang;
use Redirect;
use Session;

use Carbon\Carbon;
use Cms\Classes\Page;

use RainLab\User\Models\Settings as UserSettings;
use RainLab\User\Components\Account as AccountComponent;
use Tlokuus\LoginWithSocial\Classes\SocialAuthManager;

class ContinueRegistrationForm extends AccountComponent
{

    const SESSION_PREFIX = 'tlokuus_loginwithsocial::';

    public function componentDetails()
    {
        return [
            'name' => 'Social Login',
            'description' => 'Adds login with social capability to the page.'
        ];
    }

    public function defineProperties()
    {
        return array_merge([
            'loginPage' => [
                'title'       => 'Login page',
                'description' => 'Login page to redirect when an error occured.',
                'type'        => 'dropdown',
                'default'     => ''
            ]
            ], parent::defineProperties());
    }

    public function onRun()
    {
        if (Auth::getUser() && !Auth::getUser()->is_guest) {
            return $this->redirectSuccess();
        }

        if (!$partial_reg = $this->getPartialRegistrationData()) {
            Flash::error(Lang::get('tlokuus.loginwithsocial::frontend.auth_error_noname'));
            return $this->redirectLogin();
        }

        $this->page['hasInitiatedRegistration'] = true;
        $this->page['initiatedProfile'] = $partial_reg['reg_data'];
        $this->page['providerName'] = $partial_reg['provider'];
    }

    public function getRedirectOptions()
    {
        return $this->getPageOptions();
    }

    public function getLoginPageOptions()
    {
        return $this->getPageOptions();
    }

    private function getPageOptions()
    {
        $pages = Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
        return array_prepend($pages, "# Do not redirect", null);
    }

    public function onFinishRegister()
    {
        if (!$partialReg = $this->getPartialRegistrationData()) {
            Flash::error(Lang::get('tlokuus.loginwithsocial::frontend.auth_error_noname'));
            return $this->redirectLogin();
        }

        $formdata = Input::post();
        $emailChanged = (!array_key_exists('email', $formdata) || $formdata['email'] != $partialReg['reg_data']['email']);

        SocialAuthManager::instance()->registerUser(
            $formdata,
            !$emailChanged && $partialReg['verified'],
            $partialReg['provider'],
            $partialReg['profile'],
            function($user) { $this->sendActivationEmail($user); },
            function() { $this->isRegisterThrottled(); },
        );

        $this->forgetPartialRegistrationData();
        return $this->redirectSuccess();
    }

    public function redirectLogin()
    {
        return Redirect::to(
            $this->pageUrl(
                $this->property('loginPage')
            )
        );   
    }

    public function redirectFailure()
    {
        return Redirect::to(
            $this->controller->pageUrl(null)
        );
    }

    public function redirectSuccess()
    {
        $property = trim((string) $this->property('redirect'));

        // No redirect
        if ($property === '0') {
            return;
        }

        $redirectUrl = post('redirect', $this->pageUrl($property) ?: $property);
        return Redirect::intended($redirectUrl);
    }

    

    public function getPartialRegistrationData()
    {
        $key = $this->session_key('continue_registration');
        if (!$data = Session::get($key)) {
            return null;
        }

        if (Carbon::now() > $data['expires']) {
            Session::forget($key);
            return null;
        }

        return $data;
    }

    public function forgetPartialRegistrationData()
    {
        $key = $this->session_key('continue_registration');
        Session::forget($key);
    }

    public function loginAttribute()
    {
        return UserSettings::get('login_attribute', UserSettings::LOGIN_EMAIL);
    }

    public static function session_key($key)
    {
        return self::SESSION_PREFIX . $key;
    }
}