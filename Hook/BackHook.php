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
use Thelia\Core\Hook\BaseHook;
use Symfony\Component\Routing\Router;
use Thelia\Core\Event\Hook\HookRenderBlockEvent;
use Thelia\Core\Translation\Translator;

/**
 * Class BackHook
 * @package Hook
 */
class BackHook extends BaseHook
{
    protected $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    protected function transQuick($id, $locale, $parameters = [])
    {
        if ($this->translator === null) {
            $this->translator = Translator::getInstance();
        }
        return $this->trans($id, $parameters, Contest::MESSAGE_DOMAIN, $locale);
    }

    public function onTopMenuTools(HookRenderBlockEvent $event)
    {
        $url = $this->router->generate("contest.game.list");
        $lang = $this->getSession()->getLang();
        $title = $this->transQuick("Contest", $lang->getLocale());
        $event->add(
            [
                "id" => "contest",
                "class" => "",
                "title" => $title,
                "url" => $url
            ]
        );
    }
}