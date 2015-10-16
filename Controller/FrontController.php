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
use Contest\Event\Module\ContestEvents;
use Contest\Event\ParticipateEvent;
use Contest\Model\AnswerQuery;
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
    public function gameAction($id)
    {
        return $this->render("game", ["game_id" => $id]);
    }

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

        try {

            /* TEST Question */
            $question_correct = [];
            $all_correct = true;
            $questions = QuestionQuery::create()->filterByGameId($id)->find();
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
            if ($all_correct) {
                $retour["redirect_url"] = "";
            } else {
                $retour["redirect_url"] = "";
            }

            /* Create participate */
            $this->dispatch(ContestEvents::PARTICIPATE_CREATE, $event);
            $retour["message"] = $this->getTranslator()->trans("Success", [], Contest::MESSAGE_DOMAIN);

        } catch (\Exception $e) {
            $retour["code"] = $e->getCode();
            $retour["message"] = $e->getMessage();
        }


        return JsonResponse::create($retour);
    }

    protected function isCorrectQuestion(Question $question, $answer_data)
    {
        $answers = AnswerQuery::create()->filterByCorrect(false)->filterByQuestion($question)->filterById($answer_data)->find();
        if ($answers && count($answers) > 0) {
            return false;
        }

        return true;

    }
}