<?php namespace Tlokuus\LoginWithSocial\Classes;

use ApplicationException;
use Auth;
use Db;
use Event;
use Flash;
use Lang;
use October\Rain\Database\ModelException;
use Request;
use ValidationException;


use Tlokuus\LoginWithSocial\Models\LinkedSocialAccount;
use October\Rain\Auth\AuthException;
use RainLab\User\Models\Settings as UserSettings;

class SocialAuthManager {
    use \October\Rain\Support\Traits\Singleton; 

    public function loginUser($user)
    {
        if ($user->isBanned()) {
            throw new AuthException('rainlab.user::lang.account.banned');
        }

        Auth::login($user);
    }

    public function attemptRegisterUser($data, $social_profile, $provider_name, $send_activation_mail_callback = null, $throttle_check_callback = null) {
        try {
            SocialAuthManager::instance()->registerUser(
                $data,
                $data['email'] == $social_profile->emailVerified,
                $provider_name,
                $social_profile,
                $send_activation_mail_callback,
                $throttle_check_callback
            );

            return true;
        } catch (ValidationException|ModelException $ex) {
            return false;
        }
    }

    public function registerUser($data, $email_verified = false, $linked_provider = null, $linked_profile = null, $send_activation_mail_callback = null, $throttle_check_callback = null)
    {

        if (!UserSettings::get('allow_registration', true)) {
            throw new ApplicationException(Lang::get('rainlab.user::lang.account.registration_disabled'));
        }

        if ($throttle_check_callback()) {
            throw new ApplicationException(Lang::get('rainlab.user::lang.account.registration_throttled'));
        }

        // Record IP address
        if ($ipAddress = Request::ip()) {
            $data['created_ip_address'] = $data['last_ip_address'] = $ipAddress;
        }
        
        Event::fire('rainlab.user.beforeRegister', [&$data]);

        $requireActivation = UserSettings::get('require_activation', true);
        $automaticActivation = UserSettings::get('activate_mode') == UserSettings::ACTIVATE_AUTO;
        $userActivation = UserSettings::get('activate_mode') == UserSettings::ACTIVATE_USER;

        // Bypass activation if e-mail has been verified by identity provider
        $activated = $email_verified || $automaticActivation;

        if (!array_key_exists('password', $data)) {
            $data['tlokuus_disablepassword_is_disabled'] = true;
        }

        $user = null;
        Db::transaction(function () use(&$user, $data, $activated, $linked_provider, $linked_profile){
            $user = Auth::register($data, $activated);

            // Link social account
            if ($linked_provider) {
                LinkedSocialAccount::link($user, $linked_provider, $linked_profile);
            }
        });

        if(!$user) {
            return;
        }

        Event::fire('rainlab.user.register', [$user, $data]);

        // Activation by the user: send an e-mail
        if (!$activated && $userActivation && $send_activation_mail_callback) {
            $send_activation_mail_callback($user);
            Flash::success(Lang::get(/*An activation email has been sent to your email address.*/'rainlab.user::lang.account.activation_email_sent'));
        }

        // Activated or no activation required: Login the user
        if ($activated || !$requireActivation) {
            Auth::login($user);
        }
    }
}