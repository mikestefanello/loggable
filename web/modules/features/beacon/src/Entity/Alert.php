<?php

namespace Drupal\beacon\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Psr\Log\LogLevel;
use Drupal\Core\Cache\Cache;

/**
 * Defines the Alert entity.
 *
 * @ingroup beacon
 *
 * @ContentEntityType(
 *   id = "alert",
 *   label = @Translation("Alert"),
 *   label_collection = @Translation("Alerts"),
 *   label_singular = @Translation("alert"),
 *   label_plural = @Translation("alerts"),
 *   label_count = @PluralTranslation(
 *     singular = "@count alert",
 *     plural = "@count alerts"
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\beacon\BeaconEntityListBuilder",
 *     "views_data" = "Drupal\beacon\Entity\AlertViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\beacon\Form\AlertForm",
 *       "add" = "Drupal\beacon\Form\AlertForm",
 *       "edit" = "Drupal\beacon\Form\AlertForm",
 *       "delete" = "Drupal\beacon\Form\AlertDeleteForm",
 *     },
 *     "access" = "Drupal\beacon\AlertAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\beacon\AlertHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "alert",
 *   admin_permission = "administer alert entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *   },
 *   links = {
 *     "canonical" = "/alert/{alert}",
 *     "add-form" = "/alert/add",
 *     "edit-form" = "/alert/{alert}/edit",
 *     "delete-form" = "/alert/{alert}/delete",
 *     "collection" = "/admin/structure/alert",
 *   },
 *   field_ui_base_route = "entity.alert.collection"
 * )
 */
class Alert extends BeaconContentEntityBase implements AlertInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function setEnabled() {
    $this->set('enabled', TRUE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setDisabled() {
    $this->set('enabled', FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->enabled->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    // Add the user tag.
    $tags = ['user.alerts:' . $this->getOwnerId()];

    // Extract the channel.
    $channel = $this->channel->entity;

    // Check if the channel still exists.
    if ($channel) {
      // Add the channel tag.
      $tags[] = 'channel.alerts:' . $this->channel->entity->id();
    }

    // Merge and return.
    return Cache::mergeTags(parent::getCacheTagsToInvalidate(), $tags);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the alert.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['channel'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Channel'))
      ->setDescription(t('The channel this alert belongs to.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'channel')
      ->setSetting('handler', 'default')
      ->setSetting('handler', 'default')->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -3,
      ]);

    $fields['enabled'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Enabled'))
      ->setDescription(t('Enabled alerts will send out messages when events are created.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'boolean',
        'weight' => -3,
        'settings' => [
          'format' => 'yes-no',
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => TRUE,
        ],
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setDescription(t('The alert type.'))
      ->setSettings([
        'max_length' => 64,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['settings'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Settings'))
      ->setDescription(t('The alert settings.'))
      ->setDefaultValue('')
      ->setSetting('admin_only', TRUE);

    $fields['event_severity'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Event severity'))
      ->setDescription(t('The event severity to send alerts for.'))
      ->setRequired(TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSettings([
        'allowed_values' => [
          LogLevel::DEBUG => 'Debug',
          LogLevel::INFO => 'Info',
          LogLevel::NOTICE => 'Notice',
          LogLevel::WARNING => 'Warning',
          LogLevel::ERROR => 'Error',
          LogLevel::CRITICAL => 'Critical',
          LogLevel::ALERT => 'Alert',
          LogLevel::EMERGENCY => 'Emergency',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['event_types'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Event types'))
      ->setDescription(t('The event types to send alerts for. If omitted, no filtering for event type will be used. Wildcard (*) characters are supported. Add a single type filter for every textbox.'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setSettings([
        'max_length' => 64,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function getParentReferenceFieldName() {
    return 'channel';
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->type->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    $settings = $this->settings->value;
    return $settings ? unserialize($settings) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings) {
    return $this->set('settings', serialize($settings));
  }

  /**
   * {@inheritdoc}
   */
  public function getEventTypes() {
    $types = [];
    foreach ($this->event_types->getValue() as $value) {
      $types[] = $value['value'];
    }
    return $types;
  }

}
