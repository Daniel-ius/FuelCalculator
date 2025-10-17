<?php

namespace Drupal\fuel_calculator\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

#[ContentEntityType(
    id: "fuel_calculation",
    label: new TranslatableMarkup("Fuel Calculation"),
    label_collection: new TranslatableMarkup("Fuel Calculations"),
    label_singular: new TranslatableMarkup("fuel calculation"),
    label_plural: new TranslatableMarkup("fuel calculations"),
    entity_keys: [
    "id" => "id",
    "uuid" => "uuid",
    ],
    handlers: [
    "view_builder" => "Drupal\Core\Entity\EntityViewBuilder",
    "list_builder" => "Drupal\Core\Entity\EntityListBuilder",
    "views_data" => "Drupal\views\EntityViewsData",
    "access" => "Drupal\Core\Entity\EntityAccessControlHandler",
    ],
    admin_permission: "administer fuel calculation entities",
    base_table: "fuel_calculation",
    label_count: [
    "singular" => new TranslatableMarkup("@count fuel calculation"),
    "plural" => new TranslatableMarkup("@count fuel calculations"),
    ],
)]
class FuelCalculation extends ContentEntityBase implements ContentEntityInterface
{
    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['distance'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Distance'))
            ->setDescription(t('Distance in kilometers.'))
            ->setSettings(
                [
                'precision' => 10,
                'scale' => 2,
                ]
            )
            ->setRequired(true);

        $fields['efficiency'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Fuel Efficiency'))
            ->setDescription(t('Fuel efficiency in L/100km.'))
            ->setSettings(
                [
                'precision' => 10,
                'scale' => 2,
                ]
            )
            ->setRequired(true);

        $fields['price'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Fuel Price'))
            ->setDescription(t('Fuel price per liter.'))
            ->setSettings(
                [
                'precision' => 10,
                'scale' => 2,
                ]
            )
            ->setRequired(true);

        $fields['fuel_spent'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Fuel Spent'))
            ->setDescription(t('Calculated fuel spent in liters.'))
            ->setSettings(
                [
                'precision' => 10,
                'scale' => 2,
                ]
            )
            ->setRequired(true);

        $fields['fuel_cost'] = BaseFieldDefinition::create('decimal')
            ->setLabel(t('Fuel Cost'))
            ->setDescription(t('Calculated fuel cost.'))
            ->setSettings(
                [
                'precision' => 10,
                'scale' => 2,
                ]
            )
            ->setRequired(true);

        $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
            ->setLabel(t('User'))
            ->setDescription(t('The user who performed the calculation.'))
            ->setSetting('target_type', 'user')
            ->setSetting('handler', 'default')
            ->setDefaultValueCallback('Drupal\fuel_calculator\Entity\FuelCalculation::getCurrentUserId');

        $fields['ip_address'] = BaseFieldDefinition::create('string')
            ->setLabel(t('IP Address'))
            ->setDescription(t('IP address of the user.'))
            ->setSettings(
                [
                'max_length' => 45,
                'text_processing' => 0,
                ]
            );

        $fields['created'] = BaseFieldDefinition::create('created')
            ->setLabel(t('Created'))
            ->setDescription(t('The time that the calculation was created.'));

        return $fields;
    }

    /**
     *
     * @return array
     *   An array of default values.
     */
    public static function getCurrentUserId()
    {
        return [\Drupal::currentUser()->id()];
    }

    /**
     *
     * @return float
     *   The distance value.
     */
    public function getDistance()
    {
        return $this->get('distance')->value;
    }

    /**
     *
     * @return float
     *   The efficiency value.
     */
    public function getEfficiency()
    {
        return $this->get('efficiency')->value;
    }

    /**
     *
     * @return float
     *   The price value.
     */
    public function getPrice()
    {
        return $this->get('price')->value;
    }

    /**
     *
     * @return float
     *   The fuel spent value.
     */
    public function getFuelSpent()
    {
        return $this->get('fuel_spent')->value;
    }

    /**
     *
     * @return float
     *   The fuel cost value.
     */
    public function getFuelCost()
    {
        return $this->get('fuel_cost')->value;
    }

    /**
     *
     * @return string
     *   The IP address.
     */
    public function getIpAddress()
    {
        return $this->get('ip_address')->value;
    }

    /**
     *
     * @return int
     *   The creation timestamp.
     */
    public function getCreatedTime()
    {
        return $this->get('created')->value;
    }
}
