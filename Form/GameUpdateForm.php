<?php
/**
* This class has been generated by TheliaStudio
* For more information, see https://github.com/thelia-modules/TheliaStudio
*/

namespace Contest\Form;

use Contest\Form\GameCreateForm as ChildGameCreateForm;
use Contest\Form\Type\GameIdType;

/**
 * Class GameForm
 * @package Contest\Form
 * @author TheliaStudio
 */
class GameUpdateForm extends ChildGameCreateForm
{
    const FORM_NAME = "game_update";

    public function buildForm()
    {
        parent::buildForm();

        $this->formBuilder
            ->add("id", GameIdType::class)
            ->remove("visible")
        ;
    }

    public function getTranslationKeys()
    {
        return array(
            "id" => "id",
            "title" => "title",
            "description" => "description",
        );
    }
}
