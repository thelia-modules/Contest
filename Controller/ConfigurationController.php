<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/
/*************************************************************************************/

namespace Contest\Controller;

use Contest\Contest;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\JsonResponse;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;

/**
 * Class ConfigurationController
 * @package Controller
 */
class ConfigurationController extends BaseAdminController
{
    public function toggleWinOptionAction(){

        return $this->toggleOption(Contest::WIN_OPTION);
    }


    public function toggleConnectOptionAction(){
        return $this->toggleOption(Contest::CONNECT_OPTION);
    }

    public function toggleFriendOptionAction(){
        return $this->toggleOption(Contest::FRIEND_OPTION);
    }

    public function setFriendMaxOptionAction($val){
        return $this->setOption(Contest::FRIEND_MAX_OPTION,$val);
    }

    public function setMaxParticipateOptionAction($val){
        return $this->setOption(Contest::MAX_PARTICIPATE_OPTION,$val);
    }

    protected function toggleOption($name){
        if (null !== $response = $this->checkAuth(array(AdminResources::MODULE), array('contest'),
                AccessManager::UPDATE)
        ) {
            return $response;
        }

        $resp = array(
            "message" => ""
        );
        $code = 200;
        try {
            Contest::setConfigValue($name, !Contest::getConfigValue($name));
            $resp["message"] = $this->getTranslator()->trans("Config toggle succes", [], Contest::MESSAGE_DOMAIN);
        } catch (\Exception $e) {
            $resp["message"] = $e->getMessage();
            $code = 500;
        }

        return JsonResponse::create($resp, $code);
    }

    protected function setOption($name,$value){
        if (null !== $response = $this->checkAuth(array(AdminResources::MODULE), array('contest'),
                AccessManager::UPDATE)
        ) {
            return $response;
        }

        $resp = array(
            "message" => ""
        );
        $code = 200;
        try {
            Contest::setConfigValue($name,$value);
            $resp["message"] = $this->getTranslator()->trans("Config toggle succes", [], Contest::MESSAGE_DOMAIN);
        } catch (\Exception $e) {
            $resp["message"] = $e->getMessage();
            $code = 500;
        }

        return JsonResponse::create($resp, $code);
    }

}