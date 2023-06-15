<?php

namespace App\Connector\Controllers;

use App\Core\Controllers\BaseController;

class ProductController extends BaseController
{
    /**
     * Create/Update Product Action
     * @return string
     */
    public function createAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        }
        $product = $this->di->getObjectManager()->create('\App\Connector\Models\ProductContainer');
        return $this->prepareResponse($product->createProductsAndAttributes([$rawBody], "cedcommerce"));
    }

    /**
     * Create Product Action
     * @return string
     */
    public function importAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $product = $this->di->getObjectManager()->get('\App\Connector\Models\SourceModel');
        return $this->prepareResponse($product->initiateImport($rawBody));
    }

    public function downloadReportAction()
    {
        $reportToken = $this->di->getRequest()->get('file_token');
        try {
            $token = $this->di->getObjectManager()->get('App\Core\Components\Helper')->decodeToken($reportToken, false);
            $filePath = $token['data']['file_path'];
            $contentType = 'text/csv';
            if (file_exists($filePath)) {
                header('Content-Type: ' . $contentType . '; charset=utf-8');
                header('Content-Disposition: attachment; filename=google_report_' . time() . '.csv');
                @readfile($filePath);
                die;
            } else {
                die('Invalid Url. No report found.');
            }
        } catch (\Exception $e) {
            die('Invalid Url. No report found.');
        }
    }

    public function syncProductAction()
    {
        // die("dd");
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }

        if (isset($rawBody['source_product_id']) && !empty($rawBody['source_product_id'])) {
            $product = $this->di->getObjectManager()->get('\App\Connector\Models\SourceModel');

            return $this->prepareResponse($product->uploadProducts($rawBody, 'PartialUpdate'));
        } else {
            $this->prepareResponse(['success' => false, 'message' => 'Product Ids not found.']);
        }
    }

    /**
     * Upload Product Action
     * @return string
     */
    public function uploadAction()
    {

        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $product = $this->di->getObjectManager()->get('\App\Connector\Models\SourceModel');
        return $this->prepareResponse($product->uploadProducts($rawBody));
    }

    /**
     * upload product by selection
     */

    /**
     * Upload Product Through CSV
     * @return string
     */
    public function uploadCSVAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $product = $this->di->getObjectManager()->create('\App\Connector\Models\ProductContainer');
        return $this->prepareResponse($product->uploadProductsCSV($rawBody));
    }

    /**
     * upload product by selection
     */
    public function selectUploadAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $rawBody['user_id'] = $this->di->getUser()->id;
        $product = $this->di->getObjectManager()->get('\App\Connector\Models\SourceModel');
        return $this->prepareResponse($product->uploadProducts($rawBody));


        /*$contentType = $this->request->getHeader('Content-Type');
        $userId = $this->di->getUser()->id;
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }

        if ($this->di->getConfig()->enable_rabbitmq) {
            $product = $this->di->getObjectManager()->get('\App\Shopifyhome\Models\SourceModel');
            return $this->prepareResponse($product->selectProductAndUpload($rawBody, $userId));
        } else {
            // nothing can be done as of now, need to make sure rabbitMQ is always UP and RUNNIG :P
            return ['success' => false, 'message' => 'RMQ_DISABLE : Internal Server Error . We are working hard and will be up shortly'];
        }*/
    }

    public function editedSaveAction()
    {

        $contentType = $this->request->getHeader('Content-Type');
        $userId = $this->di->getUser()->id;
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $products = new \App\Connector\Models\ProductContainer();
        return $this->prepareResponse($products->editedProduct($rawBody));
    }

    /**
     * Get a single product by id/source_product_id
     * @return string
     */
    public function getProductAction()
    {
        $productId = $this->di->getRequest()->get();
        $products = new \App\Connector\Models\Product\Edit;
        // $products = new \App\Connector\Models\ProductContainer();
        return $this->prepareResponse($products->getProduct($productId));
    }

    public function saveProductAction()
    {
        $productId = $this->di->getRequest()->get();
        $products = new \App\Connector\Models\Product\Edit;
        $rawBody = $this->request->getJsonRawBody(true);
        // $products = new \App\Connector\Models\ProductContainer();
        return $this->prepareResponse($products->saveProduct($rawBody));
    }

    public function getProductByIdAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }

        $rawBody['user_id'] = $this->di->getUser()->id;

        $product = $this->di->getObjectManager()->get('\App\Connector\Models\ProductContainer');
        return $this->prepareResponse($product->getProductById($rawBody));
    }

    /**
     * @return string
     */
    public function downloadExportedProductAction()
    {
        $userDetails = $this->di->getRequest()->get('token');
        $token = $this->di->getObjectManager()->get('App\Core\Components\Helper')->decodeToken($userDetails, false);
        $contentType = 'text/csv';
        if ($token['data']['extension'] === 'xlsx') {
            $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        }
        $file_path = BP . DS . 'var' . DS . 'exports' . DS . $token['data']['user_id'] . DS . $token['data']['fileName'];
        if (file_exists($file_path)) {
            header('Content-Type: ' . $contentType . '; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $token['data']['fileName'] . '');
            @readfile($file_path);
            die;
        } else {
            echo 'File not found at specified path.';
            die;
        }
    }

    /**
     * @return string
     */
    public function getProductHeadersAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        }
        $products = new \App\Connector\Models\Product();
        return $this->prepareResponse($products->getHeadersFromCsv($rawBody));
    }

    /**
     * Get Config Attribute during profile mapping
     * @return string
     */
    public function getConfigAttributesAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        }
        $products = new \App\Connector\Models\Product();
        return $this->prepareResponse($products->getConfigAttributes($rawBody));
    }

    public function saveConfigAttributeMappingAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        }
        $products = new \App\Connector\Models\Product();
        return $this->prepareResponse($products->saveConfigAttributeMapping($rawBody));
    }

    public function saveValueMappingAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        }
        $products = new \App\Connector\Models\Product();
        return $this->prepareResponse($products->saveValueMapping($rawBody));
    }

    /**
     * Delete product in mass
     * @return mixed
     */
    public function deleteMultipleProductsAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        }
        $products = new \App\Connector\Models\ProductContainer();
        return $this->prepareResponse($products->deleteMultipleProducts($rawBody));
    }

    public function createQueuedProductsAction()
    {
        $productData = $this->request->get();
        $productModel = new \App\Connector\Models\Product();
        $status = $productModel->createProducts($productData['product'], $productData['count'], $productData['history_index']);
        return $status['status'];
    }

    /**
     * Delete Product by Id
     * @return mixed
     */
    public function deleteProductAction()
    {
        $productId = $this->di->getRequest()->get();
        $products = new \App\Connector\Models\ProductContainer();
        return $this->prepareResponse($products->deleteProduct($productId));
    }

    public function getProductFormAction()
    {
        $attribute = $this->di->getObjectManager()->create('\App\Connector\Models\ProductAttribute');
        return $this->prepareResponse($attribute->getProductForm());
    }

    public function clearAllFeedsAction()
    {
        $productModel = new \App\Connector\Models\Product();
        $responseData = $productModel->clearAllFeeds();
        return $this->prepareResponse($responseData);
    }

    public function downloadFeedDataAction()
    {
        $fileRelativePath = $this->request->get('fileName');
        $file_path = BP . DS . 'var' . DS . $fileRelativePath;
        $fileName = substr($fileRelativePath, strrpos($fileRelativePath, '/'));
        $ext = explode('.', $fileName);
        $contentType = 'text/csv';
        if ($ext[1] === 'xlsx') {
            $contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        }
        if (file_exists($file_path)) {
            header('Content-Type: ' . $contentType . '; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $fileName . '');
            @readfile($file_path);
            die;
        } else {
            echo 'File not found at specified path.';
            die;
        }
    }

    public function getProductFeedsAction()
    {
        $productModel = new \App\Connector\Models\Product();
        $responseData = $productModel->getProductFeeds($this->request->get());
        return $this->prepareResponse($responseData);
    }

    /**
     * Get Product list based on search and filter
     * @return mixed
     */
    public function getProductsCountAction()
    {
        $containerModel = new \App\Connector\Models\ProductContainer();
        $responseData = $containerModel->getProductsCount($this->request->get());
        return $this->prepareResponse($responseData);
    }

    /**
     * Get Product list based on search and filter
     * @return mixed
     */
    public function getProductsAction()
    {
        $containerModel = new \App\Connector\Models\ProductContainer();
        $responseData = $containerModel->getProducts($this->request->get());
        return $this->prepareResponse($responseData);
    }

    /**
     * Get Product list based on search and filter
     * @return mixed
     */
    public function getChildProductsAction()
    {
        $containerModel = new \App\Connector\Models\ProductContainer();
        $responseData = $containerModel->getChildProducts($this->request->get());
        return $this->prepareResponse($responseData);
    }


    public function getAllVariantAction()
    {
        $productModel = new \App\Connector\Models\Product();
        $responseData = $productModel->getAllVariant($this->request->get());
        return $this->prepareResponse($responseData);
    }

    /**
     * Get All Attributes of Merchant.
     * @return mixed
     */
    public function getAllAttributesAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $pageSettings = $this->request->getJsonRawBody(true);
        } else {
            $pageSettings = $this->request->get();
        }
        $attribute = $this->di->getObjectManager()->create('\App\Connector\Models\ProductAttribute');
        if (isset($pageSettings['count']) && isset($pageSettings['activePage'])) {
            if (isset($pageSettings['filter']) || isset($pageSettings['search'])) {
                $response = $attribute->getAllAttributes($pageSettings['count'], ($pageSettings['count'] * $pageSettings['activePage']) - $pageSettings['count'], $pageSettings);
            } else {
                $response = $attribute->getAllAttributes($pageSettings['count'], ($pageSettings['count'] * $pageSettings['activePage']) - $pageSettings['count']);
            }
        } else {
            $response = $attribute->getAllAttributes();
        }
        return $this->prepareResponse($response);
    }

    /**
     * Create Product Attribute
     * @return string
     */
    public function createAttributeAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $attribute = $this->di->getObjectManager()->create('\App\Connector\Models\ProductAttribute');
        return $this->prepareResponse($attribute->createAttribute($rawBody));
    }

    /**
     * Update Product Attribute
     * @return string
     */
    public function updateAttributeAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $attribute = $this->di->getObjectManager()->create('\App\Connector\Models\ProductAttribute');
        return $this->prepareResponse($attribute->updateAttribute($rawBody));
    }

    /**
     * Delete Product Attribute
     * @return string
     */
    public function deleteAttributeAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $attribute = $this->di->getObjectManager()->create('\App\Connector\Models\ProductAttribute');
        return $this->prepareResponse($attribute->deleteAttribute($rawBody));
    }

    /**
     * Get Product Attribute Option
     * @return string
     */
    public function getAttributeOptionAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $attribute = $this->di->getObjectManager()->create('\App\Connector\Models\ProductAttribute');
        return $this->prepareResponse($attribute->getAttributeOptions($rawBody));
    }

    /**
     * Delete Attribute Option
     * @return string
     */
    public function deleteAttributeOptionAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $attribute = $this->di->getObjectManager()->create('\App\Connector\Models\ProductAttribute');
        return $this->prepareResponse($attribute->deleteAttributeOption($rawBody));
    }


    public function exportProductAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        }
        $product = $this->di->getObjectManager()->create('\App\Connector\Models\Product');
        return $this->prepareResponse($product->exportProduct($rawBody));
    }

    public function getColumnsAction()
    {
        $product = new \App\Connector\Models\Product();
        return $this->prepareResponse($product->getProductColumns());
    }

    public function ifRequiredAttributeMappedAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        }
        $product = new \App\Connector\Models\Product();
        return $this->prepareResponse($product->ifRequiredAttributeMapped($rawBody));
    }

    public function saveUnmappedAttributesAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        }
        $product = new \App\Connector\Models\Product();
        return $this->prepareResponse($product->saveUnmappedAttributes($rawBody));
    }

    public function getProductsByQueryAction()
    {

        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $product = new \App\Connector\Models\Product();
        return $this->prepareResponse($product->getProductsByQuery($rawBody));
    }

    public function getAttributesByProductQueryAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $product = new \App\Connector\Models\Product();
        return $this->prepareResponse($product->getAttributesByProductQuery($rawBody));
    }

    public function downloadCSVAction()
    {

        $userDetails = $this->di->getRequest()->get('file_token');
        try {
            $token = $this->di->getObjectManager()->get('App\Core\Components\Helper')->decodeToken($userDetails, false);
            $file_path = $token['data']['file_path'];
            $filenameextract_array = explode('/', $file_path);
            $filename = $filenameextract_array[count($filenameextract_array) - 1];
            if ($file_path) {
                $contentType = 'text/csv';
                if (file_exists($file_path)) {
                    header('Content-Type: ' . $contentType . '; charset=utf-8');
                    header('Content-Disposition: attachment; filename=' . $filename . '');
                    @readfile($file_path);
                    die;
                } else {
                    echo 'File not found at specified path.';
                    die;
                }
            } else {
                die('No feed found');
            }
        } catch (\Exception $e) {
            die('Invalid Token');
        }
    }

    public function updateProductAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $productContainer = new \App\Connector\Models\ProductContainer;
        return $this->prepareResponse($productContainer->updateProduct($rawBody));
    }

    public function syncWithSourceAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $productContainer = new \App\Connector\Models\ProductContainer;
        return $this->prepareResponse($productContainer->syncWithSource($rawBody));
    }

    public function importCSVAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->di->getRequest()->get();
        }
        $product = $this->di->getObjectManager()->create('\App\Connector\Models\ProductContainer');
        return $this->prepareResponse($product->importCSV($rawBody));
    }

    public function exportProductCSVAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->di->getRequest()->get();
        }
        $product = $this->di->getObjectManager()->create('\App\Connector\Models\ProductContainer');
        return $this->prepareResponse($product->exportProductCSV($rawBody));
    }

    public function enableDisableAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }

        $product = $this->di->getObjectManager()->get('\App\Connector\Models\ProductContainer');
        return $this->prepareResponse($product->enableDisable($rawBody));
    }

    public function selectAndSyncAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $productContainer = new \App\Connector\Models\ProductContainer;
        return $this->prepareResponse($productContainer->syncData($rawBody));
    }

    public function syncCatalogAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $productContainer = new \App\Connector\Models\ProductContainer;
        return $this->prepareResponse($productContainer->syncCatalog($rawBody));
    }

    public function getAllProductStatusesAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }

        $containerModel = new \App\Connector\Models\ProductContainer();
        $responseData = $containerModel->getAllProductStatuses($rawBody);
        $response = $this->prepareResponse($responseData);
        return $response;
    }

    public function getStatusWiseFilterCountAction()
    {
        $containerModel = new \App\Connector\Models\ProductContainer();
        $responseData = $containerModel->getStatusWiseFilterCount($this->request->get());
        return $this->prepareResponse($responseData);
    }

    public function getProductAttributesAction()
    {
        try {
            $contentType = $this->request->getHeader('Content-Type');
            $rawBody = [];
            if (strpos($contentType, 'application/json') !== false) {
                $rawBody = $this->request->getJsonRawBody(true);
            } else {
                $rawBody = $this->request->get();
            }

            $containerModel = new \App\Connector\Models\ProductContainer();
            $responseData = $containerModel->getProductAttributes($rawBody);
            $response = $this->prepareResponse($responseData);
            return $response;
        } catch (\Exception $e) {
            $response = ['success' => false, "message" => $e->getMessage()];
        }
        return $this->prepareResponse($response);
    }
}
