<?php

namespace App\Services;

use App\Models\PlatformMarketAuthorization;
use Wish\WishAuth;
use Wish\WishClient;
use Config;

class ApiWishAuthService 
{
    protected $mwsName = 'wish-mws';
    protected $stores;

    public function __construct()
    {
        $this->stores = Config::get($this->mwsName.'.store');
    }

    public function initWishClient($storeName)
    {   
        if(isset($this->stores[$storeName])){
            $marketPlaceToken = PlatformMarketAuthorization::MarketPlaceToken($storeName)->first();
            $currentDate = strtotime(date("y-m-d"));
            $expireDate = strtotime($marketPlaceToken->expire_date);
            if($expireDate - $currentDate <= 1){
                $accessToken = $this->refreshWishToken($storeName,$marketPlaceToken);
            }else{
                $accessToken = $marketPlaceToken->access_token;
            }
            $wishClient = new WishClient($accessToken,'prod');
            return $wishClient;
        }else {
            throw new Exception('Config file does not exist or cannot be read!');
        }
    }

    public function getTokenByAuthorizationCode($storeName,$authorizationCode,$url)
    {
        if(isset($this->stores[$storeName])){
            $auth = new WishAuth($this->stores[$storeName]['client_id'],$this->stores[$storeName]['client_secret'],'prod');
            $response = $auth->getToken($authorizationCode,$url);
            $accessToken = $response->getData()->access_token;
            $refreshToken = $response->getData()->refresh_token;
        }else {
            throw new Exception('Config file does not exist or cannot be read!');
        }
    }

    public function refreshWishToken($storeName,$marketPlaceToken)
    {
        if(isset($this->stores[$storeName])){
            $auth = new WishAuth($this->stores[$storeName]['client_id'],$this->stores[$storeName]['client_secret'],'prod');
            $response = $auth->refreshToken($marketPlaceToken->refresh_token);
            if($accessToken = $response->getData()->access_token){
                $marketPlaceToken->access_token = $accessToken;
                $marketPlaceToken->expire_date = date('Y-m-d',strtotime('+29 day'));
                $marketPlaceToken->save();
                return $accessToken;
            }
        }else {
            throw new Exception('Config file does not exist or cannot be read!');
        }
    }
}
