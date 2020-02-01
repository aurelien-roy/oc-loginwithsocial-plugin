<?php
namespace Tlokuus\LoginWithSocial;

use System\Classes\PluginBase;
use Rainlab\User\Models\User;
use System\Classes\SettingsManager;
use Backend;

class Plugin extends PluginBase
{

    private $providers;
    private $enabledProviders = ['Discord'];
    private $loginUrl = 'register';

    public $require = ['RainLab.User', 'Tlokuus.DisablePassword'];

    public function pluginDetails()
    {
        return [
            'name' => 'Login With Social',
            'description' => 'Provide third-party auth providers',
            'author' => 'Tlokuus',
            'icon' => 'icon-users'
        ];
    }

    public function registerComponents()
    {
        return [
            'Tlokuus\LoginWithSocial\Components\SocialLogin' => 'socialLogin',
            'Tlokuus\LoginWithSocial\Components\ContinueRegistrationForm' => 'continueRegistrationForm'
        ];
    }

    public function boot()
    {
        User::extend(function($model) {
            $model->hasMany['linked_social_accounts'] = ['\Tlokuus\LoginWithSocial\Models\LinkedSocialAccount'];
        });
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label'       => 'tlokuus.loginwithsocial::backend.settings.menu_label',
                'description' => 'tlokuus.loginwithsocial::backend.settings.menu_description',
                'category'    => SettingsManager::CATEGORY_USERS,
                'icon'        => 'icon-users',
                'class'       => 'Tlokuus\LoginWithSocial\Models\Settings',
                'order'       => 600,
                'keywords'    => 'login signin sign with user users social google facebook',
                'permissions' => ['rainlab.users.access_settings']
            ]
        ];
    }

    public function registerFormWidgets()
    {
        return [
            'Tlokuus\LoginWithSocial\FormWidgets\ToggleList' => 'togglelist',
        ];
    }
}