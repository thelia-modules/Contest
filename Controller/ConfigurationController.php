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
use Thelia\Core\Translation\Translator;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/module/Contest", name="contest.config")
 * Class ConfigurationController
 * @package Controller
 */
class ConfigurationController extends BaseAdminController
{
    /**
     * @Route("/win/toggle", name=".toggle.win", methods="POST")
     */
    public function toggleWinOptionAction(){

        return $this->toggleOption(Contest::WIN_OPTION);
    }

    /**
     * @Route("/connect/toggle", name=".toggle.connect", methods="POST")
     */
    public function toggleConnectOptionAction(){
        return $this->toggleOption(Contest::CONNECT_OPTION);
    }

    /**
     * @Route("/friend/toggle", name=".toggle.friend", methods="POST")
     */
    public function toggleFriendOptionAction(){
        return $this->toggleOption(Contest::FRIEND_OPTION);
    }

    /**
     * @Route("/friend/max/{val}", name=".friend.max", methods="POST")
     */
    public function setFriendMaxOptionAction($val){
        return $this->setOption(Contest::FRIEND_MAX_OPTION,$val);
    }

    /**
     * @Route("/participation/max/{val}", name=".participate.max", methods="POST")
     */
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
            $resp["message"] = Translator::getInstance()->trans("Config toggle succes", [], Contest::MESSAGE_DOMAIN);
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
            $resp["message"] = Translator::getInstance()->trans("Config toggle succes", [], Contest::MESSAGE_DOMAIN);
        } catch (\Exception $e) {
            $resp["message"] = $e->getMessage();
            $code = 500;
        }

        return JsonResponse::create($resp, $code);
    }

}