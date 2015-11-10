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

use Contest\Event\Base\MailEvent;

/**
 * Class MailFriendEvent
 * @package Contest\Event
 */
class MailFriendEvent extends MailEvent
{
    protected $friends;

    /**
     * @return mixed
     */
    public function getFriends()
    {
        return $this->friends;
    }

    /**
     * @param mixed $friends
     */
    public function setFriends($friends)
    {
        if (is_array($friends)) {
            $this->friends = $friends;
        } else {
            if (!in_array($friends, $this->friends)) {
                $this->friends[] = $friends;
            }
        }

        return $this->friends;
    }

    public function setFriend($friend)
    {
        if (!in_array($friend, $this->friends)) {
            $this->friends[] = $friend;
        }

        return $this->friends;
    }


}