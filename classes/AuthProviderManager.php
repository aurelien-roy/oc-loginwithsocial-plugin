<?php 
namespace Tlokuus\LoginWithSocial\Classes;

use RuntimeException;
use Hybridauth\Hybridauth;
use Tlokuus\LoginWithSocial\Models\Settings;

class AuthProviderManager
{
    use \October\Rain\Support\Traits\Singleton;

    private $availableProviders;
    private $enabledProviders = [];

    public $env_key_id = "%s_ID";
    public $env_key_secret = "%s_SECRET";

    public function __construct()
    {
        $this->scanAvailableProviders();   
        $this->enabledProviders = Settings::get('providers');
    }

    public function scanAvailableProviders()
    {
        $this->availableProviders = [];

        // Load Hybridauth built-in providers
        $hybridauthPath = dirname((new \ReflectionClass(Hybridauth::class))->getFileName());
        $providerDirectory = $hybridauthPath . '/Provider/';
        
        $fs = new \FilesystemIterator($providerDirectory);
        foreach ($fs as $file) {
            if (!$file->isDir()) {
                $class_name = strtok($file->getFilename(), '.');

                // The plugin provides no support for OpenID
                if(strpos(strtolower($class_name), 'openid')) {
                    continue;
                }

                $provider_name = $class_name;
                $provider_path = '\Hybridauth\Provider\\' . $class_name;
                $this->availableProviders[$provider_name] = $provider_path;
            }
        }

    }

    public function getVariableNames($provider_name) {
        return [
            'id' => sprintf($this->env_key_id, strtoupper($provider_name)),
            'secret' => sprintf($this->env_key_secret, strtoupper($provider_name))
        ];
    }

    public function isConfigured($provider_name) {
        return $this->getProviderCredentials($provider_name, false) !== false;
    }

    public function getProviderCredentials($provider_name, $throw_on_error = true)
    {
        $provider_name = strtoupper($provider_name);

        $env_keys = $this->getVariableNames($provider_name);

        $credentials = array_map(function($k){ return env($k); }, $env_keys);

        if(!($credentials['id'] && $credentials['secret'])){
            if($throw_on_error) {
                throw new RuntimeException("Keys not availale for provider $provider_name. You need to define ${env_keys['id']} and ${env_keys['secret']} in your .env file.");
            }
            return false;
        }

        return $credentials;
    }

    public function resolveProvider($provider)
    {
        if (!$this->isAvailable($provider)){
            throw new RuntimeException("Unknown auth provider: $provider");
        }

        if (!$this->isEnabled($provider)){
            throw new RuntimeException("Auth provider is not enabled: $provider");
        }

        return $this->availableProviders[$provider];
    }

    public function isEnabled($provider)
    {
        return $this->isAvailable($provider) && in_array($provider, $this->enabledProviders);
    }

    public function isAvailable($provider)
    {
        return array_key_exists($provider, $this->availableProviders);
    }

    public function getProviders()
    {
        return collect(array_map(
            function($p){
                return ['name' => $p, 'enabled' => in_array($p, $this->enabledProviders)];
            },
            array_sort(array_keys($this->availableProviders))
        ));
    }

    public function getEnabledProviders()
    {
        return $this->enabledProviders;
    }

}