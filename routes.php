<?php
use Tlokuus\LoginWithSocial\Components\SocialLogin;

Route::get('login_with/{provider}', array("as" => "tlokuus_loginwithsocial_callback", 'middleware' => ['web'], function ($provider) {
    if(!($page = Session::get(SocialLogin::session_key('after_callback_url')))){
        throw new ApplicationException("Unexpected social login callback");
    }

    $url = $page['url'] . '?' . http_build_query(Input::get()) . '&social_action=' . $page['action'] . '&provider=' . $provider;
    return Redirect::to($url);
}));