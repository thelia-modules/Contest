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

namespace Contest\Hook;

use Contest\Contest;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;

/**
 * Class ConfigHook
 * @package Contest\Hook
 */
class ConfigHook extends BaseHook
{
    public function onModuleConfig(HookRenderEvent $event)
    {
        $param = [
            "WIN_OPTION" => Contest::getConfigValue(Contest::WIN_OPTION),
            "CONNECT_OPTION" => Contest::getConfigValue(Contest::CONNECT_OPTION),
            "FRIEND_OPTION" => Contest::getConfigValue(Contest::FRIEND_OPTION),
            "FRIEND_MAX_OPTION" => Contest::getConfigValue(Contest::FRIEND_MAX_OPTION),
            "MAX_PARTICIPATE_OPTION" => Contest::getConfigValue(Contest::MAX_PARTICIPATE_OPTION),
        ];
        $event->add($this->render("module_configuration.html", $param));
    }

    public function onModuleConfigInsertJS(HookRenderEvent $event)
    {
        $event->add($this->render("module-config-js.html"));
    }
}