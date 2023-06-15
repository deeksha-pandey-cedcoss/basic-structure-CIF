<?php

namespace App\Connector\Controllers;

class GetController extends \App\Core\Controllers\BaseController
{
    public function oneAction()
    {
        $connectorsHelpler = $this->di
            ->getObjectManager()
            ->get('App\Connector\Components\Connectors');
        if ($code = $this->request->get('code')) {
            return $this->prepareResponse([
                'success' => true,
                'code' => '',
                'message' => '',
                'data' => $connectorsHelpler->getConnectorByCode($code),
            ]);
        } else {
            return $this->prepareResponse([
                'success' => false,
                'code' => 'missing_required_params',
                'message' => 'Missing Code.',
            ]);
        }
    }

    public function getInstalledAppsAction()
    {
        $connectorsHelpler = $this->di->getObjectManager()->get('App\Connector\Components\Connectors');
        $connectors = $connectorsHelpler
            ->getConnectorsWithFilter([
                'is_source' => 1,
                'installed' => 1,
            ], $this->di->getUser()->getId());
        $installedApps = [];
        foreach ($connectors as $connector) {
            $installedApps[] = [
                'code' => $connector['code'],
                'title' => $connector['title'],
                'shops' => $connectorsHelpler->getConnectorModelByCode($connector['code'])->getShops(),
            ];
        }
        return $this->prepareResponse(['success' => true, 'message' => 'Installed apps.', 'data' => $installedApps]);
    }

    public function allQueuedTasksAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $queuedTasks = new \App\Connector\Models\QueuedTasks;
        return $this->prepareResponse($queuedTasks->getAllQueuedTasks($rawBody));
    }

    public function allNotificationsAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $notifications = new \App\Connector\Models\Notifications;
        return $this->prepareResponse($notifications->getAllNotifications($rawBody));
    }

    public function clearNotificationsAction()
    {
        $notifications = new \App\Connector\Models\Notifications;
        return $this->prepareResponse($notifications->clearAllNotifications());
    }

    public function configFormAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        if (isset($rawBody['marketplace'])) {
            $userId = $this->di->getUser()->id;
            $configForm = $this->di->getObjectManager()->get('\App\\' . ucfirst($rawBody['marketplace']) . '\Models\SourceModel')->getMarketplaceAttributesForm($userId);
            return $this->prepareResponse(['success' => true, 'data' => $configForm]);
        } else {
            return $this->prepareResponse(['success' => false, 'code' => 'invalid_request', 'message' => 'Invalid request']);
        }
    }

    public function configAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        if (isset($rawBody['marketplace'])) {
            $userId = $this->di->getUser()->id;
            $connectorHelper = $this->di->getObjectManager()
                ->get('App\Connector\Components\Connectors')
                ->getConnectorModelByCode($rawBody['marketplace']);
               
                
            if ($connectorHelper) {
                $configForm = $connectorHelper->getConfigData($userId);
            }
            // $configForm = $this->di->getObjectManager()->get('\App\\' . ucfirst($rawBody['marketplace']) . '\Models\SourceModel')->;
            return $this->prepareResponse(['success' => true, 'data' => $configForm]);
        } else {
            return $this->prepareResponse(['success' => false, 'code' => 'invalid_request', 'message' => 'Invalid request']);
        }
    }

    public function saveConfigAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }

        if (isset($rawBody['marketplace'])) {
            $userId = $this->di->getUser()->id;
            $connectorHelper = $this->di->getObjectManager()
                ->get('App\Connector\Components\Connectors')
                ->getConnectorModelByCode($rawBody['marketplace']);

            if ($connectorHelper) {
                $configForm = $connectorHelper->saveConfigData($rawBody, $userId);
            }
            return $this->prepareResponse(['success' => true, 'code' => 'config_data_saved', 'message' => 'Config Data Saved Successfully']);
        } else {
            return $this->prepareResponse(['success' => false, 'code' => 'invalid_request', 'message' => 'Invalid request']);
        }
    }

    public function importOptionsAction()
    {
        $connectorsHelpler = $this->di->getObjectManager()->get('App\Connector\Components\Connectors');
        $connectors = $connectorsHelpler->getConnectorsWithFilter(['can_import' => 1, 'installed' => 1], $this->di->getUser()->getId());

        $options = [];
        foreach ($connectors as $connector) {
            $options[] = [
                'code' => $connector['code'],
                'title' => $connector['title'],
                'shops' => $connectorsHelpler->getConnectorModelByCode($connector['code'])->getShops(),
            ];
        }
        $warehouse = $this->di->getObjectManager()->create('\App\Connector\Models\Warehouse');

        $data = [
            'file' => [
                'title' => 'Import from file',
                'fileTypes' => '.csv, .xlsx',
                'url' => [
                    0 => 'connector/product/getProductHeaders',
                    1 => 'connector/product/getConfigAttributes',
                    2 => 'connector/product/saveConfigAttributeMapping',
                    3 => 'connector/product/ifRequiredAttributeMapped',
                    4 => 'connector/product/saveUnmappedAttributes',
                    5 => 'connector/product/saveValueMapping',
                ],
            ],
            'marketplace' => [
                'title' => 'Import from marketplace',
                'options' => $options,
                'url' => [
                    0 => '/import/getHeaders',
                    1 => 'connector/product/getConfigAttributes',
                    2 => 'connector/product/saveConfigAttributeMapping',
                    3 => 'connector/product/ifRequiredAttributeMapped',
                    4 => 'connector/product/saveUnmappedAttributes',
                    5 => 'connector/product/saveValueMapping',
                ],
            ],
            'warehouses' => $warehouse->getWarehouseName(),
        ];
        return $this->prepareResponse(['success' => true, 'code' => '', 'message' => '', 'data' => $data]);
    }

    public function allAction()
    {
        $search = $this->request->get();
        if (isset($search['search'])) {
            $connectors = $this->di->getObjectManager()->get('App\Connector\Components\Connectors')->getConnectorsWithFilter(['type' => 'real', 'code' => $search['search']], $this->di->getUser()->getId());
            return $this->prepareResponse(['success' => true, 'code' => '', 'message' => '', 'data' => $connectors]);
        } else {
            $connectors = $this->di->getObjectManager()
                ->get('App\Connector\Components\Connectors')
                ->getConnectorsWithFilter([
                    'type' => 'real',
                ], $this->di->getUser()->getId());
            return $this->prepareResponse([
                'success' => true,
                'code' => '',
                'message' => '',
                'data' => $connectors,
            ]);
        }
    }

    public function withFiltersAction()
    {
        if ($filters = $this->request->get('filters')) {
            $connectors = $this->di
                ->getObjectManager()
                ->get('App\Connector\Components\Connectors')
                ->getConnectorsWithFilter($filters, $this->di->getUser()->getId());
        } else {
            $connectors = $this->di
                ->getObjectManager()
                ->get('App\Connector\Components\Connectors')
                ->getAllConnectors($this->di->getUser()->getId());
        }
        return $this->prepareResponse(['success' => true, 'code' => '', 'message' => '', 'data' => $connectors]);
    }

    public function installationFormAction()
    {
        if ($code = $this->request->get('code')) {
            if ($model = $this->di->getConfig()->connectors->get($code)->get('source_model')) {
                $data = $this->di->getObjectManager()->get($model)->getInstallationForm($code);
                if ($data['post_type'] === "redirect") {
                    $this->response->redirect($data['action']);
                } elseif ($data['post_type'] === "view_template") {
                    $this->view->setViewsDir(CODE . $data['viewDir']);
                    $this->view->setVars($data['params']);
                    $this->view->pick($data['template']);
                }
            }
        } else {
            return $this->prepareResponse(['success' => false, 'code' => 'missing_required_params', 'message' => 'Missing Code.']);
        }
    }

    public function configurationFormAction()
    {
        if ($code = $this->request->get('code')) {
            $tabContent = [];
            $userconfig = $this->di->getObjectManager()->get('App\Core\Models\User')->load($this->di->getUser()->getId());
            $tabContent = $this->di->getConfig()->tabs_content->toArray();
            $coreConfig = $this->di->getCoreConfig();
            foreach ($tabContent[$code]['data'] as $groupkey => $groups) {
                foreach ($groups['formJson'] as $attributekey => $attributes) {
                    if (isset($attributes['depend_on_config']) && !$coreConfig->get($attributes['depend_on_config'])) {
                        continue;
                    }
                    if (isset($attributes['option_type']) && $attributes['option_type'] == 'dynamic') {
                        $toCallMethod = $attributes['option_method'];
                        $tabContent[$code]['data'][$groupkey]['formJson'][$attributekey]['data']['values'] = $this->di->getObjectManager()->get($attributes['option_path'])->$toCallMethod();
                    }
                    $keyData = explode('][', $attributes['key']);
                    $keyData = trim($keyData[1], ']');
                    if (strpos($code, '/') !== false) {
                        $mainCode = explode('/', $code);
                        $main = $mainCode[0];
                    } else {
                        $main = $code;
                    }
                    $tabContent[$code]['data'][$groupkey]['formJson'][$attributekey]['data']['value'] = $userconfig->getConfigByPath($keyData, $main) ? $userconfig->getConfigByPath($keyData, $main)['value'] : '';
                }
            }
            $tabContent['data'] = $tabContent[$code]['data'];
            unset($tabContent[$code]);
            return $this->prepareResponse(['success' => true, 'code' => '', 'message' => '', 'data' => $tabContent['data']]);
        } else {
            return $this->prepareResponse(['success' => false, 'code' => 'missing_required_params', 'message' => 'Missing Code.']);
        }
    }

    public function configurationTabAction()
    {
        return $this->prepareResponse([
            'success' => true,
            'code' => '',
            'message' => '',
            'data' => $this->di->getObjectManager()->get('App\Connector\Components\ConfigurationTabs')
                ->getTabs($this->di->getUser()->getId()),
        ]);
    }

    public function servicesAction()
    {
        $filters = $this->request->get('filters');
        $services = $this->di
            ->getObjectManager()
            ->get('App\Connector\Components\Services')
            ->getWithFilter($filters, $this->di->getUser()->getId());
        return $this->prepareResponse(['success' => true, 'code' => '', 'message' => '', 'data' => $services]);
    }

    /**
     * @return mixed
     */
    public function categoriesAction()
    {
        $marketplace = $this->request->get('marketplace');
        $category = $this->request->get('category') ? $this->request->get('category') : '';
        if ($marketplace) {
            $connectorsHelpler = $this->di->getObjectManager()->get('App\Connector\Components\Connectors');
            $categories = $connectorsHelpler->getConnectorModelByCode($marketplace)->getChildCategories($category);
            return $this->prepareResponse(['success' => true, 'code' => '', 'message' => '', 'data' => $categories]);
        }
        return $this->prepareResponse(['success' => false, 'code' => 'missing_required_params', 'message' => 'Missing Marketplace Code.']);
    }

    /**
     * @return mixed
     */
    public function getAttributesAction()
    {
        $marketplace = $this->request->get('marketplace');

        $category = $this->request->get('category') ? $this->request->get('category') : '';

        if ($marketplace) {
            $connectorsHelpler = $this->di->getObjectManager()->get('App\Connector\Components\Connectors');
            $attributes = $connectorsHelpler->getConnectorModelByCode($marketplace)->getAttributes($category);
            return $this->prepareResponse(['success' => true, 'code' => '', 'message' => '', 'data' => $attributes]);
        }
        return $this->prepareResponse(['success' => false, 'code' => 'missing_required_params', 'message' => 'Missing Marketplace Code.']);
    }

    public function importCategoryAction(){
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }

        if (isset($rawBody['marketplace'])) {
            $connectorHelper = $this->di->getObjectManager()
                ->get('App\Connector\Components\Connectors')
                ->getConnectorModelByCode($rawBody['marketplace']);
            return $this->prepareResponse($connectorHelper->importCategory($rawBody));
        } else {
            return $this->prepareResponse(['success' => false, 'message' => 'Invalid request']);
        }
    }

    /**
     * Download The Extension by it's relative path
     */
    public function downloadAction()
    {
        $file = $this->di->getRequest()->get('file');
        if ($file) {
            $contentType = 'application/zip';
            $file_path = BP . DS . 'var' . DS . 'modules' . DS . $file;
            if (file_exists($file_path)) {
                // $fileName = end(explode(DS, $file));
                header('Content-Type: ' . $contentType . '; charset=utf-8');
                header('Content-Disposition: attachment; filename=' . $fileName . '');
                @readfile($file_path);
                die;
            } else {
                echo 'File not found at specified path.';
                die;
            }
        }
    }

    public function allFeedsAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        if (isset($rawBody['marketplace'])) {
            $userId = $this->di->getUser()->id;
            $Feeds = $this->di->getObjectManager()->get('\App\\' . ucfirst($rawBody['marketplace']) . '\Models\SourceModel')->getAllFeeds($userId, $rawBody);
            if ($Feeds) {
                return $this->prepareResponse(['success' => true, 'data' => ['rows' => $Feeds['data'], 'count' => $Feeds['count']]]);
            } else {
                return $this->prepareResponse(['success' => false, 'code' => 'Not_Found', 'message' => 'Feeds Not Found']);
            }
        } else {
            return $this->prepareResponse(['success' => false, 'code' => 'invalid_request', 'message' => 'Invalid request']);
        }
    }

    public function feedDetailsAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        if (isset($rawBody['marketplace'])) {
            $userId = $this->di->getUser()->id;
            $Feeds = $this->di->getObjectManager()->get('\App\\' . ucfirst($rawBody['marketplace']) . '\Models\SourceModel')->getfeedDetails($userId, $rawBody['data']);
            if ($Feeds) {
                return $this->prepareResponse($Feeds);
            } else {
                return $this->prepareResponse(['success' => false, 'code' => 'Not_Found', 'message' => 'Feeds Not Found']);
            }
        } else {
            return $this->prepareResponse(['success' => false, 'code' => 'invalid_request', 'message' => 'Invalid request']);
        }
    }

    public function feedDeleteAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        if (isset($rawBody['marketplace'])) {
            $userId = $this->di->getUser()->id;
            $Feeds = $this->di->getObjectManager()->get('\App\\' . ucfirst($rawBody['marketplace']) . '\Models\SourceModel')->feedDelete($userId, $rawBody['id']);
            if ($Feeds) {
                return $this->prepareResponse($Feeds);
            } else {
                return $this->prepareResponse(['success' => false, 'code' => 'Not_Found', 'message' => 'Feeds Not Found']);
            }
        } else {
            return $this->prepareResponse(['success' => false, 'code' => 'invalid_request', 'message' => 'Invalid request']);
        }
    }

    public function configurableAttributesAction()
    {
        $response = $this->di->getObjectManager()->get('\App\Connector\Models\Product')->getConfigurableAttributes();
        return $this->prepareResponse($response);
    }
}
