<?php
/**
* This class has been generated by TheliaStudio
* For more information, see https://github.com/thelia-modules/TheliaStudio
*/

namespace Contest\Form;

use Contest\Form\AnswerCreateForm as ChildAnswerCreateForm;
use Contest\Form\Type\AnswerIdType;

/**
 * Class AnswerForm
 * @package Contest\Form
 * @author TheliaStudio
 */
class AnswerUpdateForm extends ChildAnswerCreateForm
{
    const FORM_NAME = "answer_update";

    public function buildForm()
    {
        parent::buildForm();

        $this->formBuilder
            ->add("id", AnswerIdType::class)
            ->remove("visible")
        ;
    }

    public function getTranslationKeys()
    {
        return array(
            "id" => "id",
            "visible" => "visible",
            "correct" => "correct",
            "title" => "title",
            "description" => "description",
            "question_id" => "question_id",
        );
    }
}
