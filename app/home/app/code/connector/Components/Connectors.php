<?php

namespace App\Connector\Components;

use function MongoDB\is_string_array;

class Connectors extends \App\Core\Components\Base
{

    /**
     * @param bool $userId
     * @param string $search
     * @return array
     */
    public function getAllConnectors($userId = false, $search = '')
    {
        if ($userId) {
            $connectors = [];
            foreach ($this->di->getConfig()->connectors->toArray() as $code => $connector) {
                if ($search == '') {
                    $connectors[$code] = $connector;
                    $connectors[$code]['installed'] = $this->checkIsInstalled($code, $userId);
                } else {
                    if (strpos($connector['title'], $search) !== false ||
                        strpos($connector['code'], $search) !== false) {
                        $connectors[$code] = $connector;
                        $connectors[$code]['installed'] = $this->checkIsInstalled($code, $userId);
                    }
                }
                //$connectors[$code]['image'] = $this->di->getUrl()->get($connectors[$code]['image']);
            }
            return $connectors;
        } else {
            return $this->di->getConfig()->connectors->toArray();
        }
    }

    /**
     * @param $code
     * @return mixed
     */
    public function getConnectorModelByCode($code)
    { 
        // print_r($this->di->getConfig()->connectors->get('amazon'));

        if($this->di->getConfig()->connectors->get($code)){
         
            return $this->di->getObjectManager()->get($this->di->getConfig()->connectors->$code->source_model);
        }
        else
            return false;
    }

    public function getShops($code,$userId,$getDetails = false){
        if($this->getConnectorModelByCode($code))
            return $this->getConnectorModelByCode($code)->getShops($userId,$getDetails);
        else
            return [];
    }
    /**
     * @param $code
     * @return mixed
     */
    public function getConnectorByCode($code)
    {
        return $this->di->getConfig()->connectors->get($code);
    }

    /**
     * @param array $array
     * @param $filters
     * @return array
     */
    public function filterConnectorCollection($array, $filters)
    {
        return array_filter($array, function ($data) use ($filters) {

            foreach ($filters as $key => $filter) {

                if (!is_array($filter)) {
                    if ($filter != $data[$key]) {
                        return false;
                    }
                } elseif (is_array($filter)) {
                    if (!isset($data[$key]) || !$this->checkConditionalValue($data[$key], $filter)) {
                        return false;
                    }
                }
            }

            return true;
        });

    }

    /**
     * @param $value
     * @param $filter
     * @return bool
     */
    public function checkConditionalValue($value, $filter)
    {
        switch ($filter['condition']) {
            case 'neq':
                if ($filter['value'] != $value) {
                    return true;
                }
                break;
            default:
                return false;
        }
    }

    /**
     * @param array $filters = ['key'=>'value','key2'=>'value','key3'=>['condition'=>'neq','value'='test']]
     * @param bool $userId
     * @return array
     */
    public function getConnectorsWithFilter($filters = [], $userId = false)
    {
        return $this->filterConnectorCollection($this->getAllConnectors($userId), $filters);
    }

    /**
     * @param $framework
     * @param $userId
     * @return int
     */
    public function checkIsInstalled($framework, $userId)
    {
        $installed = [];
        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $collection = $mongo->getCollectionForTable('user_details');

        $details = $collection->findOne(['user_id' => (string)$userId ]);
        if ( isset($details['shops']) ) {
            foreach( $details['shops'] as $value ) {
                if ( $value['marketplace'] === $framework ) {
                    $installed[] = $value;
                }
            }
        }
        return $installed;
        // $config = \App\Connector\Models\User\Connector::findFirst("code='{$framework}' AND user_id='{$userId}'");
        // if ($config) {
        //     return 1;
        // }
        // return 0;
    }
}
