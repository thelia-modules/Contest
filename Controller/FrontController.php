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
use Contest\Event\MailEvents;
use Contest\Event\MailFriendEvent;
use Contest\Event\Module\ContestEvents;
use Contest\Event\ParticipateEvent;
use Contest\Model\AnswerQuery;
use Contest\Model\GameQuery;
use Contest\Model\ParticipateQuery;
use Contest\Model\Question;
use Contest\Model\QuestionQuery;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\HttpFoundation\JsonResponse;

/**
 * Class FrontController
 * @package Contest\Controller
 */
class FrontController extends BaseFrontController
{
    /**
     * Render Game
     * @param $id
     * @return \Thelia\Core\HttpFoundation\Response
     */
    public function gameAction($id)
    {
        if (Contest::getConfigValue(Contest::CONNECT_OPTION) && null == $this->getSecurityContext()->getCustomerUser()) {
            return $this->generateRedirectFromRoute("customer.login.view");
        }

        if (null != $customer = $this->getSecurityContext()->getCustomerUser()) {
            if (!$this->testMaxParticipation($customer->getUsername(), $id)) {
                return $this->render("game-max-participate", [
                    "game_id" => $id,
                    "email" => $customer->getUsername(),
                    "MAX_PARTICIPATE_OPTION" => Contest::getConfigValue(Contest::MAX_PARTICIPATE_OPTION)
                ]);
            }
        }

        return $this->render("game", ["game_id" => $id]);
    }

    /**
     * Process the game
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response|static
     */
    public function processGameAction($id)
    {

        $this->checkXmlHttpRequest();
        $retour = [
            "code" => 200,
            "message" => ""
        ];

        $data = $this->getRequest()->get("questions");
        $customer_id = $this->getRequest()->get("customer_id");
        $email = $this->getRequest()->get("email");
        $event = new ParticipateEvent();

        $event->setGameId($id);

        /* TEST MAIL AND CUSTOMER */
        if ($customer_id) {
            $customer = $this->getSecurityContext()->getCustomerUser();
            if ($customer && $customer->getId() == $customer_id) {
                $event->setCustomerId($customer_id);
                $event->setEmail($customer->getUsername());
            }
        } else {
            if ($email) {
                $event->setEmail($email);
            }
        }

        if (!$this->testMaxParticipation($event->getEmail(), $id)) {
            $retour["html"] = $this->renderRaw("include/game-max-participate-content", [
                "game_id" => $id,
                "email" => $event->getEmail(),
                "MAX_PARTICIPATE_OPTION" => Contest::getConfigValue(Contest::MAX_PARTICIPATE_OPTION)
            ]);
            $retour["code"] = "9999";

            return JsonResponse::create($retour);
        }

        try {

            /* TEST Question */
            $question_correct = [];
            $all_correct = true;
            $questions = QuestionQuery::create()->filterByVisible(true)->filterByGameId($id)->find();
            if ($questions && count($questions) == count($data)) {
                /** @var Question $question */
                foreach ($questions as $question) {
                    if (isset($data[$question->getId()])) {
                        $answer_data = $data[$question->getId()];
                        $correct = $this->isCorrectQuestion($question, $answer_data);
                        $question_correct[$question->getId()] = $correct;

                        if (!$correct) {
                            $all_correct = false;
                        }
                    }
                }
            }

            /* TEST WIN */
            /* TODO : Voir les conditions de victoire */
            $event->setWin($all_correct);

            /* Create participate */
            $this->dispatch(ContestEvents::PARTICIPATE_CREATE, $event);

            if (Contest::getConfigValue(Contest::WIN_OPTION)) {
                if ($all_correct) {
                    $retour["url"] = $this->getRouteFromRouter(Contest::ROUTER, "contest.front.game.success",
                        ["id" => $id]);
                } else {
                    $retour["url"] = $this->getRouteFromRouter(Contest::ROUTER, "contest.front.game.fail",
                        ["id" => $id]);
                }
            } else {
                $retour["url"] = $this->getRouteFromRouter(Contest::ROUTER, "contest.front.game.end",
                    ["id" => $id, "part" => $event->getParticipate()->getId()]);
            }

            $retour["message"] = $this->getTranslator()->trans("Success", [], Contest::MESSAGE_DOMAIN);

        } catch (\Exception $e) {
            $retour["code"] = $e->getCode();
            $retour["message"] = $e->getMessage();
        }


        return JsonResponse::create($retour);
    }

    /**
     * Render Success page
     * @param $id
     * @return \Thelia\Core\HttpFoundation\Response
     */
    public function successGameAction($id)
    {
        return $this->render("game-success", ["game_id" => $id]);
    }

    /**
     * Render Fail page
     * @param $id
     * @return \Thelia\Core\HttpFoundation\Response
     */
    public function failGameAction($id)
    {
        return $this->render("game-fail", ["game_id" => $id]);
    }

    /**
     * Render End page
     * @param $id
     * @return \Thelia\Core\HttpFoundation\Response
     */
    public function endGameAction($id, $part)
    {
        $param = [
            "game_id" => $id,
            "part_id" => $part,
            "FRIEND_OPTION" => Contest::getConfigValue(Contest::FRIEND_OPTION),
            "FRIEND_MAX_OPTION" => Contest::getConfigValue(Contest::FRIEND_MAX_OPTION)
        ];

        return $this->render("game-end", $param);
    }

    public function sendInvitationAction($id, $part)
    {
        $resp = array(
            "message" => ""
        );
        $code = 200;
        try {
            $event = new MailFriendEvent();
            $event->setGame(GameQuery::create()->findOneById($id));
            $event->setParticipate(ParticipateQuery::create()->findOneById($part));
            $friends = $this->getRequest()->get("friends");
            if (is_array($friends)) {
                $event->setFriends($friends);
            }
            $this->dispatch(MailEvents::SEND_FRIEND, $event);
            $resp["message"] = $this->getTranslator()->trans("Send invitation", [], Contest::MESSAGE_DOMAIN);
        } catch (\Exception $e) {
            $resp["message"] = $e->getMessage();
            $code = 500;
        }

        return JsonResponse::create($resp, $code);
    }

    /**
     * Test if anwsers are correct for the question
     * @param Question $question
     * @param $answer_data
     * @return bool
     * @throws \Propel\Runtime\Exception\PropelException
     */
    protected function isCorrectQuestion(Question $question, $answer_data)
    {
        $answers = AnswerQuery::create()->filterByCorrect(false)->filterByQuestion($question)->filterById($answer_data)->find();
        if ($answers && count($answers) > 0) {
            return false;
        }

        return true;

    }

    protected function testMaxParticipation($email, $game_id)
    {
        return (count(ParticipateQuery::create()->filterByEmail($email)->filterByGameId($game_id)->find()) < Contest::getConfigValue(Contest::MAX_PARTICIPATE_OPTION)) ? true : false;
    }
}