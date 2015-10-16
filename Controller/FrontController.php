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

use Thelia\Controller\Front\BaseFrontController;

/**
 * Class FrontController
 * @package Contest\Controller
 */
class FrontController extends BaseFrontController
{
    public function gameAction($id){
       return  $this->render("game",["game_id" => $id]);
    }
}