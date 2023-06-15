<?php

namespace App\Connector\Models;

use App\Core\Models\BaseMongo;
use FacebookAds\Exception\Exception;
use Phalcon\Validation;

class ProductContainer extends BaseMongo
{
    use \App\Core\Components\Concurrency;

    const RANGE = 7;
    const END_FROM = 6;
    const START_FROM = 5;
    const IS_EQUAL_TO = 1;
    const IS_CONTAINS = 3;
    const IS_NOT_EQUAL_TO = 2;
    const IS_NOT_CONTAINS = 4;
    const IS_GREATER_THAN = 8;
    const IS_LESS_THAN = 9;
    const PRODUCT_TYPE_SIMPLE = 'simple';
    const PRODUCT_TYPE_VARIANT = 'variant';

    const VARIANT_FIELDS_REQUIRED = [
        'price' => 'float',
        'quantity' => 'int',
        'source_product_id' => 'string',
        'user_id' => "string",
        "shop_id" => "string",
        "title" => "string",
    ];

    const VI_PRODUCT_FIELDS_REQUIRED = [
        'source_product_id' => 'string',
        'container_id' => 'string',
        'user_id' => "string",
        "shop_id" => "string",
        "title" => "string",
    ];

    const DEFAULT_FIELDS = [
        "description" => "",
        "additional_images" => [],
        "variant_attributes" => [],
        // "marketplace" => [],
        "brand" => "",
    ];

    const DEFAULT_FIELDS_FOR_VARIANT = [
        "description" => "",
        "additional_images" => [],
        "variant_attributes" => [],
        "brand" => "",
        "sku" => "",
    ];

    //protected $sqlConfig;
    protected $implicit = false;
    protected $table = 'product_container';
    public static $defaultPagination = 20;

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
     * @return mixed
     */
    public function validation()
    {
        $validator = new Validation();
        return $this->validate($validator);
    }

    /**
     * productOnly = boolean
     */

    public function getProductsCount($params)
    {
        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $collection = $mongo->setSource("product_container")->getPhpCollection();

        $userId = $this->di->getUser()->id;

        $countAggregation = $this->buildAggregateQuery($params, 'productCountall');


        $countAggregation[] = [
            '$count' => 'count',
        ];
        // print_r($countAggregation);
        // die;

        try {
            $totalVariantsRows = $collection->aggregate($countAggregation)->toArray();
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                // 'data' => $aggregation
            ];
        }

        $responseData = [
            'success' => true,
            'query' => $countAggregation,
            'data' => [],
        ];

        $totalVariantsRows = $totalVariantsRows[0]['count'] ? $totalVariantsRows[0]['count'] : 0;
        $responseData['data']['count'] = $totalVariantsRows;
        return $responseData;
    }

    /**
     * count : number
     * activePage: number
     * type : admin | user
     * filterType : or | and
     * filter : Array
     * or_filter : Array
     * search : string
     * next: string
     * target_marketplace = string e.g shopify, facebook
     */
    public function getProducts($params)
    {
        // print_r($params);die;
        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $collection = $mongo->setSource("product_container")->getPhpCollection();

        $totalPageRead = 1;
        $limit = $params['count'] ?? self::$defaultPagination;
        $prev = [];

        if (isset($params['next'])) {
            $nextDecoded = json_decode(base64_decode($params['next']), true);
            $prev = $nextDecoded['pointer'] ?? null;
            $totalPageRead = $nextDecoded['totalPageRead'];
        }
        if (isset($params['prev'])) {
            $nextDecoded = json_decode(base64_decode($params['prev']), true);
            $prev = $nextDecoded['cursor'] ?? null;
            $totalPageRead = $nextDecoded['totalPageRead'];
        }

        if (isset($params['prev'])) {
            $prevDecoded = json_decode(base64_decode($params['prev']), true);
        }

        if (isset($params['activePage'])) {
            $page = $params['activePage'];
            $offset = ($page - 1) * $limit;
        }
        $userId = $this->di->getUser()->id;

        $aggregation = $this->buildAggregateQuery($params);

        if (isset($offset)) {
            $aggregation[] = ['$skip' => (int)$offset];
        }

        $aggregation[] = ['$limit' => (int)$limit + 1];
        // echo "<pre>";
        // print_r(($aggregation));die;
        try {
            /** use this below code get to slow down */
            //TODO: TEST Needed here
            $cursor = $collection->aggregate($aggregation);
            $it = new \IteratorIterator($cursor);
            $it->rewind(); // Very important

            $rows = [];
            while ($limit > 0 && $doc = $it->current()) {
                $rows[] = $doc;
                $limit--;
                $it->next();
            }
            if (!$it->isDead()) {
                if (!isset($params['prev'])) {
                    $prev[] = $rows[0]['_id'];
                } else if (count($prev) > 1) {
                    array_pop($prev);
                }
                $next = base64_encode(json_encode([
                    'cursor' => ($doc)['_id'],
                    'pointer' => $prev,
                    'totalPageRead' => $totalPageRead + 1,
                ]));
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                // 'data' => $aggregation
            ];
        }

        $prevCursor = null;
        if (count($prev) > 1 || isset($params['prev'])) {
            if (count($prev) === 1 && $rows[0]['_id'] === $prev[0]) {
                $prevCursor = null;
            } else {
                $prevCursor = base64_encode(json_encode([
                    'cursor' => $prev,
                    'totalPageRead' => $totalPageRead + 1,
                ]));
            }
        }

        $responseData = [
            'success' => true,
            'data' => [
                'totalPageRead' => $page ?? $totalPageRead,
                'current_count' => count($rows),
                'next' => $next ?? null,
                'prev' => $prevCursor,
                'rows' => $rows,
                // 'query' => $aggregation
            ],
        ];

        return $responseData;
    }

    private function buildAggregateQuery($params, $callType = 'getProduct')
    {
        $productOnly = false;
        $aggregation = [];
        $andQuery = [];
        $orQuery = [];
        $filterType = '$and';
        $userId = $this->di->getUser()->id;

        // type is used to check user Type, either admin or some customer
        if (isset($params['type'])) {
            $type = $params['type'];
        } else {
            $type = '';
        }

        if (isset($params['productOnly'])) {
            $productOnly = $params['productOnly'] === "true";
        }

        if (isset($params['filterType'])) {
            $filterType = '$' . $params['filterType'];
        }

        if (isset($params['filter']) || isset($params['search'])) {
            $andQuery = self::search($params);
        }

        if (isset($params['or_filter'])) {
            $orQuery = self::search(['filter' => $params['or_filter']]);
            $temp = [];
            foreach ($orQuery as $optionKey => $orQueryValue) {
                $temp[] = [
                    $optionKey => $orQueryValue,
                ];
            }
            $orQuery = $temp;
        }

        if ($type != 'admin') {
            $aggregation[] = ['$match' => ['user_id' => $userId]];
        }


        if ($callType === "productCountall") {
            // $aggregation[] = ['$match' => ['type' => "simple"]];
            $aggregation[] = ['$match' => ['visibility' => "Catalog and Search"]];
        }

        if ($callType === "productCount") {
            // $aggregation[] = ['$match' => ['type' => "simple"]];
            $aggregation[] = ['$match' => ['type' => "simple"]];
        }


        if (isset($params['next']) && $callType === 'getProduct') {
            $nextDecoded = json_decode(base64_decode($params['next']), true);
            $aggregation[] = [
                '$match' => ['_id' => [
                    '$gt' => $nextDecoded['cursor'],
                ]],
            ];
        } elseif (isset($params['prev']) && $callType === 'getProduct') {
            $prevDecoded = json_decode(base64_decode($params['prev']), true);
            if (count($prevDecoded['cursor']) != 0) {
                $lastIndex = $prevDecoded['cursor'][count($prevDecoded['cursor']) - 1];
                $aggregation[] = [
                    '$match' => ['_id' => [
                        '$gte' => $lastIndex,
                    ]],
                ];
            }
        }

        if (isset($params['container_ids'])) {
            $aggregation[] = [
                '$match' => ['container_id' => [
                    '$in' => $params['container_ids']
                ]],
            ];
        }

        if (isset($params['app_code'])) {
            $aggregation[] = [
                '$match' => ['app_codes' => ['$in' => [$params['app_code']]]],
            ];
        }

        if ($productOnly) {
            $aggregation[] = [
                '$match' => [
                    '$or' => [
                        ['$and' => [
                            ['type' => 'variation'],
                            ["visibility" => 'Catalog and Search'],
                        ]],
                        ['$and' => [
                            ['type' => 'simple'],
                            ["visibility" => 'Catalog and Search'],
                        ]],
                    ],
                ],
            ];
        }

        if (count($andQuery)) {
            $aggregation[] = ['$match' => [
                $filterType => [
                    $andQuery,
                ],
            ]];
        }

        if (count($orQuery)) {
            $aggregation[] = ['$match' => [
                '$or' => $orQuery,
            ]];
        }

        return $aggregation;
    }

    /**
     * container_id
     * target_marketplace
     */
    public function getChildProducts($params)
    {
        // print_r($params);die;
        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $collection = $mongo->setSource("product_container")->getPhpCollection();

        $totalPageRead = 1;
        $limit = $params['count'] ?? self::$defaultPagination;
        $prev = [];

        if (isset($params['next'])) {
            $nextDecoded = json_decode(base64_decode($params['next']), true);
            $prev = $nextDecoded['pointer'] ?? null;
            $totalPageRead = $nextDecoded['totalPageRead'];
        }
        if (isset($params['prev'])) {
            $nextDecoded = json_decode(base64_decode($params['prev']), true);
            $prev = $nextDecoded['cursor'] ?? null;
            $totalPageRead = $nextDecoded['totalPageRead'];
        }

        if (isset($params['prev'])) {
            $prevDecoded = json_decode(base64_decode($params['prev']), true);
        }

        if (isset($params['activePage'])) {
            $page = $params['activePage'];
            $offset = ($page - 1) * $limit;
        }
        $userId = $this->di->getUser()->id;
        $aggregation = [];

        $aggregation[] = [
            '$match' => [
                'user_id' => $userId,
                'container_id' => $params['container_id']
            ]
        ];

        if (isset($offset)) {
            $aggregation[] = ['$skip' => (int)$offset];
        }

        $aggregation[] = ['$limit' => (int)$limit + 1];
        // echo "<pre>";
        // print_r(($aggregation));die;
        try {
            $cursor = $collection->aggregate($aggregation);
            $it = new \IteratorIterator($cursor);
            $it->rewind(); // Very important

            $rows = [];
            $source_child = [];
            $target_child = [];
            while ($limit > 0 && $doc = $it->current()) {
                $rows[] = $doc;
                if (!isset($doc['visibility']) || $doc['visibility'] != 'Catalog and Search') {
                    if (isset($doc['source_marketplace'])) {
                        $source_child[] = $doc;
                    } else if ($doc['target_marketplace'] === $params['target_marketplace']) {
                        $target_child[] = $doc;
                    }
                }
                $limit--;
                $it->next();
            }
            foreach ($target_child as $tchild) {
                foreach ($source_child as $sKey => $sChild) {
                    if ($tchild['source_id'] === $sChild['source_product_id']) {
                        $source_child[$sKey] = json_decode(json_encode($sChild), true)
                            + json_decode(json_encode($tchild), true);
                    }
                }
            }
            if (!$it->isDead()) {
                if (!isset($params['prev'])) {
                    $prev[] = $rows[0]['_id'];
                } else if (count($prev) > 1) {
                    array_pop($prev);
                }
                $next = base64_encode(json_encode([
                    'cursor' => ($doc)['_id'],
                    'pointer' => $prev,
                    'totalPageRead' => $totalPageRead + 1,
                ]));
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => $aggregation
            ];
        }

        $prevCursor = null;
        if (count($prev) > 1 || isset($params['prev'])) {
            if (count($prev) === 1 && $rows[0]['_id'] === $prev[0]) {
                $prevCursor = null;
            } else {
                $prevCursor = base64_encode(json_encode([
                    'cursor' => $prev,
                    'totalPageRead' => $totalPageRead + 1,
                ]));
            }
        }

        $responseData = [
            'success' => true,
            'data' => [
                'totalPageRead' => $page ?? $totalPageRead,
                'current_count' => count($source_child),
                'next' => $next ?? null,
                'prev' => $prevCursor,
                'rows' => $source_child,
            ],
        ];

        return $responseData;
    }

    /**
     * {
     *  [source_product_id : 'ABC', .....],
     *  [source_product_id : 'XYZ' , ... ],
     *  ....
     * }
     */

    public function editedProductNew($data)
    {
        foreach ($data['items'] as $key => $value) {
        }
        // target_marketplace is marketplace where client want to make changes in it products
        if (!isset($data['target_marketplace'])) {
            return ['success' => false, 'message' => 'Required field target_marketplace missing.'];
        }

        // For Sending Complete Data
        if (isset($data['details']) && isset($data['variants'])) {
            $this->editedProduct($data['details']);
            foreach ($data['variants'] as $variant) {
                $this->editedProduct(['edited' => $variant]);
            }

            return ['success' => true, 'message' => 'Product Saved.'];
        }

        if (!isset($data['source_product_id']) || !isset($data['edited'])) {
            return ['success' => false, 'message' => 'Required field source_product_id | edited missing.'];
        }

        $userId = $this->di->getUser()->id;

        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        // $collection = $mongo->getCollectionForTable($data['target_marketplace'] . '_product');
        $collection = $mongo->getCollectionForTable($data['target_marketplace'] . '_product_container');
        $product_container = $mongo->getCollectionForTable('product_container');

        if (isset($data['_id'])) {
            unset($data['_id']);
        }

        $query = ['source_product_id' => $data['source_product_id']];

        if ($collection->findOne($query) === null) {
            $data['_id'] = (string)$this->getCounter('product_id');
            $data['edited']['source_product_id'] = $data['source_product_id'];
            $status = $collection->insertOne($data['edited']);
            $status->getInsertedCount();
        } else {
            $set = ['$set' => $data['edited']];
            $status = $collection->updateOne($query, $set);
            $status->getModifiedCount();
        }

        if ($status) {
            $query = ['source_product_id' => $data['source_product_id']];
            // $set = ['$set' => [
            //     'marketplace.' . $data['target_marketplace'] . '.isEdited' => true,
            // ]];
            // $product_container->updateOne($query, $set);
            // return ['success' => true, 'message' => 'Product saved ', 'ss' => $set];
            return ['success' => true, 'message' => 'Product saved '];
        }
        return ['success' => false, 'message' => 'no update in product product'];
    }


    public function editedProduct($data)
    {
        // target_marketplace is marketplace where client want to make changes in it products
        if (!isset($data['target_marketplace'])) {
            return ['success' => false, 'message' => 'Required field target_marketplace missing.'];
        }

        // For Sending Complete Data
        if (isset($data['details']) && isset($data['variants'])) {
            $this->editedProduct($data['details']);
            foreach ($data['variants'] as $variant) {
                $this->editedProduct(['edited' => $variant]);
            }

            return ['success' => true, 'message' => 'Product Saved.'];
        }

        if (!isset($data['source_product_id']) || !isset($data['edited'])) {
            return ['success' => false, 'message' => 'Required field source_product_id | edited missing.'];
        }

        $userId = $this->di->getUser()->id;

        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        // $collection = $mongo->getCollectionForTable($data['target_marketplace'] . '_product');
        $collection = $mongo->getCollectionForTable($data['target_marketplace'] . '_product_container');
        $product_container = $mongo->getCollectionForTable('product_container');

        if (isset($data['_id'])) {
            unset($data['_id']);
        }

        $query = ['source_product_id' => $data['source_product_id']];

        if ($collection->findOne($query) === null) {
            $data['_id'] = (string)$this->getCounter('product_id');
            $data['edited']['source_product_id'] = $data['source_product_id'];
            $status = $collection->insertOne($data['edited']);
            $status->getInsertedCount();
        } else {
            $set = ['$set' => $data['edited']];
            $status = $collection->updateOne($query, $set);
            $status->getModifiedCount();
        }

        if ($status) {
            $query = ['source_product_id' => $data['source_product_id']];
            // $set = ['$set' => [
            //     'marketplace.' . $data['target_marketplace'] . '.isEdited' => true,
            // ]];
            // $product_container->updateOne($query, $set);
            // return ['success' => true, 'message' => 'Product saved ', 'ss' => $set];
            return ['success' => true, 'message' => 'Product saved '];
        }
        return ['success' => false, 'message' => 'no update in product product'];
    }

    public function getProductById($productDetails)
    {
        if (isset($productDetails['id']) && isset($productDetails['source_marketplace'])) {
            $source_product_id = $productDetails['id'];
            if (isset($productDetails['user_id'])) {
                $userId = $productDetails['user_id'];
            } else {
                $userId = $this->di->getUser()->id;
            }
            $mongo = $this->di->getObjectManager()->get('\App\Core\Models\BaseMongo');
            $collection = $mongo->getCollectionForTable('product_container');

            // $marketplace_collection = $mongo->getCollectionForTable($productDetails['source_marketplace'] . '_product');
            $marketplace_collection = $mongo->getCollectionForTable($productDetails['source_marketplace'] . '_product_container');

            $finalQuery = ['container_id' => (string)$source_product_id, 'user_id' => (string)$userId];
            $response = $collection->find($finalQuery)->toArray();
            $marketplace_response = $marketplace_collection->findOne($finalQuery, ["typeMap" => ['root' => 'array', 'document' => 'array']]);
            if ($response) {
                $productData = $response;
                // if ( isset($marketplace_response) ) {
                //     $productData['edited_fields'] = $marketplace_response['edited_fields'];
                // }
                // if($response['type']=="variation" && $response['visibility']=="Not Visible Individually"){
                //     $allProductData=$collection->find(['container_id' => (string)$response['container_id'],'user_id'=>$userId])->toArray();
                //     $marketplace_response = $marketplace_collection->find(['container_id' => (string)$response['container_id'],'user_id'=>$userId])->toArray();
                //     foreach ($allProductData as $key=>$product){
                //         foreach ($marketplace_response as $key=>$marketplace_product){
                //             // if($product['type']=="variation" && $product['visibility']=="Catalog and Search"){
                //             //     if ( isset($marketplace_product['edited_fields']['description']) ) {
                //             //         $allProductData[$key]['editableDescription'] = $marketplace_product['edited_fields']['description'];
                //             //     } else {
                //             //         $allProductData[$key]['editableDescription'] = isset($product['description']) ? strip_tags($product['description']) : strip_tags($product['long_description']);
                //             //     }
                //             // }
                //             if($product['type']=="variation" && $product['visibility'] == "Not Visible Individually"){
                //                 if ( isset($marketplace_product['edited_fields']) ) {
                //                     $allProductData[$key]['edited_fields'] = $marketplace_product['edited_fields'];
                //                 }
                //             }
                //         }
                //     }
                //     $productData=$allProductData;
                // }
                return ['success' => true, 'data' => $productData];
            }
        }
        return ['success' => false, 'message' => 'Product not found'];
    }

    public function getMarketplaceProducts($conditionalQuery, $options, $marketplace, $storeId = false, $unwindVariants = false)
    {
        $marketplaceProduct = $this->di->getObjectManager()->create('\App\Connector\Models\MarketplaceProduct');
        $this->getCollection()->createIndex([
            "details.title" => "text",
            "details.short_description" => "text",
            "details.long_description" => "text",
            "variants.source_variant_id" => 1,
        ]);
        $aggregation = [];
        if ($unwindVariants) {
            $aggregation[] = ['$unwind' => '$variants'];
        }
        if (count($conditionalQuery)) {
            $aggregation[] = ['$match' => $conditionalQuery];
        }

        if (isset($options['limit'])) {
            $aggregation[] = ['$limit' => (int)$options['limit']];
            $aggregation[] = ['$skip' => (int)$options['skip']];
        }

        $aggregation[] = [
            '$lookup' => [
                'from' => $marketplaceProduct->getSource(),
                'localField' => "_id", // field in the product1 collection
                'foreignField' => "_id", // field in the product2 collection
                'as' => "fromItems",
            ],
        ];

        $aggregation[] = [
            '$replaceRoot' => ['newRoot' => ['$mergeObjects' => [['$arrayElemAt' => ['$fromItems', 0]], '$$ROOT']]],
        ];
        /*
        $aggregation = [
        ['$match'=>$conditionalQuery],
        [
        '$lookup'=> [
        'from'=> $marketplaceProduct->getSource(),
        'localField'=> "_id",    // field in the product1 collection
        'foreignField'=> "_id",  // field in the product2 collection
        'as'=> "fromItems"
        ]
        ],
        [
        '$replaceRoot'=> [ 'newRoot'=> [ '$mergeObjects'=> [ [ '$arrayElemAt'=> [ '$fromItems', 0 ] ], '$$ROOT' ] ] ]
        ],
        //[ '$project'=> ['fromItems'=> 0 ] ],
        //['$filter' => $conditionalQuery]
        //[ '$project'=> ['fromItems'=>['$filter'=>['input'=>$conditionalQuery]] ] ]

        ];*/
        $collection = $this->getCollection()->aggregate($aggregation, $options);
        return $collection;
        $userId = $this->di->getUser()->id;
        //$collection = ->getCollection();

        foreach ($containerIds as $value) {
            $contIds[] = $value['product_id'];
        }
        return $contIds;
    }

    /**
     * Initiate Product Importing
     * @param $data
     * @return mixed
     */
    public function importProducts($data)
    {
        if (isset($data['marketplace'])) {
            $connectorHelper = $this->di->getObjectManager()
                ->get('App\Connector\Components\Connectors')
                ->getConnectorModelByCode($data['marketplace']);


            if ($connectorHelper) {
                return $connectorHelper->initiateImport($data);
            }
        }
    }

    /**
     * Sync Products From Source
     * @param $data
     * @return mixed
     */
    public function syncCatalog($data)
    {
        if (isset($data['marketplace'])) {
            $connectorHelper = $this->di->getObjectManager()
                ->get('App\Connector\Components\Connectors')
                ->getConnectorModelByCode($data['marketplace']);


            if ($connectorHelper) {
                return $connectorHelper->initiateSyncCatalog($data);
            }
        }
    }

    /**
     * Initiate Product Uploading
     * @param $data
     * @return mixed
     */
    public function uploadProducts($data)
    {


        // if (isset($data['marketplace'])) {
        //     $connectorHelper = $this->di->getObjectManager()
        //         ->get('App\Connector\Components\Connectors')
        //         ->getConnectorModelByCode($data['marketplace']);

        //     if ($connectorHelper) {
        //         return $connectorHelper->initiateUpload($data);
        //     }
        // }

        // $connectorHelper=$this->di->getObjectManager()
        //         ->get('App\Connector\Models\SourceModel')
        //         ->initiateUpload($data);

        // if ($connectorHelper) {
        //         return $connectorHelper->initiateUpload($data);
        //     }

    }

    /**
     * Initiate Product Uploading By CSV
     * @param $data
     * @return mixed
     */
    public function uploadProductsCSV($data)
    {
        if (isset($data['marketplace'])) {
            $connectorHelper = $this->di->getObjectManager()
                ->get('App\Connector\Components\Connectors')
                ->getConnectorModelByCode($data['marketplace']);

            if ($connectorHelper) {
                return $connectorHelper->uploadviaCSV($data);
            }
        }
    }

    public function fetchProductsByProfile($profileId, $userId, $sourceShopId = false)
    {
        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $collection = $mongo->getCollectionForTable('profiles');
        $profile = $collection->findOne([
            "profile_id" => (int)$profileId,
        ]);

        if ($profile) {
            $productQuery = $profile->query;
            $marketplace = $profile->source;
            $productData = $this->di->getObjectManager()->get('\App\Connector\Models\Product')->getProductsByQuery(['marketplace' => $marketplace, 'query' => $productQuery], $userId, $sourceShopId);
            if ($productData['success']) {
                return $productData['data'];
            }
            return [];
        } else {
            return false;
        }
    }

    /**
     * @param $data
     * @param $marketplace
     * @param int $storeId
     * @return array
     */
    public function createProductsAndAttributes($data, $marketplace, $home_shop_id = 0, $merchantId = false, $targetEventData = [])
    {
        $this->di = $this->getDi();
        if (!$merchantId) {
            $merchantId = $this->di->getUser()->id;
        }

        $response = [];

        foreach ($data as $productData) {
            $response[] = $this->di->getObjectManager()
                ->create('\App\Connector\Models\ProductContainer')
                ->createProduct($productData, $marketplace, $home_shop_id, $merchantId, $targetEventData);
        }
        return $response;
    }


    /**
     * @param $productData
     * @pInvalid Dataaram null $marketplace
     * @return array
     */
    public function createProduct($productData, $marketplace, $home_shop_id, $merchantId, $targetEventData)
    {

        $newMode = true;
        $variantIds = [];
        if (isset($productData['details']) && isset($productData['variants'])) {
            if ($productData['details']['type'] != "variation" && $productData['details']['type'] != "simple") {
                return ["success" => false, "message" => "No Product Type is Given, add variation | simple" . ", Given type is " . $productData['details']['type']];
            }

            if (isset($productData['details']['variant_attribute'])) {
                $productData['details']['variant_attributes'] = $productData['details']['variant_attribute'];
            } else if (isset($productData['details']['variant_attributes'])) {
                $productData['details']['variant_attributes'] = $productData['details']['variant_attributes'];
            }
            if (isset($productData['variant_attribute'])) {
                $productData['variant_attributes'] = $productData['variant_attribute'];
            } else if (isset($productData['variant_attributes'])) {
                $productData['variant_attributes'] = $productData['variant_attributes'];
            }

            if (isset($productData['variants'][0]['variant_attribute'])) {
                $productData['variants'][0]['variant_attributes'] = $productData['variants'][0]['variant_attribute'];
            } else if (isset($productData['variants'][0]['variant_attributes'])) {
                $productData['variants'][0]['variant_attributes'] = $productData['variants'][0]['variant_attributes'];
            }

            $this->di->getLog()->logContent('product 2:' . json_encode($productData), 'info', 'product_cccc.log');
            if ((isset($productData['details']['variant_attributes']) && count($productData['details']['variant_attributes']) > 0)
                || (isset($productData['variant_attributes']) && count($productData['variant_attributes']) > 0)
                || (isset($productData['variants'][0]['variant_attributes']) && count($productData['variants'][0]['variant_attributes']) > 0)
            ) {
                $parentExists = null;
                if (isset($productData['details']['source_product_id'])) {
                    $parentExists = $this->loadByField([
                        "source_product_id" => $productData['details']['source_product_id'],
                        "source_marketplace" => $marketplace,
                    ], $merchantId);
                } elseif (isset($productData['variants'][0]['source_product_id'])) {
                    $parentExists = $this->loadByField([
                        "source_product_id" => $productData['variants'][0]['source_product_id'],
                        "source_marketplace" => $marketplace,
                    ], $merchantId);
                }

                if (!$parentExists) {
                    $parentResponseData = $this
                        ->createParentProduct($productData, $merchantId, $marketplace, $home_shop_id, $targetEventData);
                }

                if (isset($parentResponseData) && !$parentResponseData['success']) {
                    return $parentResponseData;
                }

                if (isset($parentResponseData)) {
                    $collectResponseData[] = $parentResponseData;
                }
            }

            if (isset($productData['is_wrapper_update']) && $productData['is_wrapper_update']) {
                $parentResponseData = $this->createParentProduct($productData, $merchantId, $marketplace, $home_shop_id, $targetEventData);
            }

            try {
                if (isset($productData['variants'])) {
                    foreach ($productData['variants'] as $key => $value) {
                        if (
                            !isset($value['container_id'])
                            && isset($parentResponseData['source_product_id'])
                        ) {
                            $value['container_id'] = $parentResponseData['source_product_id'];
                        }
                        $isolatedProductResponse = $this->di->getObjectManager()
                            ->create('\App\Connector\Models\ProductContainer')
                            ->createIsolatedProduct($value, $merchantId, $marketplace, $home_shop_id, $targetEventData);
                        $collectResponseData[] = $isolatedProductResponse;
                    }
                }

                return [
                    'success' => true,
                    'message' => 'Product(s) created successfully.',
                    'data' => $collectResponseData,
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'code' => 'something_went_wrong',
                    'message' => 'Something went wrong',
                    'data' => [$e->getMessage()],
                ];
            }
        } else {
            return [
                'success' => false,
                'code' => 'invalid_data',
                'message' => 'Invalid Data',
                'data' => [],
            ];
        }
    }

    public function checkForUniqueSku(&$product)
    {
        $mongo = $this->di->getObjectManager()->get('\App\Core\Models\BaseMongo');
        $productContainer = $mongo->getCollectionForTable('product_container');
        $filter = [
            'user_id' => $this->di->getUser()->id,
            'source_sku' => $product['source_sku']
        ];
        $product_sku_count = $productContainer->count($filter);
        if ($product_sku_count > 0) {
            $counter = (string)$this->getCounter($product['source_sku'], $this->di->getUser()->id);
            $product['sku'] = $product['source_sku'] . '_' . $counter;
        }
    }

    public function createParentProduct($pData, $id, $marketplace, $home_shop_id, $targetEventData)
    {
        if (!isset($pData['details'])) {
            return [
                'success' => false,
                'message' => 'Required Param, Deatils is missing',
            ];
        }
        try {
            $data = $pData['details'];
            $data['variant_attributes'] = $pData['variant_attributes'] ?? [];
            if (isset($pData['details']['variant_attributes'])) {
                if (empty($data['variant_attributes'])) {
                    $data['variant_attributes'] = $pData['details']['variant_attributes'];
                }
            }
            $data['variant_attributes'] = $pData['details']['variant_attributes'] ?? [];

            // not needed since in case of product edit, because shop_id is already set
            // $data['shop_id'] = (string) $home_shop_id;
            $existing = $this->loadByField([
                "source_product_id" => (string)$data['source_product_id']
            ]);

            if ($existing) {
                $data['_id'] = (string)$existing['_id'];
                $data['source_marketplace'] = $marketplace;
            } else {
                $data['_id'] = (string)$this->getCounter('product_id');
                $data['user_id'] = $id;
                $data['shop_id'] = $home_shop_id;
                if (!isset($data['source_product_id'])) {
                    $data['source_product_id'] = $data['_id'];
                }

                if (!isset($data['container_id'])) {
                    $data['container_id'] = $data['source_product_id'];
                }

                $data['created_at'] = date("Y-m-d H:i:s");
                $data['visibility'] = "Catalog and Search";
                $data['source_marketplace'] = $marketplace;
                $data = $this->validateDetailsData($data);

                if (isset($data['message']) && $data['success'] === false) {
                    return $data;
                }
            }

            $eventsManager = $this->di->getEventsManager();
            $eventsManager->fire('application:productSaveBefore', $this, ['product_data' => &$data]);
            print_r($data);

            $product_container = $this->setData($data);
            // $status = $product_container->save();
            $status = $this->handleLock('product_container', function () use ($product_container) {
                return $product_container->save();
            });

            if (isset($targetEventData['target_marketplace'], $targetEventData['target_shop_id'])) {
                $data['targetEventData']['target_marketplace'] = $targetEventData['target_marketplace'];
                $data['targetEventData']['target_shop_id'] = $targetEventData['target_shop_id'];
            }

            $eventsManager = $this->di->getEventsManager();
            $eventsManager->fire('application:productSaveAfter', $this, $data);

            return [
                'success' => true,
                'source_product_id' => $data['source_product_id'],
                'message' => 'Parent Product Created Successfullyyyyyyy',
                'data' => $product_container->getId(),
                'container_id' => $data['container_id'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function createIsolatedProduct($data, $id, $marketplace, $home_shop_id, $targetEventData)
    {
        try {
            $existing = $this->loadByField(["source_product_id" => $data['source_product_id'], "source_marketplace" => $marketplace], $id);
            if ($existing) {
                $data['_id'] = (string)$existing['_id'];
                $data['source_marketplace'] = $marketplace;
            } else {
                $data['_id'] = (string)$this->getCounter('product_id');
                $data['user_id'] = $id;
                $data['shop_id'] = (string)$home_shop_id;

                if (!isset($data['source_product_id'])) {
                    $data['source_product_id'] = $data['_id'];
                }

                if (!isset($data['container_id'])) {
                    $data['container_id'] = $data['source_product_id'];
                }

                $data['created_at'] = date("Y-m-d H:i:s");

                if (!isset($data['variant_attributes']) || count($data['variant_attributes']) === 0) {
                    $data['visibility'] = "Catalog and Search";
                } else {
                    $data['visibility'] = "Not Visible Individually";
                }
                //                $data['type'] = "simple";
                $data['source_marketplace'] = $marketplace;
                $data = $this->validateVariantData($data);

                if (isset($data['message']) && $data['success'] === false) {
                    return $data;
                }
            }
            $eventsManager = $this->di->getEventsManager();
            $eventsManager->fire('application:productSaveBefore', $this, ['product_data' => &$data]);

            $product_container = $this->setData($data);

            $status = $this->handleLock('product_container', function () use ($product_container) {
                return $product_container->save();
            });

            if (isset($targetEventData['target_marketplace'], $targetEventData['target_shop_id'])) {
                $data['targetEventData']['target_marketplace'] = $targetEventData['target_marketplace'];
                $data['targetEventData']['target_shop_id'] = $targetEventData['target_shop_id'];
            }

            $eventsManager = $this->di->getEventsManager();
            $eventsManager->fire('application:productSaveAfter', $this, $data);
            return [
                'success' => true,
                'message' => 'Variant Created Successfully',
                'data' => $data['_id'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    public function validateVariantData($variant)
    {
        foreach (self::VARIANT_FIELDS_REQUIRED as $key => $type) {
            if (!isset($variant[$key])) {
                return ['success' => false, 'message' => 'Required Fields ' . $key . ' is missing'];
            }
            switch ($type) {
                case "int":
                    $variant[$key] = (int)$variant[$key];
                    break;
                case "float":
                    $variant[$key] = (float)$variant[$key];
                    break;
                case "string":
                    $variant[$key] = (string)$variant[$key];
                    break;
                default:
                    break;
            }
        }

        foreach (self::DEFAULT_FIELDS_FOR_VARIANT as $key => $defaultValue) {
            if (!isset($variant[$key])) {
                $variant[$key] = $defaultValue;
            }
        }

        return $variant;
    }

    public function validateDetailsData($details)
    {
        foreach (self::VI_PRODUCT_FIELDS_REQUIRED as $key => $type) {
            if (!isset($details[$key])) {
                return ['success' => false, 'message' => 'Required Fields ' . $key . ' is missing'];
            }
            switch ($type) {
                case "int":
                    $details[$key] = (int)$details[$key];
                    break;
                case "float":
                    $details[$key] = (float)$details[$key];
                    break;
                case "string":
                    $details[$key] = (string)$details[$key];
                    break;
                default:
                    break;
            }
        }

        foreach (self::DEFAULT_FIELDS as $key => $defaultValue) {
            if (!isset($details[$key])) {
                $details[$key] = $defaultValue;
            }
        }
        return $details;
    }

    public function saveMainImage($encodedFile, $count, $merchantId)
    {
        $value = substr($encodedFile['encoded_file'], strpos($encodedFile['encoded_file'], ','), strlen($encodedFile['encoded_file']));
        $decoded_image = base64_decode($value);
        $ext = explode('.', $encodedFile['file_name']);
        $fileName = 'image_' . $count . '_' . time() . '.' . $ext[1];
        $path = 'media' . DS . 'temp' . DS . $fileName;
        $url = 'media' . DS . 'product' . DS . $merchantId . DS . $fileName;
        $handle = fopen($path, 'w');
        fwrite($handle, $decoded_image);
        return $url;
    }

    public function saveAdditionalImages($additionalImgs, $count, $merchantId)
    {
        $additionalImages = '';
        $img_count = 1;
        foreach ($additionalImgs as $key => $varAdditionalImgs) {
            if (isset($varAdditionalImgs['encoded_file'])) {
                $value = substr($varAdditionalImgs['encoded_file'], strpos($varAdditionalImgs['encoded_file'], ','), strlen($varAdditionalImgs['encoded_file']));
                $decoded_image = base64_decode($value);
                $ext = explode('.', $varAdditionalImgs['file_name']);
                $fileName = 'variant_image_' . $count . '' . $img_count . '_' . time() . '.' . $ext[1];
                $path = 'media' . DS . 'temp' . DS . $fileName;
                $url = 'media' . DS . 'product' . DS . $merchantId . DS . $fileName;
                $handle = fopen($path, 'w');
                fwrite($handle, $decoded_image);
                $additionalImages = $additionalImages . $url . ',';
            } else {
                $additionalImages = $additionalImages . substr($varAdditionalImgs, strlen($this->di->getUrl()->get()), strlen($varAdditionalImgs)) . ',';
            }
            $img_count = $img_count + 1;
        }
        return $additionalImages;
    }

    /**
     * Here we can find the product by key value
     * @param $condition
     * @return mixed
     */
    public function loadByField($condition, $id = null)
    {
        if (!$id) {
            $id = $this->di->getUser()->id;
        }

        $condition['user_id'] = (string)$id;
        $collection = $this->getCollection();
        // $options = ["typeMap" => ['root' => 'array', 'document' => 'array']];
        $containerValues = $collection->findOne($condition);

        return $containerValues;
    }

    /**
     * Get a single product by id/source_product_id
     * @param $productIds
     * @return array
     */
    /*public function getProduct($productIds)
    {
    if (isset($productIds['id'])) {
    $containerValues = $this->loadByField(["_id" => $productIds['id']]);
    } elseif (isset($productIds['source_product_id'])) {
    $containerValues = $this->loadByField(["details.source_product_id" => $productIds['source_product_id']]);
    } else {
    return [
    'success' => false,
    'message' => 'Please input id or source_product_id',
    'code' => 'invalid_data'
    ];
    }

    if ($containerValues) {
    return [
    'success' => true,
    'message' => 'Product Data',
    'data' => $containerValues
    ];
    } else {
    return [
    'success' => false,
    'message' => 'Product not found',
    'code' => 'not_found'
    ];
    }
    }*/

    public function getProduct($productIds)
    {
        if (isset($productIds['id'])) {
            $containerValues = $this->loadByField(["_id" => $productIds['id']]);
        } elseif (isset($productIds['source_product_id'])) {

            /*$this->di->getLog()->logContent('getsource = '.print_r($this->getSource(), true),'info','product_container.log');
            $this->di->getLog()->logContent('details='.$this->di->getUser()->id,'info','product_container.log');
            $this->di->getLog()->logContent('details.source.id ='.$productIds['source_product_id'],'info','product_container.log');*/

            $containerValues = $this->loadByField(["details.source_product_id" => $productIds['source_product_id']]);
        } else {
            return [
                'success' => false,
                'message' => 'Please input id or source_product_id',
                'code' => 'invalid_data',
            ];
        }

        if ($containerValues) {
            return [
                'success' => true,
                'message' => 'Product Data',
                'data' => $containerValues,
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Product not found',
                'code' => 'not_found',
            ];
        }
    }

    public function getFormBuilderJson($attributes, $attributeValues, $configAttribute)
    {
        $configAttribute = $configAttribute ? explode(',', $configAttribute) : [];
        $optionsTable = new \App\Connector\Models\ProductAttributeOption();
        $formJson = [];
        foreach ($attributes as $containerAttr_value) {
            if (in_array($containerAttr_value['code'], $configAttribute)) {
                continue;
            }
            switch ($containerAttr_value['frontend_type']) {
                case 'checkbox':
                    $options = $optionsTable::find(["attribute_id='{$containerAttr_value['id']}'"]);
                    $attrOptions = [];
                    $oldValues = json_decode($attributeValues[$containerAttr_value['code']]);
                    if (!is_array($oldValues)) {
                        $tempVal = $oldValues;
                        $oldValues = [];
                        $oldValues[0] = $tempVal;
                    }
                    $newvalues = [];
                    foreach ($options->toArray() as $optionVal) {
                        if (in_array($optionVal['id'], $oldValues)) {
                            $newvalues[] = $optionVal['value'];
                        }
                        $attrOptions[] = ['id' => $optionVal['value'], 'value' => $optionVal['value']];
                    }
                    array_push($formJson, [
                        'attribute' => $containerAttr_value['label'],
                        'key' => $containerAttr_value['code'],
                        'field' => $containerAttr_value['frontend_type'],
                        'data' => [
                            "value" => $newvalues,
                            "values" => $attrOptions,
                            "required" => $containerAttr_value['is_required'],
                        ],
                    ]);
                    break;
                case 'dropdown':
                    $options = $optionsTable::find(["attribute_id='{$containerAttr_value['id']}'"]);
                    $attrOptions = [];
                    $value = '';
                    foreach ($options->toArray() as $optionValue) {
                        if ($optionValue['id'] == $attributeValues[$containerAttr_value['code']]) {
                            $value = $optionValue['value'];
                        }
                        $attrOptions[] = ['id' => $optionValue['value'], 'value' => $optionValue['value']];
                    }
                    array_push($formJson, [
                        'attribute' => $containerAttr_value['label'],
                        'key' => $containerAttr_value['code'],
                        'field' => $containerAttr_value['frontend_type'],
                        'data' => [
                            "value" => $value,
                            "values" => $attrOptions,
                            "required" => $containerAttr_value['is_required'],
                        ],
                    ]);
                    break;
                case 'fileupload':
                    array_push($formJson, [
                        'attribute' => $containerAttr_value['label'],
                        'key' => $containerAttr_value['code'],
                        'field' => $containerAttr_value['frontend_type'],
                        'data' => [
                            "value" => $this->di->getUrl()->get() . $attributeValues[$containerAttr_value['code']],
                            "required" => $containerAttr_value['is_required'],
                        ],
                    ]);
                    break;
                case 'multipleinputs':
                    $multipleInputsValue = json_decode($attributeValues[$containerAttr_value['code']], true);
                    array_push($formJson, [
                        'attribute' => $containerAttr_value['label'],
                        'key' => $containerAttr_value['code'],
                        'field' => $containerAttr_value['frontend_type'],
                        'data' => [
                            "value" => $multipleInputsValue == null ? [] : $multipleInputsValue,
                            "required" => $containerAttr_value['is_required'],
                        ],
                    ]);
                    break;
                case 'radio':
                    $options = $optionsTable::find(["attribute_id='{$containerAttr_value['id']}'"]);
                    $attrOptions = [];
                    $value = '';
                    foreach ($options->toArray() as $optionValue) {
                        if ($optionValue['id'] == $attributeValues[$containerAttr_value['code']]) {
                            $value = $optionValue['value'];
                        }
                        $attrOptions[] = ['id' => $optionValue['value'], 'value' => $optionValue['value']];
                    }
                    array_push($formJson, [
                        'attribute' => $containerAttr_value['label'],
                        'key' => $containerAttr_value['code'],
                        'field' => $containerAttr_value['frontend_type'],
                        'data' => [
                            "value" => $value,
                            "display" => "inline",
                            "values" => $attrOptions,
                            "required" => $containerAttr_value['is_required'],
                        ],
                    ]);
                    break;
                case 'textarea':
                    array_push($formJson, [
                        'attribute' => $containerAttr_value['label'],
                        'key' => $containerAttr_value['code'],
                        'field' => $containerAttr_value['frontend_type'],
                        'data' => [
                            "value" => $attributeValues[$containerAttr_value['code']],
                            "placeholder" => $containerAttr_value['label'],
                            "required" => $containerAttr_value['is_required'],
                            "maxLength" => 1000,
                        ],
                    ]);
                    break;
                case 'textfield':
                    $fieldType = '';
                    if ($containerAttr_value['backend_type'] == 'varchar' || $containerAttr_value['backend_type'] == 'text') {
                        $fieldType = 'text';
                    } else {
                        $fieldType = 'number';
                    }
                    if (isset($attributeValues[$containerAttr_value['code']])) {
                        array_push($formJson, [
                            'attribute' => $containerAttr_value['label'],
                            'key' => $containerAttr_value['code'],
                            'field' => $containerAttr_value['frontend_type'],
                            'data' => [
                                "type" => $fieldType,
                                "value" => $attributeValues[$containerAttr_value['code']],
                                "placeholder" => $containerAttr_value['label'],
                                "required" => $containerAttr_value['is_required'],
                            ],
                        ]);
                    }
                    break;
                case 'toggle':
                    array_push($formJson, [
                        'attribute' => $containerAttr_value['label'],
                        'key' => $containerAttr_value['code'],
                        'field' => $containerAttr_value['frontend_type'],
                        'data' => [
                            "value" => $attributeValues[$containerAttr_value['code']],
                            "required" => $containerAttr_value['is_required'],
                        ],
                    ]);
                    break;
                case 'calendar':
                    array_push($formJson, [
                        'attribute' => $containerAttr_value['label'],
                        'key' => $containerAttr_value['code'],
                        'field' => $containerAttr_value['frontend_type'],
                        'data' => [
                            "value" => $attributeValues[$containerAttr_value['code']],
                            "required" => $containerAttr_value['is_required'],
                            "minYear" => 1970,
                            "maxYear" => 2045,
                            "displayFormat" => "DD-MM-YYYY",
                        ],
                    ]);
                    break;
            }
        }
        return $formJson;
    }

    /**
     * @param $ids
     * @return array
     */
    public function deleteMultipleProducts($ids)
    {
        $errors = '';
        if ($ids['products'] == 'all') {
            $productIds = [];
            $allProducts = self::find(['columns' => 'id'])->toArray();
            foreach ($allProducts as $key => $value) {
                $productIds[] = [
                    'id' => $value['id'],
                ];
            }
            foreach ($productIds as $value) {
                $response = $this->deleteProduct($value);
                if (!$response['success']) {
                    $errors .= 'Error in product id ' . $value['id'] . ' -> ' . $response['message'] . ', ';
                }
            }
        } else {
            foreach ($ids['products'] as $value) {
                $response = $this->deleteProduct($value);
                if (!$response['success']) {
                    $errors .= 'Error in product id ' . $value['id'] . ' -> ' . $response['message'] . ', ';
                }
            }
        }
        if ($errors == '') {
            $errors = 'All products deleted successfully';
        }
        return ['success' => true, 'message' => $errors];
    }

    /**
     * Delete the product from ,merchant specific product table
     * @param $productIds
     * @return array
     */
    public function deleteProduct($productIds)
    {
        $collection = $this->getCollection();
        if (isset($productIds['id'])) {
            $containerValues = $this->loadByField(["_id" => $productIds['id']]);
        } elseif (isset($productIds['source_product_id'])) {
            $containerValues = $this->loadByField(["container_id" => $productIds['source_product_id']]);
        } else {
            return [
                'success' => false,
                'message' => 'Please input id or source_product_id',
                'code' => 'invalid_data',
            ];
        }
        $this->di->getLog()->logContent('Container == ' . print_r($containerValues, true), 'info', 'shopify' . DS . $this->di->getUser()->id . DS . 'product' . DS . date("Y-m-d") . DS . 'webhook_product_delete.log');
        $merchantId = $this->di->getUser()->id;
        if ($containerValues) {
            $containerId = false;
            if (is_array($containerValues)) {
                $containerId = $containerValues['_id'];
            } else {
                $containerId = $containerValues->_id;
            }
            $marketplaceProduct = $this->di->getObjectManager()
                ->create('\App\Connector\Models\MarketplaceProduct');
            if (isset($productIds['marketplace'])) {
                $marketplaceProduct->deleteProduct(
                    $merchantId,
                    $productIds['marketplace'],
                    ['_id' => $containerId]
                );
                return [
                    'success' => true,
                    'message' => $productIds['marketplace'] . ' Product Deleted Successfully',
                    'data' => [$containerId],
                ];
            } else {
                if ($containerValues['type'] == "variation" && $containerValues['visibility'] == "Catalog and Search") {
                    $allProductVariants = $collection->find(['container_id' => (string)$containerValues['source_product_id'], 'user_id' => $merchantId])->toArray();
                    $_ids = [];
                    foreach ($allProductVariants as $variant) {
                        $_ids[] = $variant['_id'];
                    }
                    $this->di->getLog()->logContent('_id Array == ' . print_r($_ids, true), 'info', 'shopify' . DS . $this->di->getUser()->id . DS . 'product' . DS . date("Y-m-d") . DS . 'webhook_product_delete.log');
                    $status = $collection->deleteMany(['_id' => ['$in' => $_ids]], ['w' => true]);
                } else {
                    $status = $collection->deleteOne(['_id' => $containerId], ['w' => true]);
                }
            }
            if ($status->isAcknowledged()) {
                $allMarketplace = $this->di->getObjectManager()
                    ->get('\App\Connector\Components\Connectors')
                    ->getConnectorsWithFilter(['installed' => 1], $merchantId);
                foreach ($allMarketplace as $key => $marketplace) {
                    $marketplaceProduct->deleteProduct(
                        $merchantId,
                        $marketplace['code'],
                        ['_id' => $containerId]
                    );
                }
                if (isset($_ids) && count($_ids) > 0) {
                    $containerIds = $_ids;
                } else {
                    $containerIds[] = $containerId;
                }
                return [
                    'success' => true,
                    'message' => 'Product Deleted Successfully',
                    'data' => implode(',', $containerIds),
                ];
            } else {
                return [
                    'success' => false,
                    'code' => 'error_in_delete',
                    'message' => 'Error occur in product deletion',
                    'data' => [],
                ];
            }
        } else {
            return [
                'success' => false,
                'code' => 'product_not_found',
                'message' => 'No product found',
            ];
        }
    }

    public static function search($filterParams = [])
    {
        $conditions = [];
        if (isset($filterParams['filter'])) {
            foreach ($filterParams['filter'] as $key => $value) {
                $key = trim($key);
                if ($key === 'template_name') {
                    // $conditions["profile.profile_id"] = $value[self::IS_EQUAL_TO];
                    $conditions["profile.profile_name"] = $value[self::IS_EQUAL_TO];
                } elseif ($key === 'marketplace.status') {

                    if ($value[self::IS_EQUAL_TO] === 'Disabled' || $value[self::IS_EQUAL_TO] === 'Not Listed') {
                        $disabledFilter = [];
                        $disabledFilter[] = ["marketplace.status" => ['$exists' => 0], "marketplace.target_marketplace" => 'amazon'];

                        $disabledFilter[] = ["marketplace.status" => ['$nin' => ['Active', 'Inactive', 'Incomplete', 'Supressed', 'Available for Offer']]];

                        $conditions['$or'] = $disabledFilter;
                    } else {
                        $conditions["marketplace.status"] = $value[self::IS_EQUAL_TO];
                    }
                    // print_r($conditions);die;
                } elseif ($key === 'status') {
                    $marketplace = $filterParams['marketplace'] ?? '';
                    if ($marketplace) {
                        $conditions["visibility"] = 'Catalog and Search';

                        if ($value[self::IS_EQUAL_TO] === 'Disabled' || $value[self::IS_EQUAL_TO] === 'Not_listed') {
                            $disabledFilter = [];
                            $disabledFilter[] = ["marketplace.{$marketplace}" => ['$exists' => 0]];

                            $disabledFilter[] = ["marketplace.{$marketplace}.status" => ['$nin' => ['Active', 'Inactive', 'Incomplete', 'Supressed', 'Available for Offer', 'Uploaded']]];

                            $conditions['$or'] = $disabledFilter;
                        } else {
                            $conditions["marketplace.{$marketplace}.status"] = $value[self::IS_EQUAL_TO];
                        }
                    }
                } elseif ($key === 'variant_status') {
                    $marketplace = $filterParams['marketplace'] ?? '';
                    if ($marketplace) {
                        if ($value[self::IS_EQUAL_TO] === 'Disabled' || $value[self::IS_EQUAL_TO] === 'Not_listed') {
                            $disabledFilter = [];
                            $disabledFilter[] = ["marketplace.{$marketplace}" => ['$exists' => 0]];

                            $disabledFilter[] = ["marketplace.{$marketplace}.status" => ['$nin' => ['Active', 'Inactive', 'Incomplete', 'Supressed', 'Available for Offer']]];

                            $conditions['$or'] = $disabledFilter;
                        } else {
                            $conditions["marketplace.{$marketplace}.status"] = $value[self::IS_EQUAL_TO];
                        }
                    }
                } else {
                    if (array_key_exists(self::IS_EQUAL_TO, $value)) {
                        $conditions[$key] = self::checkInteger($key, $value[self::IS_EQUAL_TO]);
                    } elseif (array_key_exists(self::IS_NOT_EQUAL_TO, $value)) {
                        $conditions[$key] = ['$ne' => self::checkInteger($key, trim($value[self::IS_NOT_EQUAL_TO]))];
                    } elseif (array_key_exists(self::IS_CONTAINS, $value)) {
                        $conditions[$key] = [
                            '$regex' => self::checkInteger($key, trim(addslashes($value[self::IS_CONTAINS]))),
                            '$options' => 'i',
                        ];
                    } elseif (array_key_exists(self::IS_NOT_CONTAINS, $value)) {
                        $conditions[$key] = [
                            '$regex' => "^((?!" . self::checkInteger($key, trim(addslashes($value[self::IS_NOT_CONTAINS]))) . ").)*$",
                            '$options' => 'i',
                        ];
                    } elseif (array_key_exists(self::START_FROM, $value)) {
                        $conditions[$key] = [
                            '$regex' => "^" . self::checkInteger($key, trim(addslashes($value[self::START_FROM]))),
                            '$options' => 'i',
                        ];
                    } elseif (array_key_exists(self::END_FROM, $value)) {
                        $conditions[$key] = [
                            '$regex' => self::checkInteger($key, trim(addslashes($value[self::END_FROM]))) . "$",
                            '$options' => 'i',
                        ];
                    } elseif (array_key_exists(self::RANGE, $value)) {
                        if (trim($value[self::RANGE]['from']) && !trim($value[self::RANGE]['to'])) {
                            $conditions[$key] = ['$gte' => self::checkInteger($key, trim($value[self::RANGE]['from']))];
                        } elseif (
                            trim($value[self::RANGE]['to']) &&
                            !trim($value[self::RANGE]['from'])
                        ) {
                            $conditions[$key] = ['$lte' => self::checkInteger($key, trim($value[self::RANGE]['to']))];
                        } else {
                            $conditions[$key] = [
                                '$gte' => self::checkInteger($key, trim($value[self::RANGE]['from'])),
                                '$lte' => self::checkInteger($key, trim($value[self::RANGE]['to'])),
                            ];
                        }
                    } elseif (array_key_exists(self::IS_GREATER_THAN, $value)) {
                        if (is_numeric(trim($value[self::IS_GREATER_THAN]))) {
                            $conditions[$key] = ['$gte' => self::checkInteger($key, trim($value[self::IS_GREATER_THAN]))];
                        }
                    } elseif (array_key_exists(self::IS_LESS_THAN, $value)) {
                        if (is_numeric(trim($value[self::IS_LESS_THAN]))) {
                            $conditions[$key] = ['$lte' => self::checkInteger($key, trim($value[self::IS_LESS_THAN]))];
                        }
                    }
                }
            }
        }
        if (isset($filterParams['search'])) {
            $conditions['$text'] = ['$search' => self::checkInteger($key, trim(addslashes($filterParams['search'])))];
        }
        return $conditions;
    }

    public static function checkInteger($key, $value)
    {
        if (
            $key == 'price' ||
            $key == 'quantity'
        ) {
            $value = trim($value);
            return (float)$value;
        }
        return trim($value);
    }

    public function updateProduct($data, $userId = false)
    {
        if (isset($data['marketplace'])) {
            $connectorHelper = $this->di->getObjectManager()
                ->get('App\Connector\Components\Connectors')
                ->getConnectorModelByCode($data['marketplace']);

            if ($connectorHelper) {
                return $connectorHelper->updateProduct($data, $userId);
            }
        }
    }

    public function syncWithSource($data)
    {
        if (isset($data['marketplace'])) {
            $connectorHelper = $this->di->getObjectManager()
                ->get('App\Connector\Components\Connectors')
                ->getConnectorModelByCode($data['marketplace']);

            if ($connectorHelper) {
                return $connectorHelper->syncWithSource($data);
            }
        }
    }

    public function importCSV($data)
    {
        if (isset($data['marketplace'])) {
            $connectorHelper = $this->di->getObjectManager()
                ->get('App\Connector\Components\Connectors')
                ->getConnectorModelByCode($data['marketplace']);

            if ($connectorHelper) {
                return $connectorHelper->importCSV($data);
            }
        }
    }

    public function exportProductCSV($data)
    {
        if (isset($data['marketplace'])) {
            $connectorHelper = $this->di->getObjectManager()
                ->get('App\Connector\Components\Connectors')
                ->getConnectorModelByCode($data['marketplace']);

            if ($connectorHelper) {
                return $connectorHelper->exportProductCSV($data);
            }
        }
    }

    public function enableDisable($data)
    {
        if (isset($data['marketplace'])) {
            $connectorHelper = $this->di->getObjectManager()
                ->get('App\Connector\Components\Connectors')
                ->getConnectorModelByCode($data['marketplace']);

            if ($connectorHelper) {
                return $connectorHelper->enableDisable($data);
            }
        }
    }

    public function syncData($data)
    {
        if (isset($data['source'])) {
            $connectorHelper = $this->di->getObjectManager()
                ->get('App\Connector\Components\Connectors')
                ->getConnectorModelByCode($data['source']);
            if ($connectorHelper) {
                return $connectorHelper->initiateSelectAndSync($data);
            }
        }
        return false;
    }

    public function createUserCatalogBrands($brands, $userId, $marketplace)
    {
        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $collection = $mongo->getCollectionForTable("store_catalog_brand");

        $brands['source_marketplace'] = $marketplace;
        $brands['user_id'] = $userId;

        $getBrands = $collection->findOne(['$and' => [['source_marketplace' => $marketplace], ['user_id' => $userId]]]);
        $getBrands = json_decode(json_encode($getBrands), true);

        if (!empty($getBrands)) {
            $newBrands = [];
            foreach ($brands['data'] as $brandId => $new_brand) {
                if (!array_key_exists($brandId, $getBrands['data'])) {
                    $newBrands[$brandId] = $new_brand;
                } else {
                    $newBrands[$brandId] = $getBrands['data'][$brandId];
                }
            }

            $brandCollection['data'] = $newBrands;
            $brandCollection['user_id'] = $brands['user_id'];
            $brandCollection['source_marketplace'] = $brands['source_marketplace'];
            if (!in_array($brands['app_code'], $getBrands['app_codes'])) {
                $appCode = $brands['app_code'];
                $brandCollection['app_codes'][] = $appCode;
            } else {
                $brandCollection['app_codes'] = $getBrands['app_codes'];
            }

            try {
                $res = $collection->updateOne(['$and' => [['source_marketplace' => $marketplace], ['user_id' => $userId]]], ['$set' => $brandCollection]);
            } catch (\Exception $e) {
                return ['errorFlag' => 'true', 'message' => $e->getMessage(), 'status' => 'exception while update'];
            }
            return ['key' => 'updated', 'message' => 'Updated', 'user_id' => $userId, 'res' => $res->getModifiedCount()];
        } else {
            try {
                if (isset($brands['app_code'])) {
                    $app_code = $brands['app_code'];
                    $brands['app_codes'] = [$app_code];
                }
                $res = $collection->insertOne($brands);
            } catch (\Exception $e) {
                return ['errorFlag' => 'true', 'message' => $e->getMessage(), 'status' => 'exception while insert'];
            }
            return ['key' => 'inserted', 'message' => 'Inserted', 'user_id' => $brands['user_id'], 'res' => $res->getInsertedCount()];
        }
    }

    public function createUserCatalogCategory($category, $userId, $marketplace)
    {
        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $collection = $mongo->getCollectionForTable("store_catalog_category");

        $category['source_marketplace'] = $marketplace;
        $category['user_id'] = $userId;

        $getCategory = $collection->findOne(['$and' => [['source_marketplace' => $marketplace], ['user_id' => $userId]]]);
        $getCategory = json_decode(json_encode($getCategory), true);

        if (!empty($getCategory)) {
            $newCategories = [];
            foreach ($category['data'] as $categoryId => $new_category) {
                if (!array_key_exists($categoryId, $getCategory['data'])) {
                    $newCategories[$categoryId] = $new_category;
                } else {
                    $newCategories[$categoryId] = $getCategory['data'][$categoryId];
                }
            }

            $newCategory['data'] = $newCategories;
            $newCategory['user_id'] = $category['user_id'];
            $newCategory['source_marketplace'] = $category['source_marketplace'];
            if (!in_array($category['app_code'], $getCategory['app_codes'])) {
                $appCode = $category['app_code'];
                $newCategory['app_codes'][] = $appCode;
            } else {
                $newCategory['app_codes'] = $getCategory['app_codes'];
            }

            try {
                $res = $collection->updateOne(['$and' => [['source_marketplace' => $marketplace], ['user_id' => $userId]]], ['$set' => $newCategory]);
            } catch (\Exception $e) {
                return ['errorFlag' => 'true', 'message' => $e->getMessage(), 'status' => 'exception while update'];
            }
            return ['key' => 'updated', 'message' => 'Updated', 'user_id' => $userId, 'res' => $res->getModifiedCount()];
        } else {
            try {
                if (isset($category['app_code'])) {
                    $app_code = $category['app_code'];
                    $category['app_codes'] = [$app_code];
                }
                $res = $collection->insertOne($category);
            } catch (\Exception $e) {
                return ['errorFlag' => 'true', 'message' => $e->getMessage(), 'status' => 'exception while insert'];
            }
            return ['key' => 'inserted', 'message' => 'Inserted', 'user_id' => $userId, 'res' => $res->getInsertedCount()];
        }
    }

    /**
     * This function is used to get all the product statuses
     * @param $data
     * @return array
     */
    public function getAllProductStatuses($data)
    {
        $productStatus = [];
        $returnArray = [];

        try {
            if (isset($data['target_marketplace'])) {
                $target_marketplace = $data['target_marketplace'];
                $model = $this->di->getObjectManager()->get($this->di->getConfig()->connectors->get($target_marketplace)->get('source_model'));
                if (method_exists($model, 'getAllProductStatuses')) {
                    $productStatus = $model->getAllProductStatuses();
                    $returnArray = $productStatus;
                } else {
                    $returnArray = ['status' => false, 'message' => 'Method not exist'];
                }
            } else {
                $returnArray = ['status' => false, 'message' => 'Target marketplace is empty'];
            }
        } catch (\Exception $e) {
            $returnArray = ['status' => false, 'message' => $e->getMessage()];
        }
        return $returnArray;
    }

    public function getStatusWiseFilterCount($params)
    {

        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $collection = $mongo->setSource("product_container")->getPhpCollection();
        $countAggregation = $this->buildAggregateQuery($params, 'productCount');
        $totalCount = [];
        $totalCount[] = [
            '$match' => [
                'user_id' => $this->di->getUser()->id,
                'type' => 'simple'
            ],
        ];
        if (isset($params['filter']) || isset($params['or_filter'])) {
            $totalCount = $this->buildAggregateQuery($params);
        }

        $countAggregation[0]['$match']['marketplace.target_marketplace'] = $params['target_marketplace'];
        $countAggregation[] = [
            '$unwind' => '$marketplace',
        ];
        $countAggregation[] = [
            '$match' =>
                [
                    'user_id' => $this->di->getUser()->id,
                    'marketplace.target_marketplace' => $params['target_marketplace'],
                ]
        ];


        $totalCount[] = [
            '$count' => 'count',
        ];

        $countAggregation[] = [
            '$group' => ['_id' => '$marketplace.status', 'total' => ['$sum' => 1]]
        ];

        $countAggregation[] = [
            '$match' =>
            [
                '_id' => ['$ne' => null],
            ]
        ];

        try {
            $totalVariantsRows = $collection->aggregate($countAggregation)->toArray();
            $totalQueryCount = $collection->aggregate($totalCount)->toArray()[0]['count'];
            $TotalNotListed = 0;
            foreach ($totalVariantsRows as $idKey => $sVAlue) {
                if ($sVAlue['_id'] == null || $sVAlue['_id'] == "Not Listed" /*|| $idKey === "Unknown"*/) continue;
                $TotalNotListed = $TotalNotListed + $sVAlue['total'];
            }
            $TotalNotListed = $totalQueryCount - $TotalNotListed;
            $flag = false;
            foreach ($totalVariantsRows as $idKey => $sVAlue) {
                if ($sVAlue['_id'] == "Not Listed") {
                    $flag = true;
                    $totalVariantsRows[$idKey]['total'] = $TotalNotListed;
                }
            }
            if ( !$flag ) {
                $totalVariantsRows[] = [
                    '_id' => 'Not Listed',
                    'total' => $TotalNotListed
                ];
            }
            $responseData = [
                'success' => true,
                'query' => $countAggregation,
                'queryForAll' => $totalCount,
                "param" => $params,
                'data' => $totalVariantsRows,
                "allListing" => $totalQueryCount
            ];
        } catch (\Exception $e) {
            $responseData = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
        return ($responseData);
    }

    /**
     * This function is used to get all the attributes of products
     *
     * @param $data
     * @return array
     */
    public function getProductAttributes($data)
    {
        try {
            if (isset($data['source_marketplace'])) {
                $source_marketplace = $data['source_marketplace'];
                $model = $this->di->getObjectManager()->get($this->di->getConfig()->connectors->get($source_marketplace)->get('source_model'));
                if (method_exists($model, 'getProductAttributes')) {
                    $productAttributes = $model->getProductAttributes($data);
                    $returnArray = $productAttributes;
                } else {
                    $returnArray = ['status' => false, 'message' => 'Method not exist'];
                }
            } else {
                $returnArray = ['status' => false, 'message' => 'Source marketplace is empty'];
            }
        } catch (\Exception $e) {
            $returnArray = ['status' => false, 'message' => $e->getMessage()];
        }
        return $returnArray;
    }


    /**
     * This function is used to get the single product status as per shop_id
     *
     * @param $data
     * @param $shop_id
     * @return array
     */
    public function getProductStatusByShopId($data, $shop_id)
    {
        try {
            if (isset($data['source_marketplace'])) {
                $source_marketplace = $data['source_marketplace'];
                $model = $this->di->getObjectManager()->get($this->di->getConfig()->connectors->get($source_marketplace)->get('source_model'));
                if (method_exists($model, 'getProductStatusByShopId')) {
                    $productAttributes = $model->getProductStatusByShopId($data,$shop_id);
                    $returnArray = $productAttributes;
                } else {
                    $returnArray = ['status' => false, 'message' => 'Method not exist'];
                }
            } else {
                $returnArray = ['status' => false, 'message' => 'Source marketplace is empty'];
            }
        } catch (\Exception $e) {
            $returnArray = ['status' => false, 'message' => $e->getMessage()];
        }
        return $returnArray;
    }
}
