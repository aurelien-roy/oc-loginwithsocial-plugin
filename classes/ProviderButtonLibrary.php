<?php namespace Tlokuus\LoginWithSocial\Classes;

use October\Rain\Support\Collection;

class ProviderButtonLibrary extends Collection{
    use \October\Rain\Support\Traits\Singleton; 

    public function __construct()
    {
        parent::__construct();
        $btns = [
            'Amazon' => [
                'name' => 'Amazon',
                'fab' => 'amazon',
                'background_color' => '#FF9900',
                'text_color' => '#000'
            ],
            'Authentiq' => [
                'name' => 'Authentiq',
                'fab' => null,
                'background_color' => '#f26641',
                'text_color' => '#fff'
            ],
            'BitBucket' => [
                'name' => 'BitBucket',
                'fab' => 'bitbucket',
                'background_color' => '#205081',
                'text_color' => '#fff'
            ],
            'Blizzard' => [
                'name' => 'Blizzard',
                'fab' => 'battle-net',
                'background_color' => '#0566b0',
                'text_color' => '#fff'
            ],
            'Discord' => [
                'name' => 'Discord',
                'fab' => 'discord',
                'background_color' => '#7289DA',
                'text_color' => '#fff'
            ],
            'Disqus' => [
                'name' => 'Disqus',
                'fab' => null,
                'background_color' => '#2e9fff',
                'text_color' => '#fff'
            ],
            'Dribbble' => [
                'name' => 'Dribbble',
                'fab' => 'dribbble',
                'background_color' => '#444444',
                'text_color' => '#fff'
            ],
            'Facebook' => [
                'name' => 'Facebook',
                'fab' => 'facebook',
                'background_color' => '#3b5998',
                'text_color' => '#fff'
            ],
            'Foursquare' => [
                'name' => 'Foursquare',
                'fab' => 'foursquare',
                'background_color' => '#0732a2',
                'text_color' => '#fff'
            ],
            'GitHub' => [
                'name' => 'GitHub',
                'fab' => 'github',
                'background_color' => '#333',
                'text_color' => '#fff'
            ],
            'GitLab' => [
                'name' => 'GitLab',
                'fab' => 'gitlab',
                'background_color' => '#fc6d26',
                'text_color' => '#fff'
            ],
            'Google' => [
                'name' => 'Google',
                'fab' => 'google',
                'background_color' => '#ea4335',
                'text_color' => '#fff'
            ],
            'Instagram' => [
                'name' => 'Instagram',
                'fab' => 'instagram',
                'background_color' => '#c13584',
                'text_color' => '#fff'
            ],
            'LinkedIn' => [
                'name' => 'LinkedIn',
                'fab' => 'linkedin',
                'background_color' => '#0077b5',
                'text_color' => '#fff'
            ],
            'Mailru' => [
                'name' => 'Mail.Ru',
                'fab' => null,
                'background_color' => '#168de2',
                'text_color' => '#fff'
            ],
            'MicrosoftGraph' => [
                'name' => 'Microsoft',
                'fab' => 'microsoft',
                'background_color' => '#00a1f1',
                'text_color' => '#fff'
            ],
            'Odnoklassniki' => [
                'name' => 'Odnoklassniki',
                'fab' => 'odnoklassniki',
                'background_color' => '#ed812b',
                'text_color' => '#fff'
            ],
            'OpenID' => [
                'name' => 'OpenID',
                'fab' => 'openid',
                'background_color' => '#000',
                'text_color' => '#fff'
            ],
            'Paypal' => [
                'name' => 'PayPal',
                'fab' => 'paypal',
                'background_color' => '#003087',
                'text_color' => '#fff',
            ],
            'Reddit' => [
                'name' => 'Reddit',
                'fab' => 'reddit',
                'background_color' => '#ff4500',
                'text_color' => '#fff'
            ],
            'Spotify' => [
                'name' => 'Spotify',
                'fab' => 'spotify',
                'background_color' => '#1db954',
                'text_color' => '#fff'
            ],
            'StackExchange' => [
                'name' => 'StackExchange',
                'fab' => 'stack-exchange',
                'background_color' => '#f48024',
                'text_color' => '#fff'
            ],
            'Steam' => [
                'name' => 'Steam',
                'fab' => 'steam',
                'background_color' => '#000',
                'text_color' => '#fff'
            ],
            'SteemConnect' => [
                'name' => 'steemconnect',
                'fab' => null,
                'background_color' => '#1500ff',
                'text_color' => '#fff'
            ],
            'Tumblr' => [
                'name' => 'Tumblr',
                'fab' => 'tumblr',
                'background_color' => '#35465c',
                'text_color' => '#fff'
            ],
            'TwitchTV' => [
                'name' => 'Twitch',
                'fab' => 'twitch',
                'background_color' => '#6441a5',
                'text_color' => '#fff'
            ],
            'Twitter' => [
                'name' => 'Twitter',
                'fab' => 'twitter',
                'background_color' => '#00acee',
                'text_color' => '#fff'
            ],
            'Vkontakte' => [
                'name' => 'VKontakte',
                'fab' => 'vk',
                'background_color' => '#45668e',
                'text_color' => '#fff'
            ],
            'WeChat' => [
                'name' => 'WeChat',
                'fab' => 'weixin',
                'background_color' => '#7bb32e',
                'text_color' => '#fff'
            ],
            'WeChatChina' => [
                'name' => 'WeChat',
                'fab' => 'weixin',
                'background_color' => '#7bb32e',
                'text_color' => '#fff'
            ],
            'WindowsLive' => [
                'name' => 'Microsoft',
                'fab' => 'microsoft',
                'background_color' => '#00a1f1',
                'text_color' => '#fff'
            ],
            'Wordpress' => [
                'name' => 'Wordpress',
                'fab' => 'wordpress',
                'background_color' => '#016087',
                'text_color' => '#fff'
            ],
            'Yahoo' => [
                'name' => 'Yahoo',
                'fab' => 'yahoo',
                'background_color' => '#410093',
                'text_color' => '#fff'
            ],
            'Yandex' => [
                'name' => 'Yandex',
                'fab' => 'yandex',
                'background_color' => '#ffcc00',
                'text_color' => '#000'
            ]
        ];

        foreach($btns as $k=>$btn){
            $this->put($k, $btn);
        }
    }
}