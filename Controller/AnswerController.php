<?php
/**
* This class has been generated by TheliaStudio
* For more information, see https://github.com/thelia-modules/TheliaStudio
*/

namespace Contest\Controller;

use Contest\Controller\Base\AnswerController as BaseAnswerController;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\Template\ParserContext;
use Thelia\Tools\TokenProvider;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/module/Contest/answer", name="contest.answer")
 * Class AnswerController
 * @package Contest\Controller
 */
class AnswerController extends BaseAnswerController
{
    /**
     * @Route("", name=".list", methods="GET")
     */
    public function defaultAction()
    {
        return parent::defaultAction();
    }

    /**
     * @Route("", name=".create", methods="POST")
     */
    public function createAction(EventDispatcherInterface $eventDispatcher, TranslatorInterface $translator)
    {
        return parent::createAction($eventDispatcher, $translator);
    }

    /**
     * @Route("/edit", name=".view", methods="GET")
     */
    public function updateAction(ParserContext $parserContext)
    {
        return parent::updateAction($parserContext);
    }

    /**
     * @Route("/edit", name=".edit", methods="POST")
     */
    public function processUpdateAction(Request $request, EventDispatcherInterface $eventDispatcher, TranslatorInterface $translator)
    {
        return parent::processUpdateAction($request, $eventDispatcher, $translator);
    }

    /**
     * @Route("/delete", name=".delete", methods="POST")
     */
    public function deleteAction(Request $request, TokenProvider $tokenProvider, EventDispatcherInterface $eventDispatcher, ParserContext $parserContext)
    {
        return parent::deleteAction($request, $tokenProvider, $eventDispatcher, $parserContext);
    }

    /**
     * @Route("/toggleVisibility", name=".toggle_visibility", methods="GET")
     */
    public function setToggleVisibilityAction(EventDispatcherInterface $eventDispatcher)
    {
        return parent::setToggleVisibilityAction($eventDispatcher);
    }
}
