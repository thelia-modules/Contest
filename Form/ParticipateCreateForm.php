<?php
/**
* This class has been generated by TheliaStudio
* For more information, see https://github.com/thelia-modules/TheliaStudio
*/

namespace Contest\Form;

use Contest\Contest;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Thelia\Form\BaseForm;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class ParticipateCreateForm
 * @package Contest\Form\Base
 * @author TheliaStudio
 */
class ParticipateCreateForm extends BaseForm
{
    const FORM_NAME = "participate_create";

    public function buildForm()
    {
        $translationKeys = $this->getTranslationKeys();
        $fieldsIdKeys = $this->getFieldsIdKeys();

        $this->addEmailField($translationKeys, $fieldsIdKeys);
        $this->addWinField($translationKeys, $fieldsIdKeys);
        $this->addGameIdField($translationKeys, $fieldsIdKeys);
        $this->addCustomerIdField($translationKeys, $fieldsIdKeys);
    }

    protected function addEmailField(array $translationKeys, array $fieldsIdKeys)
    {
        $this->formBuilder->add("email", TextType::class, array(
            "label" => $this->translator->trans($this->readKey("email", $translationKeys), [], Contest::MESSAGE_DOMAIN),
            "label_attr" => ["for" => $this->readKey("email", $fieldsIdKeys)],
            "required" => true,
            "constraints" => array(
                new NotBlank(),
            ),
            "attr" => array(
            )
        ));
    }

    protected function addWinField(array $translationKeys, array $fieldsIdKeys)
    {
        $this->formBuilder->add("win", CheckboxType::class, array(
            "label" => $this->translator->trans($this->readKey("win", $translationKeys), [], Contest::MESSAGE_DOMAIN),
            "label_attr" => ["for" => $this->readKey("win", $fieldsIdKeys)],
            "required" => false,
            "constraints" => array(
            ),
            "attr" => array(
            )
        ));
    }

    protected function addGameIdField(array $translationKeys, array $fieldsIdKeys)
    {
        $this->formBuilder->add("game_id", IntegerType::class, array(
            "label" => $this->translator->trans($this->readKey("game_id", $translationKeys), [], Contest::MESSAGE_DOMAIN),
            "label_attr" => ["for" => $this->readKey("game_id", $fieldsIdKeys)],
            "required" => true,
            "constraints" => array(
                new NotBlank(),
            ),
            "attr" => array(
            )
        ));
    }

    protected function addCustomerIdField(array $translationKeys, array $fieldsIdKeys)
    {
        $this->formBuilder->add("customer_id", IntegerType::class, array(
            "label" => $this->translator->trans($this->readKey("customer_id", $translationKeys), [], Contest::MESSAGE_DOMAIN),
            "label_attr" => ["for" => $this->readKey("customer_id", $fieldsIdKeys)],
            "required" => false,
            "constraints" => array(
            ),
            "attr" => array(
            )
        ));
    }

    public static function getName()
    {
        return static::FORM_NAME;
    }

    public function readKey($key, array $keys, $default = '')
    {
        if (isset($keys[$key])) {
            return $keys[$key];
        }

        return $default;
    }

    public function getTranslationKeys()
    {
        return array(
            "email" => "Email",
            "win" => "Win",
            "game_id" => "Game id",
            "customer_id" => "Customer id",
        );
    }

    public function getFieldsIdKeys()
    {
        return array(
            "email" => "participate_email",
            "win" => "participate_win",
            "game_id" => "participate_game_id",
            "customer_id" => "participate_customer_id",
        );
    }
}
