<?php

namespace App\Core\Controllers;

use App\Core\Models\User;

class UserController extends BaseController
{

    public function createAction()
    {
        
        $contentType = $this->request->getHeader('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        } else {
            $rawBody = $this->request->get();
        }

        // $rawBody['resources'] = range(0,300);

        // if ( isset($rawBody['app_id']) ) {
        //     $rawBody['apps'] = [
        //         [
        //             "auth_url" => "",
        //             "domain" => $rawBody['domain'],
        //             "app_id" => (int)$rawBody['app_id'],
        //             "app_name" => $rawBody['marketplace'],
        //             "app_marketplace" => $rawBody['marketplace']
        //         ]
        //     ];
        // } else {
        //     $rawBody['apps'] = [
        //         [
        //             "auth_url" => "",
        //             "domain" => $rawBody['domain'],
        //             "app_id" => 2,
        //             "app_name" => "facebook",
        //             "app_marketplace" => "facebook"
        //         ]
        //     ];
        // }
     
        $user = new \App\Core\Models\User;
        return $this->prepareResponse($user->createUser($rawBody,'customer',true));
    }

    /*
    For Customer Api
    Reset passwortd by using the old password
    */
    public function resetpasswordAction()
    {
        $user = $this->di->getUser();
        return $this->prepareResponse($user->resetUserPassword($this->request->get()));
    }

    public function loginAction()
    {
        $user = new \App\Core\Models\User;
        return $this->prepareResponse($user->login($this->request->get()));
    }
    public function shopifyLoginAction()
    {
        
        $user = new \App\Shopify\Models\User;
        return $this->prepareResponse($user->login($this->request->get()));
    }

    public function loginAsUserAction()
    {
        $user = new \App\Core\Models\User;
        return $this->prepareResponse($user->loginAsUser($this->request->get()));
    }

    /*
    For Admin Api
    Reset passwortd by using the old password
    */
    public function updateStatusAction()
    {
        $user = new \App\Core\Models\User;
        return $this->prepareResponse($user->updateStatus($this->request->get()));
    }

    public function deleteAction()
    {
        $user = new \App\Core\Models\User;
        return $this->prepareResponse($user->deleteUser($this->request->get()));
    }

    /*
    Verify (confirm) the user when clicked on the confirmation email link.
    */
    public function verifyuserAction()
    {
        $user = new \App\Core\Models\User;
        return $this->prepareResponse($user->confirmUser($this->request->get('token')));
    }

    /**
     * @return mixed
     */
    public function updateConfirmationAction()
    {
        $user = new \App\Core\Models\User;
        return $this->prepareResponse($user->updateConfirmation($this->request->get()));
    }

    /*
    Forgot password action. It will send the email with reset password link
    */
    public function forgotAction()
    {   
        $user = new \App\Core\Models\User;
        return $this->prepareResponse($user->forgotPassword($this->request->get()));
    }

    /*
    Forgot password action. It will send the email with reset password link
    */
    public function forgotresetAction()
    {
        $user = new \App\Core\Models\User;
        return $this->prepareResponse($user->forgotReset($this->request->get()));
    }

    /*
    For Customer Api
    Get the customer detail along with the user_data table
    */
    public function getDetailsAction()
    {
        $user = $this->di->getUser();
        return $this->prepareResponse($user->getDetails());
    }

    public function getAllAction()
    {
        $user = $this->di->getUser();
        $pageSettings = $this->request->get();

        /* Below commented code is written for advanced filter and full-text search */

        if (isset($pageSettings['filter']) || isset($pageSettings['search'])) {
            return $this->prepareResponse($user->getAll($pageSettings['count'], ($pageSettings['count'] * $pageSettings['activePage']) - $pageSettings['count'], $pageSettings));
        } else {
            return $this->prepareResponse($user->getAll($pageSettings['count'], ($pageSettings['count'] * $pageSettings['activePage']) - $pageSettings['count']));
        }
    }

    /*
    For Admin Api
    Update the customer accept the array in key value form
    */
    public function adminUpdateUserAction()
    {
        $user = new \App\Core\Models\User;
        return $this->prepareResponse($user->adminUpdateUser($this->request->get()));
    }

    /*
    For Customer Api
    Update the customer accept the array in key value form
    */
    public function updateuserAction()
    {
        $user = $this->di->getUser();
        return $this->prepareResponse($user->updateUser($this->request->get()));
    }

    /*
    For Customer Api
    Update the customer profile Picture
    */
    public function updateProfilePicAction()
    {
        $user = $this->di->getUser();
        if ($this->request->hasFiles() == true) {
            foreach ($this->request->getUploadedFiles() as $file) {
                $allowedExtension = ['png', 'jpg', 'gif'];
                $extension = strtolower($file->getExtension());
                if (in_array($extension, $allowedExtension)) {
                    $path = BP . DS . 'public' . DS . 'media' . DS . 'user' . DS . $user->id . DS . 'profile-pic.' . $extension;
                    if (!file_exists(BP . DS . 'public' . DS . 'media' . DS . 'user' . DS . $user->id)) {
                        $oldmask = umask(0);
                        mkdir(BP . DS . 'public' . DS . 'media' . DS . 'user' . DS . $user->id, 0777, true);
                        umask($oldmask);
                    }
                    $file->moveTo($path);
                    $url = 'media' . DS . 'user' . DS . $user->id . DS . 'profile-pic.' . $extension;
                    return $this->prepareResponse($user->updateUser(['profile_pic' => $url, 'username' => $user->username]));
                } else {
                    return $this->prepareResponse(['success' => false, 'code' => 'inavlid_extension', 'message' => 'Invalid Extension Type', 'data' => []]);
                }
            }
        } else {
            if ($this->request->get('profile_pic') == null) {
                return $this->prepareResponse($user->updateUser(['profile_pic' => null, 'username' => $user->username]));
            }
        }
        return $this->prepareResponse(['success' => false, 'code' => 'file_not_found', 'message' => 'No File Found', 'data' => []]);
    }

    /*
    Prepare configuration form
    */
    public function configurationFormAction()
    {
        if ($code = $this->request->get('code')) {
            if ($model = $this->di->getConfig()->connectors->get($code)->get('source_model')) {
                return $this->prepareResponse(['success' => true, 'code' => '', 'message' => '', 'data' => $this->di->getObjectManager()->get($model)->getInstallationForm($code)]);
            }
        } else {
            return $this->prepareResponse(['success' => false, 'code' => 'missing_required_params', 'message' => 'Missing Code.']);
        }
    }

    public function logoutAction()
    {
        $user = $this->di->getUser();
        return $this->prepareResponse($user->logout());
    }

    public function setTrialPeriodAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        }
        $userModel = new \App\Core\Models\User;
        return $this->prepareResponse($userModel->setTrialPeriod($rawBody));
    }

    public function createWebhookAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        }
        $userModel = new \App\Core\Models\User;
        return $this->prepareResponse($userModel->createWebhook($rawBody));
    }

    public function getExistingWebhooksAction()
    {
        $contentType = $this->request->getHeader('Content-Type');
        $rawBody = [];
        if (strpos($contentType, 'application/json') !== false) {
            $rawBody = $this->request->getJsonRawBody(true);
        }
        $userModel = new \App\Core\Models\User;
        return $this->prepareResponse($userModel->getExistingWebhooks($rawBody));
    }
}
