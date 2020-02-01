<?php namespace Tlokuus\LoginWithSocial\Models;

use Model;
use ReflectionClass;

class LinkedSocialAccount extends Model
{
    public $timestamps = true;

    public $table = 'tlokuus_linked_social_accounts';

    public $jsonable = ['profile_data'];

    public $belongsTo = [
        'user' => 'RainLab\User\Models\User',
    ];

    /**
     * Retrieves a linked social account from an HybridAuth profile
     * 
     * @param \HybridAuth\User\Profile $social_profile
     * @return
     */
    public static function findFromProfile($provider, $identifier)
    {
        return self::where('provider', $provider)
            ->where('identifier', $identifier)
            ->first();
    }

    public static function link($user, $provider, $profile)
    {
        $link = new self();
        $link->provider = $provider;
        $link->identifier = $profile->identifier;
        $link->profile_data = $profile;
        $link->user = $user;
        $link->save();
    }

    public static function unlink($user, $provider)
    {
        $user->linked_social_accounts()->where('provider', $provider)->delete();
    }

    public static function hasLinkedAccount($user, $provider)
    {
        return boolval(
            $user->linked_social_accounts()->where('provider', $provider)->count()
        );
    }
}