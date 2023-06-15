<?php

namespace App\Core\Controllers;

class TestingController extends BaseController
{

    /**
     * @return mixed
     */
    public function setdataAction()
    {
        $handlerData = [
            'user_id' => $this->_user_id,
            'data' => $data,
            'type' => 'full_class',
            'class_name' => '\App\Core\Components\UnitTestApp',
            'queue_name' => 'test_aliexpress',
            'own_weight' => 100,
            'method' => 'testAliExpress'
        ];
        if ($this->di->getConfig()->get('enable_rabbitmq_internal')) {
            $helper = $this->di->getObjectManager()->get('\App\Rmq\Components\Helper');
            return $helper->createQueue($handlerData['queue_name'], $handlerData);
        }
        return true;

        $mongo = $this->di->getLog()->logContent('dfj','info','fhhf.log');
        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $collection = $mongo->getCollectionForTable('product_container');
        $aggragation = [];
        // $aggragation[] = [
        //     '$search' => [
        //         'index' => 'default',       
        //         'autocomplete' => [
        //             'query' => 'the',         
        //             'path' => 'title'       
        //         ]
        //     ]
        // ];
        // $aggragation[] = [
        //     '$search' => [
        //         'index' => 'default',       
        //         "compound" => [
        //             "must" => [
        //                 [
        //                     'text' => [
        //                         'query' => '61284cf4b0cd2a3419593f9c',         
        //                         'path' => 'user_id' 
        //                     ],
        //                 ],
        //                 [
        //                     'autocomplete' => [
        //                         'query' => 'the',         
        //                         'path' => 'title'       
        //                     ],
        //                 ]
        //             ]
        //         ]
                
        //     ]
        // ];
        // $aggragation[] = [
        //     '$match' => [
        //         "user_id" => "6193aea839e3720b5637100a",
        //         "visibility" => "Catalog and Search"
        //     ]
        // ];

        $aggragation[] = [
            '$project' => [
                "description" => 0
            ]
        ];

        $aggragation[] = [
            '$graphLookup' => [
                "startWith" => "\$source_product_id",          
                "connectFromField" => "source_product_id",          
                "connectToField" => "source_product_id",         
                "maxDepth" => 1, 
                "as" => "lookup"  , 
                "restrictSearchWithMatch" => [
                    // "user_id" => "6193aea839e3720b5637100a",
                    "target_marketplace" => "amazon"
                ],
                "from" => "product_container"
            ]
        ];

        // $aggragation[] = [
        //     '$match' => [
        //         "lookup" => ['$ne' => []]
        //         // "visibility" => "Catalog and Search"
        //     ]
        // ];
        
        $aggragation[] = [
            '$limit' => 100
        ];
        echo "<pre>";
        try {
            $res = $collection->aggregate($aggragation, ['maxTimeMS' => 5000])->toArray();
            print_r($aggragation);
            print_r($res);
        } catch(\Exception $e) {
            echo "<hr><b>" . $e->getMessage() . "</b><hr>"; 
            print_r($aggragation);
        }
        die("in");
        $user_id = $this->di->getUser()->getId();

        $mongo = $this->di->getObjectManager()->create('\App\Core\Models\BaseMongo');
        $mongo->setSource("testing_".$user_id);
        $collection = $mongo->getCollection();
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
       /* $collection->setData($rawBody);
        $collection->save();*/
        if(isset($rawBody['resource']) && isset($rawBody['data'])){
            $collection->replaceOne(
                ['resource'=>$rawBody['resource'] ],
                $rawBody,
                ['upsert'=>true]
            );
            return $this->prepareResponse(['success' => true, 'code' => '', 'message' => 'Resource Saved Successfully']);

        }
        else{
            return $this->prepareResponse(['success' => false, 'code' => 'missing_params', 'message' => 'Missing Params']);

        }

    }

    /**
     * @return mixed
     */
    public function getdataAction()
    {

        $user_id = $this->di->getUser()->getId();
        $resource = $this->request->get('resource');
        $mongo = $this->di->getObjectManager()->get('\App\Core\Models\BaseMongo');
        $mongo->setSource("testing_".$user_id);
        $collection = $mongo->getCollection();
        $options = ["typemap" => ['root' => 'array', 'document' => 'array']];
        $data = $collection->findOne(['resource'=>$resource], $options);
        return $this->prepareResponse(['success' => true, 'code' => '', 'message' => '', 'data'=>$data]);

    }

    /**
     * @return mixed
     */
    public function getResourcesWithDataAction(){
        $user_id = $this->di->getUser()->getId();
        $mongo = $this->di->getObjectManager()->get('\App\Core\Models\BaseMongo');
        $mongo->setSource("testing_".$user_id);
        $collection = $mongo->getCollection();
        $data = $collection->find()->toArray();
        return $this->prepareResponse(['success' => true, 'code' => '', 'message' => '', 'data'=>$data]);
    }
}
