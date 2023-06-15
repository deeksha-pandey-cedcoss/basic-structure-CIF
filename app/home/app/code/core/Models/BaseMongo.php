<?php

namespace App\Core\Models;

use Phalcon\Mvc\Model;

use Phalcon\Mvc\Model\Resultset\Simple as Resultset;

class BaseMongo extends Model
{
    const RANGE = 7;
    const END_FROM = 6;
    const START_FROM = 5;
    const IS_EQUAL_TO = 1;
    const IS_CONTAINS = 3;
    const IS_NOT_EQUAL_TO = 2;
    const IS_NOT_CONTAINS = 4;
    protected $table = ''; 
    protected $collectionName = false;
    protected $currentTransaction = false;
    protected $implicit = false;
    protected $di;
    protected $read_connection_service;
    protected $isGlobal = false;

    //HDFCINGBB
    public function initialize()
    {

        $this->setSource($this->table);
        $this->initializeDb($this->getMultipleDbManager()->getDb());
    }



    /**
     * Initialize the model
     */
    public function onConstruct()
    {
        $this->di = $this->getDi();
        $token = $this->di->getRegistry()->getDecodedToken();
        
        $this->setSource($this->table);



        $this->initializeDb($this->getMultipleDbManager()->getDb());
    }

    /**
     * @return \MongoDB\Collection
     */
    public function getPhpCollection(){
        $dbHandle = $this->getMultipleDbManager()->getDb().'_mongo';
        $database = $this->di->getConfig()->databases->$dbHandle;
        $dsn = $database['host'];

        $mongo = new \MongoDB\Client($dsn,array("username" => $database['username'], "password" =>$database['password']));
        $db = $database['dbname'];
        $table = $this->getSource();
        return $mongo->$db->$table;
    }
    /*public function setCollection($collectionName){
        $this->collectionName = $collectionName;
        return $this;
    }*/
    /**
     * @return mixed
     */
    public function getCollection( $collectionName = false)
    {
        if($collectionName){
            return $this->getConnection()->selectCollection($collectionName);
        }
        elseif($this->collectionName){
            return $this->getConnection()->selectCollection($this->collectionName);
        }
        else{
            return $this->getConnection()->selectCollection($this->getSource());
        }


    }

    public function getConnection(){
        return $this->getDbConnection();
    }
    /**
     * @param $counterKey
     * @param $merchantId
     * @return mixed
     */
    public function getCounter($counterKey, $merchantId = false)
    {
        if($merchantId){
            $counterKey = $counterKey.'_'.$merchantId;
        }
        else{
            $counterKey = $counterKey;
        }

        $model = $this->getDi()->getObjectManager()->create('\App\Core\Models\BaseMongo');
//        $model->setCollection('counter'); //before
        $model->setSource('counter');

        $document = $model->getCollection()->findOneAndUpdate(
            ['_id'=>$counterKey ],
            ['$inc'=>['sequence_value'=>1]],
            ['writeConcern' => new \MongoDB\Driver\WriteConcern(\MongoDB\Driver\WriteConcern::MAJORITY)]
        );
       
        
       
        if (is_null($document)) {

            $model->getCollection()->insertOne(['_id'=>$counterKey,'sequence_value'=>2]);
            return (string)1;
            /*$document = $model->getCollection()->findOneAndUpdate(
                ['_id'=>$counterKey ],
                ['$inc'=>['sequence_value'=>1]]
            );*/
        }

        return (string)$document->sequence_value;
    }



    public function save():bool
    {
        try {
            $data = $this->getData();
            
            if(isset($this->_id)){
                
                $data['_id'] = $this->_id;
                $this->getCollection()->updateOne(['_id' => $this->_id ],['$set' => $data],['upsert'=>true]);
            }
            else{
                $result = $this->getCollection()->insertOne($data);
                if($result && $id = $result->getInsertedId()){
                    $this->_id = $id;

                }
            
            }
           
            
        } catch (\Exception $e) {
            $this->di->getLog()->logContent("Error saving data : ".$e->getMessage() . PHP_EOL, 'info', 'exception.log');
            return false;
        }
        return true;
    }


    public function delete():bool
    {
        try {
           
            if(isset($this->_id)){
                
                $this->getCollection()->deleteOne(['_id' => $this->_id ]);
            }
           
            
        } catch (\Exception $e) {
            $this->di->getLog()->logContent("Error deleting data : ".$e->getMessage() . PHP_EOL, 'info', 'exception.log');
            return false;
        }
        return true;
    }


    /**
     * @return mixed
     */
    public function getMultipleDbManager()
    {
        return $this->getDi()->getObjectManager()->get('\App\Core\Components\MultipleDbManager');
    }

    /**
     * @param $id
     * @return mixed
     */
    public function load($id)
    {

        return get_class($this)::findFirst([["_id"=>$id]]);
    }

    public function findOne(){

    }
    public static function findFirst($data = NULL): ?\Phalcon\Mvc\ModelInterface{
        if(isset($this)){
            $model = $this;
        }
        else{
            $c = get_called_class();
            $model = new $c;
        }
        

        if(isset($data[0]) && isset($data[0]['_id']) && is_string($data[0]['_id'])){
            $data = ['_id'=>new \MongoDB\BSON\ObjectId($data[0]["_id"])];
        }
        elseif(isset($data['_id'])){
            if(is_string($data['_id'])){
                $data = ['_id'=>new \MongoDB\BSON\ObjectId($data["_id"])];
        
            }
        }
        elseif(isset($data[0])){
            $data = $data[0];
        }
        $opts = ["typeMap" => ['root' => 'array', 'document' => 'array']];
       
        $data = $model->getCollection()->findOne($data,$opts);
        if(isset($data['_id'])){
            $model->setData($data);
            return $model;
        }
        return null;
    }


    public static function find($data = []):\Phalcon\Mvc\Model\ResultsetInterface{
        if(isset($this)){
            $model = $this;
        }
        else{
            $c = get_called_class();
            $model = new $c;
        }
        

        $filter = [];
        $opts = ["typeMap" => ['root' => 'array', 'document' => 'array']];
        if(isset($data['limit'])){
            $data['limit'] = (int)$data['limit'];
            if(isset($data[0])){
                $filter = $data[0] ??  [];
                unset($data[0]);
            }

            $opts = array_merge($opts, $data);
            if(isset($opts['offset'])){
                $opts['skip'] = $opts['offset'];
                unset($opts['offset']);
            }
        }else{
            if(isset($data[0])){
                $filter = $data[0];
            }
            else{
                $filter = $data;
            }
            

        }
        $data = $model->getCollection()->find($filter,$opts)->toArray();

        
        $data = new Result($data);
        return new Resultset(
            null,
            $model,
            $data
        );;
    }


    public static function count($data = []):int{
        if(isset($this)){
            $model = $this;
        }
        else{
            $c = get_called_class();
            $model = new $c;
        }
        

        
        $opts = ["typeMap" => ['root' => 'array', 'document' => 'array']];
        
        $data = $model->getCollection()->find($data,$opts)->toArray();

        
        return is_array($data)?count($data):0;
    }

    /**
     * @return \MongoDB\BSON\ObjectId
     */


    public static function getObjectId($stringId){
        return new \MongoDB\BSON\ObjectId($stringId);
    }
    /**
     * @return mixed
     */
    public function getDbConnection()
    {
        return $this->getDi()->get($this->getMultipleDbManager()->getDb());
    }

    /**
     * @param $property 
     * @param $arguments
     * @return $this
     * @throws \Exception
     */
    public function __call($property, $arguments)
    {
        if (strpos($property, 'get') === 0) {
            $output = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', substr($property, 3)));

            if(property_exists($this,$output))
                return $this->$output;
            else
                return null;
        } elseif (strpos($property, 'set') === 0) {
            $output = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', substr($property, 3)));
            $this->$output = $arguments[0];
            return $this;
        } else {
            //throw new \Exception($property . 'Method not found');
        }
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        $data = $this->toArray();
        if(isset($data['_id']) && !is_string($data['_id'])){
            $data['_id'] = (string)$data['_id'];
        }
        return $data;
    }

    public function toArray($columns = NULL): array{
        
        //$reflect = new \ReflectionClass($this);
        //$props = $reflect->getProperties();
        //$props = $reflect->getAttributes();
        //print_r($props);die;
        $data = (array) $this;

        $result = [];
        foreach ($data as $key => $value) {
            //var_dump($key);
           
            if(substr($key, 1,1) == '*')
                continue;
            if (is_object($value) && ! ($value instanceof \MongoDB\BSON\ObjectId))
            {
                continue;
            }
            else
                $result[$key] = $value;
        }
        
        
        
        unset($result['table']);
        unset($result['currentTransaction']);
        unset($result['implicit']);
        unset($result['di']);
        unset($result['read_connection_service']);
        unset($result['write_connection_service']);
        unset($result['isGlobal']);
        return $result;
    }
    /**
     * @param $data
     * @return $this
     */
    public function setData($data)
    {
        if (count($data)) {
            foreach ($data as $key => $value) {
                $this->$key = $value;

            }   
        }
        if(isset($data['_id'])){
            $this->id = $data['_id'];
        }
        return $this;
    }

    /**
     * @param $source
     * @param $params
     * @return array
     */
    public function getFilteredResults($source,$params){
        $this->setCollection($source);
        $collection = $this->getCollection();
        $limit = $params['count'] ?? 20;
        $page = $params['activePage'] ?? 1;
        $offset = ($page - 1) * $limit;

        $options = ['limit' => (int)$limit, 'skip' => (int)$offset,"typeMap" => ['root' => 'array', 'document' => 'array']];
        $conditionalQuery = self::search($params);
        $rows = $collection->find($conditionalQuery,$options);
        $totalRows = $collection->find($conditionalQuery);
        $responseData = [];
        $responseData['success'] = true;
        $responseData['data']['rows'] = $rows->toArray();
        $responseData['data']['count'] = count($totalRows->toArray());
        return $responseData;
    }

    /**
     * prepare conditions for where clause
     * @param array $filterParams
     * @return array
     */
    public static function search($filterParams = [])
    {
        $conditions = [];
        if (isset($filterParams['filter'])) {
            foreach ($filterParams['filter'] as $key => $value) {
                $key = trim($key);

                if (is_string($value)) {
                    $conditions[$key] = $value;
                }elseif (array_key_exists(self::IS_EQUAL_TO, $value)) {
                    if (is_bool($value[self::IS_EQUAL_TO])) {
                        $conditions[$key] = $value[self::IS_EQUAL_TO];
                    } else {
                        $conditions[$key] = [
                            '$regex' => '^' . $value[self::IS_EQUAL_TO] . '$',
                            '$options' => 'i'
                        ];
                    }
                } elseif (array_key_exists(self::IS_NOT_EQUAL_TO, $value)) {
                    if (is_bool($value[self::IS_NOT_EQUAL_TO])) {
                        $conditions[$key] =  ['$ne' => $value[self::IS_NOT_EQUAL_TO]];
                    } else {
                        $conditions[$key] =  ['$ne' => trim(addslashes($value[self::IS_NOT_EQUAL_TO]))];
                    }
                } elseif (array_key_exists(self::IS_CONTAINS, $value)) {
                    $conditions[$key] = [
                        '$regex' =>  trim(addslashes($value[self::IS_CONTAINS])),
                        '$options' => 'i'
                    ];
                } elseif (array_key_exists(self::IS_NOT_CONTAINS, $value)) {
                    $conditions[$key] = [
                        '$regex' => "^((?!" . trim(addslashes($value[self::IS_NOT_CONTAINS])) . ").)*$",
                        '$options' => 'i'
                    ];
                } elseif (array_key_exists(self::START_FROM, $value)) {
                    $conditions[$key] = [
                        '$regex' => "^" . trim(addslashes($value[self::START_FROM])),
                        '$options' => 'i'
                    ];
                } elseif (array_key_exists(self::END_FROM, $value)) {
                    $conditions[$key] = [
                        '$regex' => trim(addslashes($value[self::END_FROM])) . "$",
                        '$options' => 'i'
                    ];
                } elseif (array_key_exists(self::RANGE, $value)) {
                    if (trim($value[self::RANGE]['from']) && !trim($value[self::RANGE]['to'])) {
                        $conditions[$key] =  ['$gte' => trim($value[self::RANGE]['from'])];
                    } elseif (trim($value[self::RANGE]['to']) &&
                        !trim($value[self::RANGE]['from'])) {
                        $conditions[$key] =  ['$lte' => trim($value[self::RANGE]['to'])];
                    } else {
                        $conditions[$key] =  [
                            '$gte' => trim($value[self::RANGE]['from']),
                            '$lte' => trim($value[self::RANGE]['to'])
                        ];
                    }
                }
            }
        }
        if (isset($filterParams['search'])) {
            $conditions['$text'] = ['$search' => trim(addslashes($filterParams['search']))];
        }
        return $conditions;
    }

    public function getCollectionForTable($table) {
//        $this->setCollection($table); //before
        $collection = $this->getCollection($table);
        return $collection;
    }

    /**
    * Here we can find the product by key value
    * @param $condition
    * @return mixed
    */
    public function loadByField($condition,$options = [])
    {
        $collection = $this->getCollection();


        $opts = ["typeMap" => ['root' => 'array', 'document' => 'array']];
        $options = array_merge($options,$opts);

        if(isset($condition['_id']) && is_string($condition['_id'])){
            $condition['_id'] = new \MongoDB\BSON\ObjectId($condition['_id']);
        }

        $containerValues = $collection->findOne($condition, $options);
        return $containerValues;
    }


    public function findByField($condition,$customOption=[])
    {
        $collection = $this->getCollection();
        $options = ["typeMap" => ['root' => 'array', 'document' => 'array']];
        $options = array_merge($options,$customOption);
        if(isset($condition['_id']) && is_string($condition['_id'])){
            $condition['_id'] = new \MongoDB\BSON\ObjectId($condition['_id']);
        }
        
        $containerValues = $collection->find($condition, $options)->toArray();
        return $containerValues;
    }


    public function setUserCache($key,$value,$userId = false){
        if ( !$userId ) $userId = $this->di->getUser()->id;
        $this->setCache('user_'.$key.'_'.$userId, $value);
    }

    public function setCache($key,$value){
        $this->di->getCache()->set($key, $value);
    }

    public function getUserCache($key, $userId = false){
        if ( !$userId ) $userId = $this->di->getUser()->id;
        return $this->getCache('user_'.$key.'_'.$userId);
    }
    public function getCache($key){
        $this->di->getCache()->get($key);
    }
}
