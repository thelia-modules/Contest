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

namespace Contest\Event\Base;

use Contest\Event\Module\ContestEvents as ChildContestEvents;

/**
 * Class MailEvents
 * @package Event\Base
 */
class MailEvents
{
        const SEND = ChildContestEvents::SEND_MAIL_WIN;
}