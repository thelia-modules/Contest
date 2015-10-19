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

namespace Contest\EventListeners;

use Contest\Contest;
use Contest\Event\MailEvent;
use Contest\Event\MailEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Action\BaseAction;
use Thelia\Core\Template\ParserInterface;
use Thelia\Mailer\MailerFactory;
use Thelia\Model\ConfigQuery;
use Thelia\Model\Lang;
use Thelia\Model\MessageQuery;

/**
 * Class MailListener
 * @package Contest\EventListeners
 */
class MailListener extends BaseAction implements EventSubscriberInterface
{

    /**
     * @var MailerFactory
     */
    protected $mailer;
    /**
     * @var ParserInterface
     */
    protected $parser;

    public function __construct(ParserInterface $parser, MailerFactory $mailer)
    {
        $this->parser = $parser;
        $this->mailer = $mailer;
    }

    /**
     * @return \Thelia\Mailer\MailerFactory
     */
    public function getMailer()
    {
        return $this->mailer;
    }


    public static function getSubscribedEvents()
    {
        return array(
            MailEvents::SEND => ["sendWin", 128]
        );
    }

    public function sendWin(MailEvent $event)
    {
        if ($event->getGame() && $event->getParticipate()) {
            $contact_email = ConfigQuery::read('store_email', false);
            $lang = Lang::getDefaultLanguage();
            $locale = $lang->getLocale();

            $message = MessageQuery::create()
                ->filterByName(Contest::MESSAGE_WIN)
                ->findOne();

            if (false === $message) {
                throw new \Exception(sprintf("Failed to load message '%s'.", Contest::MESSAGE_WIN));
            }

            $game = $event->getGame();
            $participate = $event->getParticipate();
            $email = $participate->getEmail();

            $this->parser->assign('game_id', $game->getId());
            $this->parser->assign('participate_id', $participate->getId());

            $message
                ->setLocale($locale);

            $name= "";
            if($customer = $participate->getCustomer()){
                $name = $customer->getFirstname()." ".$customer->getLastname();
            }

            $instance = \Swift_Message::newInstance()
                ->addTo($email, $name)
                ->addFrom($contact_email, ConfigQuery::read('store_name'))
            ;

            // Build subject and body
            $message->buildMessage($this->parser, $instance);

            $this->getMailer()->send($instance);
        }

    }
}