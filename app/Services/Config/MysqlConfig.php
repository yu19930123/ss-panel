<?php

namespace App\Services\Config;

use App\Models\ConfigModel as ConfigModel;

class MysqlConfig implements ModelConfigInterface
{
    private $db;

    /**
     * MysqlConfig constructor.
     */
    public function __construct()
    {
        $this->db = new ConfigModel();
    }

    /**
     * @param $key
     * @return null|string
     */
    public function get($key)
    {
        $m = $this->getByKey($key);
        if ($m) {
            return $m->value;
        }
        return '';
    }

    private function getByKey($key)
    {
        $m = ConfigModel::where('key', $key)->first();

        return $m;
    }

    /**
     * @param $key
     * @param $value
     *
     * @return bool
     */
    public function set($key, $value)
    {
        $m = $this->getByKey($key);
        if (!$m) {
            $m = new ConfigModel();
        }
        $m->key = $key;
        $m->value = $value;

        return $m->save();
    }
}
