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

use Contest\Model\Game;
use Contest\Model\Participate;
use Thelia\Core\Event\ActionEvent;

/**
 * Class MailEvent
 * @package Event\Base
 */
class MailEvent extends ActionEvent
{
    protected $game;
    protected $participate;

    /**
     * @return Game
     */
    public function getGame()
    {
        return $this->game;
    }

    /**
     * @param Game $game
     */
    public function setGame($game)
    {
        $this->game = $game;
        return $this;
    }

    /**
     * @return Participate
     */
    public function getParticipate()
    {
        return $this->participate;
    }

    /**
     * @param Participate $participate
     */
    public function setParticipate($participate)
    {
        $this->participate = $participate;
        return $this;
    }
}