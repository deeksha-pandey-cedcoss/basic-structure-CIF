<?php

namespace App\Core\Models\User;

use App\Core\models\Base;

class Config extends Base
{
    protected $table = 'user_config_data';

    public function set($user_id, $path, $value, $framework = 'global')
    {
        $config = Config::findFirst("framework='{$framework}' AND path='{$path}' AND user_id='{$user_id}'");
        if ($config) {
            $status = $config->setValue($value)->save();
        } else {
            $newConfig = new Config;
            $status = $newConfig->setPath($path)->setFramework($framework)
                        ->setValue($value)->setUserId($user_id)->save();
        }
        return $status;
    }

    public function get($user_id, $framework = 'global')
    {
        $config = Config::find("framework='{$framework}' AND user_id='{$user_id}'");
        if ($config) {
            return $config->toArray();
        }
        return [];
    }

    public function getConfigByPath($user_id, $path, $framework = 'global')
    {
        $config = Config::findFirst("framework='{$framework}' AND path='{$path}' AND user_id='{$user_id}'");
        
        if ($config) {
            return $config->toArray();
        }
        return false;
    }

    public function removeConfigData($user_id, $path, $framework = 'global') {
        $config = Config::findFirst("framework='{$framework}' AND path='{$path}' AND user_id='{$user_id}'");
        if ($config) {
            return $config->delete();
        }
        return false;
    }


    public function setForShop($user_id, $shopId = 0, $path, $value, $framework = 'global')
    {
        $config = Config::findFirst("framework='{$framework}' AND path='{$path}' AND user_id='{$user_id}' AND shop_id={$shopId}");
        if ($config) {
            $status = $config->setValue($value)->save();
        } else {
            $newConfig = new Config;
            $status = $newConfig->setPath($path)->setFramework($framework)
                ->setValue($value)->setUserId($user_id)->setShopId($shopId)->save();
        }
        return $status;
    }

    public function getForShop($user_id, $shopId = 0, $framework = 'global')
    {
        $config = Config::find("framework='{$framework}' AND user_id='{$user_id}' AND shop_id={$shopId}");
        if ($config) {
            return $config->toArray();
        }
        return [];
    }

    public function getConfigByPathForShop($user_id, $shopId = 0, $path, $framework = 'global')
    {
        $config = Config::findFirst("framework='{$framework}' AND path='{$path}' AND user_id='{$user_id}' AND shop_id={$shopId}");
        if ($config) {
            return $config->toArray();
        }
        return false;
    }

    public function removeConfigDataForShop($user_id, $shopId = 0, $path, $framework = 'global') {
        $config = Config::findFirst("framework='{$framework}' AND path='{$path}' AND user_id='{$user_id}' AND shop_id={$shopId}");
        if ($config) {
            return $config->delete();
        }
        return false;
    }
}
