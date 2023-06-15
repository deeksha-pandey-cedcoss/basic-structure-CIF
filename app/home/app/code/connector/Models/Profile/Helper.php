<?php

namespace App\Connector\Models\Profile;

use App\Core\Models\BaseMongo;

class Helper extends BaseMongo
{
    protected $profile_id;

    protected $marketplace;

    protected $profile_data;

    public $process_data;

    public $user_id;

    public $source_marketplace;

    public function processData()
    {
        if (isset($this->process_data['source_marketplace'])) {
            $this->source_marketplace = $this->process_data['source_marketplace'];
        }

        if (isset($this->process_data['profile_id'], $this->process_data['marketplace'])) {
            if (is_null($this->user_id)) {
                $this->user_id = $this->di->getUser()->id;
            }

            $this->profile_id = $this->process_data['profile_id'];
            $this->marketplace = $this->process_data['marketplace'];

            return ['success' => true];
        } elseif (isset($this->process_data['profile_data'], $this->process_data['marketplace'])) {
            if (is_null($this->user_id)) {
                $this->user_id = $this->di->getUser()->id;
            }
            $this->marketplace = $this->process_data['marketplace'];
            $this->profile_data = $this->process_data['profile_data'];
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => 'profile id and marketplace,source marketplace OR profile data and marketplace source marketplace is missing'];
        }
    }

    public function getProducts()
    {
        $res = $this->processData();

        if (!$res['success']) {
            return $res;
        }

        $profileData = $this->getProfileData();

        $getAllRule = $this->extractRuleFromProfileData($profileData);
        // print_r($getAllRule);

        if (!$getAllRule['success']) {
            return $getAllRule['message'];
        } else {
            $customizedFilterQuery = $getAllRule['data'];
            if ($customizedFilterQuery) {
                $finalQuery = [];
                
                $finalQuery[] = [
                    '$match' => [
                        'user_id' => $this->di->getUser()->id
                    ]
                ];

                $finalQuery[] = [
                    '$match' => $customizedFilterQuery,
                ];

                if (isset($this->process_data['skip'], $this->process_data['limit'])) {
                    $limit = (int) $this->process_data['limit'];
                    $skip = (int) $this->process_data['skip'];
                } else {
                    $skip = 0;
                    $limit = 250;
                }

                $finalQuery[] = [
                    '$graphLookup' => [
                        "from" => "product_container",
                        "startWith" => '$container_id',
                        "connectFromField" => "container_id",
                        "connectToField" => "group_id",
                        "as" => "variants",
                        "maxDepth" => 1,
                    ],
                ];
                if (!is_null($this->source_marketplace)) {
                    $finalQuery[] = [
                        '$match' => ["source_marketplace" => ['$eq' => $this->source_marketplace]],
                    ];
                }

                $finalQuery[] = [
                    '$skip' => $skip,
                ];

                $finalQuery[] = [
                    '$limit' => $limit,
                ];

                // print_r(\json_encode($finalQuery));die;

                $finalQuery[] = [
                    '$lookup' => [
                        'from' => $this->process_data['marketplace'] . '_product',
                        'let' => ['product_container_id' => '$source_product_id', 'marketplace' => '$marketplace'],
                        'pipeline' => [
                            ['$match' =>
                                ['$expr' => [
                                    '$and' => [
                                        // ['$eq' => [$this->process_data['target_shop_id'], '$$marketplace.' . $this->process_data['marketplace'] . '.shop_id']],
                                        ['$eq' => ['$source_product_id', '$$product_container_id']],
                                    ],
                                ],
                                ],
                            ],
                            ['$project' => ['_id' => 0]],
                        ],
                        'as' => $this->process_data['marketplace'] . "_marketplace",
                    ],
                ];

                $collection = $this->getCollectionForTable('product_container');
                $response = $collection->aggregate($finalQuery, ["typeMap" => ['root' => 'array', 'document' => 'array', 'object' => 'array', 'array' => 'array']]);
                $response = $response->toArray();
                if (count($response)) {
                    return ['success' => true, 'data' => $response, 'next' => 'mnbgkjinjnk'];
                } else {
                    return ['success' => false, 'message' => "No data found as per applied rule"];
                }
                $count = isset($response[0]) ? $response[0]['count'] : 0;
            }
            return ['success' => false, 'message' => "invalid query"];
        }
    }

    public function getProfileData()
    {
        $profileParams = [];
        if (!is_null($this->profile_id)) {
            $profileParams['filters'] = ['id' => $this->profile_id];
            $obj = new Model();
            $profileData = $obj->getProfile($profileParams['filters']['id']);

            $this->profile_data = $profileData['data'];
            $this->profile_id = null;
        } else {
            $profileData = $this->profile_data;
        }
        return $profileData;
    }

    public function extractRuleFromProfileData($profile_data)
    {
        $finalquery = [];
        if (!empty($profile_data['query'])) {
            $mainquery = $this->convertQueryFromMysqlToMongo($profile_data['query']);
        }

        if (isset($profile_data['targets']) && !empty($profile_data['targets'][$this->marketplace]['skip_query'])) {
            $subQuery = $this->convertQueryFromMysqlToMongo($profile_data['targets'][$this->marketplace]['skip_query']);
            $finalquery['$and'][] = $mainquery;
        /*$finalquery['$and'][] = $subQuery;*/// TODO
        } else {
            $finalquery['$and'][] = $mainquery;
        }
        if (!is_null($this->source_marketplace)) {
            $sourceQuery = $this->convertQueryFromMysqlToMongo("(source_marketplace == $this->source_marketplace)");
            $finalquery['$and'][] = $sourceQuery;
        }
        //$finalquery['$and'][] = ['group_id'=>['$exists'=>true]];
        if (count($finalquery)) {
            return ['success' => true, 'data' => $finalquery];
        } else {
            return ['success' => false, 'message' => "No query found"];
        }
    }

    public function convertQueryFromMysqlToMongo($query)
    {
        if ($query != '') {
            $filterQuery = [];
            $orConditions = explode('||', $query);
            $orConditionQueries = [];
            foreach ($orConditions as $key => $value) {
                $andConditionQuery = trim($value);
                $andConditionQuery = trim($andConditionQuery, '()');
                $andConditions = explode('&&', $andConditionQuery);
                $andConditionSet = [];
                foreach ($andConditions as $andKey => $andValue) {
                    $andConditionSet[] = $this->getAndConditions($andValue);
                }
                $orConditionQueries[] = [
                    '$and' => $andConditionSet,
                ];
            }
            $orConditionQueries = [
                '$or' => $orConditionQueries,
            ];
            return $orConditionQueries;
        }
        return false;
    }

    public function getAndConditions($andCondition)
    {
        $preparedCondition = [];
        $conditions = ['==', '!=', '!%LIKE%', '%LIKE%', '>=', '<=', '>', '<'];
        $andCondition = trim($andCondition);
        if (!is_null($this->source_marketplace)) {
            $connectorHelper = $this->di->getObjectManager()
                ->get('App\Connector\Components\Connectors')
                ->getConnectorModelByCode($this->source_marketplace);
            //TODO get attributes work pending
        }

        foreach ($conditions as $key => $value) {
            if (strpos($andCondition, $value) !== false) {
                $keyValue = explode($value, $andCondition);
                $isNumeric = false;
                $valueOfProduct = trim(addslashes($keyValue[1]));
                if (trim($keyValue[0]) == 'collections') {
                    $productIds = $this->di->getObjectManager()->get('\App\Shopify\Models\SourceModel')->getProductIdsByCollection((string) $valueOfProduct);
                    if ($productIds &&
                        count($productIds)) {
                        if ($value == '==' ||
                            $value == '%LIKE%') {
                            $preparedCondition['source_product_id'] = [
                                '$in' => $productIds,
                            ];
                        } elseif ($value == '!=' ||
                            $value == '!%LIKE%') {
                            $preparedCondition['source_product_id'] = [
                                '$nin' => $productIds,
                            ];
                        }
                    } else {
                        $preparedCondition['source_product_id'] = [
                            '$in' => $productIds,
                        ];
                    }
                    continue;
                }
                switch ($value) {
                    case '==':
                        if ($isNumeric) {
                            $preparedCondition[trim($keyValue[0])] = $valueOfProduct;
                        } else {
                            $preparedCondition[trim($keyValue[0])] = [
                                '$regex' => '^' . $valueOfProduct . '$',
                                '$options' => 'i',
                            ];
                        }
                        break;
                    case '!=':
                        $preparedCondition[trim($keyValue[0])] = [
                            '$not' => [
                                '$regex' => '^' . $valueOfProduct . '$',
                                '$options' => 'i',
                            ],
                        ];
                        break;
                    case '%LIKE%':
                        $preparedCondition[trim($keyValue[0])] = [
                            '$regex' => ".*" . $valueOfProduct . ".*",
                            '$options' => 'i',
                        ];
                        break;
                    case '!%LIKE%':
                        $preparedCondition[trim($keyValue[0])] = [
                            '$regex' => "^((?!" . $valueOfProduct . ").)*$",
                            '$options' => 'i',
                        ];
                        break;
                    case '>':
                        $preparedCondition[trim($keyValue[0])] = [
                            '$gt' => (float) $valueOfProduct,
                        ];
                        break;
                    case '<':
                        $preparedCondition[trim($keyValue[0])] = [
                            '$lt' => (float) $valueOfProduct,
                        ];
                        break;
                    case '>=':
                        $preparedCondition[trim($keyValue[0])] = [
                            '$gte' => (float) $valueOfProduct,
                        ];
                        break;
                    case '<=':
                        $preparedCondition[trim($keyValue[0])] = [
                            '$lte' => (float) $valueOfProduct,
                        ];
                        break;
                }
                break;
            }
        }
        return $preparedCondition;
    }

    public function getProfileAttribute($path)
    {
        $res = $this->processData();
        if (!$res['success']) {
            return $res;
        }
        $profileData = json_decode(json_encode($this->getProfileData()));
        $attributeDefinePath = $this->defineAttributePath();
        $pathDepth = explode('.', $path);
        $fetchedKey = [];
        $realMatch = 1;

        foreach ($attributeDefinePath as $key => $value) {
            if (count($pathDepth) >= $key) {
                if (count($pathDepth) == $key) {
                    $realMatch = $key;
                }
                $fetchedKey[] = $value;
            }
        }
        $givenPathFormate = $attributeDefinePath[$realMatch];
        $pathFormateArr = explode('.', $givenPathFormate);

        foreach ($pathDepth as $key => $value) {
            if (isset($pathFormateArr[$key])) {
                if ((strpos($pathFormateArr[$key], '$') !== false)) {
                    $name = str_replace('$', '', $pathFormateArr[$key]);
                    $$name = $value;
                }
            }
        }

        $attributes = [];

        foreach ($fetchedKey as $key => $value) {
            if (isset($value)) {
                $string = '';
                eval('$string = "' . $value . '";');
                $strArr = explode('.', $string);
                $dyProfileData = $profileData;
                $fetched = true;

                foreach ($strArr as $strkey => $strval) {
                    if (isset($dyProfileData->{$strval})) {
                        $dyProfileData = $dyProfileData->$strval;
                    } else {
                        $fetched = false;
                        break;
                    }
                }

                if ($fetched) {
                    $dyProfileData = json_decode(json_encode($dyProfileData), true);

                    $attributes = array_merge($attributes, $dyProfileData);
                }
                $dyProfileData = $profileData;
            }
        }
        if (count($attributes)) {
            return ['success' => true, 'data' => $attributes];
        } else {
            return ['success' => false, 'message' => 'attribute not found'];
        }
    }

    public function defineAttributePath()
    {
        return [
            1 => 'attributes_mapping',
            3 => 'targets.$target_marketplace.attributes_mapping',
            7 => 'targets.$target_marketplace.shops.$target_shop_id.warehouses.$target_warehouse_id.attributes_mapping',
            9 => 'targets.$target_marketplace.shops.$target_shop_id.warehouses.$target_warehouse_id.sources.$source_marketplace_id.attributes_mapping',
            13 => 'targets.$target_marketplace.shops.$target_shop_id.warehouses.$target_warehouse_id.sources.$source_marketplace_id.shops.$source_marketplace_shop_id.warehouses.$source_marketplace_warehouse_id.attributes_mapping',
        ];
    }
}
