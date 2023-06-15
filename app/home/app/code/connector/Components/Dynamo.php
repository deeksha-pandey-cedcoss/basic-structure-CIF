<?php
namespace App\Connector\Components;
use Aws\DynamoDb\DynamoDbClient;

class Dynamo extends \App\Core\Components\Base
{
    public $table;
    public $dynamoDbClient;
    public $uniqueKeys = [];
    public $tableUniqueColumn;
    

    public function getTable()
    {
        return $this->table;
    } 

    public function setTable($table)
    {
        $this->table = $table;
        return $this->table;
    }

    public function setUniqueKeys($uniqueKeys)
    {
        $this->uniqueKeys = $uniqueKeys;
        return $this->uniqueKeys;
    }

    public function getUniqueKeys()
    {
        return $this->uniqueKeys;
    }


    public function setDetails($config)
    {
        if(empty($config)){
            $client = DynamoDbClient::factory(include BP.'/app/etc/dynamo.php');
            $this->dynamoDbClient = $client;
            return $this->dynamoDbClient;
        } else {
            $client = DynamoDbClient::factory($config);
            $this->dynamoDbClient = $client;
            return $this->dynamoDbClient;
        }
    }

    public function getDetails()
    {
        if(is_null($this->dynamoDbClient)){
            $client = DynamoDbClient::factory(include BP.'/app/etc/dynamo.php');
            $this->dynamoDbClient = $client;
            return $this->dynamoDbClient;
        }
        return $this->dynamoDbClient;
    }

    public function getTableUniqueColumn() 
    {
        return $this->tableUniqueColumn;
    }

    public function setTableUniqueColumn($columnName) 
    {
        $this->tableUniqueColumn = $columnName;
    }

    public function save($data)
    {
        $table = $this->getTable();
        $client = $this->getDetails();


        if(!empty($this->uniqueKeys) && !is_null($this->tableUniqueColumn))
        {
            /*foreach ($data as $sentValue) {
                $item = [];
                $uniquekeyValue = '';
                foreach ($sentValue as $key => $value) {
                    if(in_array($key, $this->uniqueKeys))
                    {
                        if(empty($uniquekeyValue)){
                            $uniquekeyValue = $value;
                        }else{
                            $uniquekeyValue = $uniquekeyValue.'_'.$value;
                        }
                        
                    }
                    $item[$key] = ['S'=>(string)$value];
                }
                if(!empty($uniquekeyValue)){
                    $item[$this->tableUniqueColumn] = ['S'=>(string)$uniquekeyValue];
                    $result = $client->putItem([
                        'Item' => $item,
                        'TableName' => $table, // REQUIRED
                    ]);
                }
            }*/
            $batchItem = [];
            foreach ($data as $sentValue) {
                $item = [];
                $uniquekeyValue = '';
                foreach ($sentValue as $key => $value) {
                    if(in_array($key, $this->uniqueKeys))
                    {
                        if(empty($uniquekeyValue)){
                            $uniquekeyValue = $value;
                        }else{
                            $uniquekeyValue = $uniquekeyValue.'_'.$value;
                        }
                        
                    }
                    $item[$key] = ['S'=>(string)$value];
                }
                if(!empty($uniquekeyValue)){
                    $item[$this->tableUniqueColumn] = ['S'=>(string)$uniquekeyValue];
                    $batchItem[] = ['PutRequest'=>['Item'=>$item]];
                }

            }
            
            $result = $client->batchWriteItem(['RequestItems'=>[$table=>$batchItem]]);
          
            return ['success'=>true,'message'=>"data saved Successfully"];
        } else {
            return ['success'=>false,'message'=>"unique key or unique table column is not defined"];
        } 

    }

}
