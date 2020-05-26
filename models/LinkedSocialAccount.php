<?php namespace Tlokuus\LoginWithSocial\Models;

use ApplicationException;
use Model;
use Lang;
use Illuminate\Database\QueryException;

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
        try {
            $link = new self();
            $link->provider = $provider;
            $link->identifier = $profile->identifier;
            $link->profile_data = $profile;
            $link->user = $user;
            $link->save();
        } catch (QueryException $ex) {
            throw new ApplicationException(Lang::get('tlokuus.loginwithsocial::frontend.linked_to_other', ['provider' => $provider, 'app' => env('APP_NAME')]));
        }
    }

    public static function unlink($user, $provider)
    {
        $account = $user->linked_social_accounts()->where('provider', $provider)->first();
        
        if($account) {
            $account->delete();
        }
    }

    public static function hasLinkedAccount($user, $provider)
    {
        return boolval(
            $user->linked_social_accounts()->where('provider', $provider)->count()
        );
    }

    public static function getLinkedAccount($user, $provider)
    {
        return $user->linked_social_accounts()->where('provider', $provider)->first();
    }
}