<?php
namespace App\Core\Controllers;

class SubUserController extends BaseController
{

    public function createAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }

        $user = new \App\Core\Models\User\SubUser;
        return $this->prepareResponse($user->createUser($rawBody));
    }


    public function loginAction()
    {
        $user = new \App\Core\Models\User\SubUser;
        return $this->prepareResponse($user->login($this->request->get()));
    }


    public function deleteAction()
    {
        $user = new \App\Core\Models\User\SubUser;
        return $this->prepareResponse($user->deleteSubUser($this->request->get()));
    }


    /*
    For Customer Api
    Get the customer detail along with the user_data table
    */
    // public function getDetailsAction(){
    //     $user = new \App\Core\Models\User\SubUser;
    //     return $this->prepareResponse($user->getAll());
    // }

    public function getSubUsersAction()
    {
        $user = new \App\Core\Models\User\SubUser;
        $pageSettings = $this->di->getRequest()->get();
        if (isset($pageSettings['count']) && isset($pageSettings['activePage'])) {
            if (isset($pageSettings['filter']) || isset($pageSettings['search'])) {
                return $this->prepareResponse($user->getSubUser($this->request->get(), $pageSettings['count'], ($pageSettings['count'] * $pageSettings['activePage']) - $pageSettings['count'], $pageSettings));
            } else {
                return $this->prepareResponse($user->getSubUser($this->request->get(), $pageSettings['count'], ($pageSettings['count'] * $pageSettings['activePage']) - $pageSettings['count']));
            }
        } else {
            return $this->prepareResponse($user->getSubUser($this->request->get()));
        }
    }

    /*
    For Customer Api
    Update the customer accept the array in key value form
    */
    public function updateAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $user = new \App\Core\Models\User\SubUser;
        return $this->prepareResponse($user->updateSubUser($rawBody));
    }

    public function updateAppAction() {
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }
        $user = new \App\Core\Models\User\SubUser;
        return $this->prepareResponse($user->updateSubUserApp($rawBody));
    }
}
