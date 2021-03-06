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

namespace Contest\Event;
use Contest\Event\Base\MailEvents as BaseMailEvents;
use Contest\Event\Module\ContestEvents;

/**
 * Class MailEvents
 * @package Event
 */
class MailEvents extends BaseMailEvents
{
    const SEND_FRIEND = ContestEvents::SEND_MAIL_WIN;
}