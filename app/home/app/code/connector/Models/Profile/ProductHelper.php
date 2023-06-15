<?php

namespace App\Connector\Models\Profile;

use App\Core\Models\BaseMongo;

class ProductHelper extends BaseMongo
{
    public $target_marketplace;
    public $profile_id;
    public $target_shop_id;
    public $warehouse_id;
    public $source_maketplace;
    public $process_data;
    public $source_shop_id;
    public $profile_data;
    public $source_shop_warehouse_id;
    public $container_ids;
    public $app_tag;


    public function requiredKey()
    {
        return [
            'profile_id',
            'target_marketplace',
            'target_shop_id',
            'warehouse_id',
            'source_maketplace',
            'source_shop_id',
            'source_shop_warehouse_id',
            'app_tag'
        ];

    }

    public function requiredKeyWithProductIds()
    {
        return [
            'target_marketplace',
            'target_shop_id',
            'warehouse_id',
            'source_maketplace',
            'source_shop_id',
            'source_shop_warehouse_id',
            'app_tag'
        ];

    }

    public function validateData()
    {
        $requiredKey = $this->requiredKey();

        foreach ($requiredKey as $key => $value) {
            if (isset($this->process_data[$value])) {
                $this->{$value} = $this->process_data[$value];
            } else {
                return ['success' => false, 'message' => "{$value} Key missing"];
            }
        }
    }

    public function validateDataWithProductIds()
    {
        $requiredKey = $this->requiredKeyWithProductIds();

        foreach ($requiredKey as $key => $value) {
            if (isset($this->process_data[$value])) {
                $this->{$value} = $this->process_data[$value];
            } else {
                return ['success' => false, 'message' => "{$value} Key missing"];
            }
        }
    }


    public function getProductByProfileId()
    {

        $validateData = $this->validateData();
        if (isset($validateData['success']) && !$validateData['success']) {
            return $validateData;
        }

        $profileData = $this->getProfileData();

        if(!empty($profileData)){
            $allProductData = $this->getProduct();
            if (isset($allProductData['success']) && !$allProductData['success']) {
                return $allProductData;
            }
            $productData = $allProductData['data'];
            $prepareProduct = $this->prepareProduct($productData);
            $prepareProduct['next'] = $allProductData['next'];
            return $prepareProduct;
        } else {
            return ['success'=>false,'message'=>'profile data is empty'];
        }
    }

    public function prepareProfileAttribute() 
    {
        $helperDataArr = [];
        $helperDataArr['profile_data'] = $this->profile_data;
        $helperDataArr['marketplace'] = $this->target_marketplace;
        $helperDataArr['source_marketplace'] = $this->source_maketplace;

        $profileHelperObj = new Helper();
        $profileHelperObj->process_data = $helperDataArr;
        $attributePath = "targets.{$this->target_marketplace}.shops.{$this->target_shop_id}.warehouses.{$this->warehouse_id}.sources.{$this->source_maketplace}.shops.{$this->source_shop_id}.warehouses.{$this->source_shop_warehouse_id}.attributes_mapping";

        $attributeRes = $profileHelperObj->getProfileAttribute($attributePath);

        return $attributeRes;

    }

    public function prepareProduct($productData)
    {
        $attributeRes = $this->prepareProfileAttribute();

        if (isset($attributeRes['success'])) {
                $mappedAttributes = $attributeRes['data'];

                foreach ($productData as $productCol => $product) {
                    $marketplaceIndex = $this->process_data['target_marketplace'] . "_marketplace";
                    $editedAData = [];

                    if(!empty($product[$marketplaceIndex])){
                        foreach ($product[$marketplaceIndex] as $key => $maketplaceConatainerData) {
                            $editedAData[$maketplaceConatainerData['source_product_id']] = $maketplaceConatainerData;
                        }
                    }
                    if(!empty($editedAData) && isset($editedAData[$product['source_product_id']])){
                        $product = array_merge($product,$editedAData[$product['source_product_id']]);
                    }

                    foreach ($mappedAttributes as $columnName => $mappedData) {

                        $namespace = "\\App\\Connector\\Models\\Profile\\Attribute\\Type\\" . ucfirst($mappedData['type']);
                        if (class_exists($namespace)) {
                            $obj = new $namespace();
                            $product = $obj->changeData($columnName, $mappedData, $product);
                        }

                    }

                    if (!empty($product['variants'])) {
                        foreach ($product['variants'] as $proCol => $variant) {
                            if(!empty($editedAData) && isset($editedAData[$variant['source_product_id']])){
                                $variant = array_merge($product,$editedAData[$variant['source_product_id']]);
                            }

                            foreach ($mappedAttributes as $columnName => $mappedData) {
                                $namespace = "\\App\\Connector\\Models\\Profile\\Attribute\\Type\\" . ucfirst($mappedData['type']);
                                if (class_exists($namespace)) {
                                    $obj = new $namespace();
                                    $variant = $obj->changeData($columnName, $mappedData, $variant);
                                }

                            }
                            $product['variants'][$proCol] = $variant;
                        }

                        $newProductData[] = $product;

                    } else {
                        $newProductData[] = $product;
                    }

                }

            } else {
                $newProductData = $newProductData;
            }

        return ['success'=>true,'data'=>$newProductData];
    }

    public function getProductByProductIds()
    {
        $validateData = $this->validateDataWithProductIds();
        if (isset($validateData['success']) && !$validateData['success']) {
            return $validateData;
        }

        $productIds = $this->process_data['container_ids'];
        $finalQuery = [];
                
        $finalQuery[] = [
            '$match' => [
               'user_id' => $this->di->getUser()->id,
                'container_id'=>['$in' => $productIds],
            ]
        ];

        $commonQuery = $this->commonQuery();
        $finalQuery = array_merge($finalQuery,$commonQuery);

        $collection = $this->getCollectionForTable('product_container');
        $response = $collection->aggregate($finalQuery, ["typeMap" => ['root' => 'array', 'document' => 'array', 'object' => 'array', 'array' => 'array']]);
        $response = $response->toArray();
        if(!empty($response)){
            $profileWiseProduct = [];

            foreach ($response as $key => $productData) {
                if(isset($productData['profile'])){

                    foreach ($productData['profile'] as $key => $profile) {
                       if($this->app_tag == $profile['app_tag']){
                            $profileWiseProduct[$profile['profile_id']][] = $productData;
                        }
                    }
                }
            }

            if(!empty($profileWiseProduct))
            {
                $prepareProductData = [];

                foreach ($profileWiseProduct as $profile_id => $products) {
                    $this->profile_id = $profile_id;
                    $profileData = $this->getProfileData();
                    if(!empty($profileData)){
                        $prepareProduct = $this->prepareProduct($products);
                        $prepareProductData[] = $prepareProduct;
                    }
                }
                if(!empty($prepareProductData)){
                    return ['success'=>true,'data'=>$prepareProductData];
                } else {
                    return ['success'=>false,'message'=>'no profile wise data found'];
                }

            } else {
                return ['success'=>false,'message'=>'no profile wise data found'];
            }

        } else {
            return ['success'=>false,'message'=>'no save data found'];
        }
    }


    public function getProduct()
    {
        $finalQuery = [];
                
        $finalQuery[] = [
            '$match' => [
                'user_id' => $this->di->getUser()->id,
                'profile.profile_id'=>$this->profile_id
            ]
        ];

        if (isset($this->process_data['page'], $this->process_data['limit'])) {
                $limit = (int) $this->process_data['limit'];
                $page = (int) $this->process_data['page'] - 1;
                $skip = ($limit * $page);
        } else {
            $skip = 0;
            $limit = 50;
        }
        $finalQuery[] = [
            '$skip' => $skip,
        ];

        $finalQuery[] = [
            '$limit' => $limit+1,
        ];
       $collection = $this->getCollectionForTable('product_container');
       $countresponse = $collection->aggregate($finalQuery, ["typeMap" => ['root' => 'array', 'document' => 'array', 'object' => 'array', 'array' => 'array']]);
        $countresponse = $countresponse->toArray();


       $commonQuery = $this->commonQuery();
       $finalQuery = array_merge($finalQuery,$commonQuery);

        
        $response = $collection->aggregate($finalQuery, ["typeMap" => ['root' => 'array', 'document' => 'array', 'object' => 'array', 'array' => 'array']]);
        $response = $response->toArray();

        if(!empty($response)){
            if(count($countresponse) <= $limit){
                return ['success'=>true,'data'=>$response,'next'=>false];
            } else {
               // array_pop($response);
                return ['success'=>true,'data'=>$response,'next'=>true];
            }

        } else {
            return ['success'=>false,'message'=>'No product data'];
        }

    }

    public function commonQuery(){
        $finalQuery = [];

        $finalQuery[] = [
            '$lookup' => [
                'from' => $this->process_data['target_marketplace'] . '_product_container',
                'localField' => 'source_product_id',
                'foreignField' => 'source_product_id',
                'as' => $this->process_data['target_marketplace'] . "_marketplace",
            ],
        ];


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


        $finalQuery[] = [
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

        return $finalQuery;
    }

    public function getProfileData()
    {

        $userId = $this->di->getUser()->id;
        $profileParams = [];

        $profileParams['filters'] = ['id' => $this->profile_id];
        $obj = new Model();
        $profileData = $obj->getProfile($profileParams);
        if(!empty($profileData['data'])){
            $this->profile_data = $profileData['data'][0];
            return $this->profile_data;
        }
        return [];

    }
}
