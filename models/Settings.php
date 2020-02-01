<?php namespace Tlokuus\LoginWithSocial\Models;

use Tlokuus\LoginWithSocial\Classes\AuthProviderManager;
use Model;

class Settings extends Model
{
    public $implement = [
        \System\Behaviors\SettingsModel::class
    ];

    public $settingsCode = 'loginwithsocial_settings';
    public $settingsFields = 'fields.yaml';


    public function getProvidersOptions()
    {
        $apm = AuthProviderManager::instance();
        return collect($apm->getProviders())
            ->keyBy('name')
            ->map(function($p) use ($apm){
                $is_configured = $apm->isConfigured($p['name']);
                $vars = $apm->getVariableNames($p['name']);
                $url = route('tlokuus_loginwithsocial_callback', ['provider' => $p['name']]);
                
                return [$p['name'], [
                    'comment' => $is_configured ? $url : 'Not configured',
                    'disabled' => !$is_configured,
                    'tooltip' => $is_configured ? 'This is the callback/redirect URL you must give to the provider if required.' : "Add {$vars['id']} and {$vars['secret']} variables into your .env file to enable this provider.",
                ]];
            })->sortBy(function($k){
                return [$k[1]['disabled'], $k[0]];
            });

    }

}