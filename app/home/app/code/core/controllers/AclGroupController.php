<?php

namespace App\Core\Controllers;

use Phalcon\Mvc\Controller;
use App\Core\Models\Acl\Role;

class AclGroupController extends BaseController
{

    public function createAction()
    {
        $role = new Role();
        $response = $role->createRole($this->di->getRequest()->get());
        return $this->prepareResponse($response);
    }

    public function updateAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $role = new Role();
        $response = $role->updateRole($rawBody);
        return $this->prepareResponse($response);
    }

    public function deleteAction()
    {
        $role = new Role();
        $response = $role->deleteRole($this->di->getRequest()->get());
        return $this->prepareResponse($response);
    }

    public function getAction()
    {
        $role = new Role();
        $response = $role->getRole($this->di->getRequest()->get());
        return $this->prepareResponse($response);
    }

    public function getRoleResourceAction()
    {
        $role = new Role();
        $response = $role->getRoleResources($this->di->getRequest()->get());
        return $this->prepareResponse($response);
    }


    public function getAllAction()
    {
        $role = new Role();
        $pageSettings = $this->di->getRequest()->get();
        $response = $role->getAllRoles($pageSettings['count'], ($pageSettings['count'] * $pageSettings['activePage']) - $pageSettings['count']);
        return $this->prepareResponse($response);
    }
}
