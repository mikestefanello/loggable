<?php

namespace Drupal\beacon\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Psr\Log\LogLevel;
use Drupal\Core\Cache\Cache;

/**
 * Defines the Event entity.
 *
 * @ingroup beacon
 *
 * @ContentEntityType(
 *   id = "event",
 *   label = @Translation("Event"),
 *   label_collection = @Translation("Events"),
 *   label_singular = @Translation("event"),
 *   label_plural = @Translation("events"),
 *   label_count = @PluralTranslation(
 *     singular = "@count event",
 *     plural = "@count events"
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\beacon\BeaconEntityListBuilder",
 *     "views_data" = "Drupal\beacon\Entity\EventViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\beacon\Form\EventForm",
 *       "add" = "Drupal\beacon\Form\EventForm",
 *       "edit" = "Drupal\beacon\Form\EventForm",
 *       "delete" = "Drupal\beacon\Form\EventDeleteForm",
 *     },
 *     "access" = "Drupal\beacon\EventAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\beacon\EventHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "event",
 *   admin_permission = "administer event entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *   },
 *   links = {
 *     "canonical" = "/event/{event}",
 *     "add-form" = "/event/add",
 *     "edit-form" = "/event/{event}/edit",
 *     "delete-form" = "/event/{event}/delete",
 *     "collection" = "/admin/structure/event",
 *   },
 *   field_ui_base_route = "entity.event.collection"
 * )
 */
class Event extends BeaconContentEntityBase implements EventInterface {

  use EntityChangedTrait;

  /**
   * The event message max length.
   *
   * @var int
   */
  const MESSAGE_MAX_LENGTH = 5000;

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Dispatch alerts for new events.
    if (!$update) {
      \Drupal::service('beacon.alert_dispatcher')->dispatch($this);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    return Cache::mergeTags(parent::getCacheTagsToInvalidate(), [
      'channel.events:' . $this->channel->entity->id(),
      'user.events:' . $this->getOwnerId(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['channel'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Channel'))
      ->setDescription(t('The channel this event belongs to.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'channel')
      ->setSetting('handler', 'default')
      ->setSetting('handler', 'default')->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -5,
      ]);

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setDescription(t('The event type.'))
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
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['severity'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Severity'))
      ->setDescription(t('The event severity.'))
      ->setDefaultValue(LogLevel::NOTICE)
      ->setRequired(TRUE)
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
        'type' => 'options_select',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user'] = BaseFieldDefinition::create('string')
      ->setLabel(t('User'))
      ->setDescription(t('The user that triggered the event.'))
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
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['url'] = BaseFieldDefinition::create('link')
      ->setLabel(t('URL'))
      ->setDescription(t('A URL that references the event.'))
      ->setSettings([
        'link_type' => 16,
        'title' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'link',
        'weight' => -3,
        'settings' => [
          'trim_length' => NULL,
          'target' => '_blank',
          'url_only' => FALSE,
          'url_plain' => FALSE,
          'rel' => '0',
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'link_default',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);

    $fields['message'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Message'))
      ->setDescription(t('The event message.'))
      ->setDefaultValue('')
      ->addPropertyConstraints('value', [
        'Length' => [
          'max' => self::MESSAGE_MAX_LENGTH,
          'maxMessage' => 'This message is too long. It should have ' . self::MESSAGE_MAX_LENGTH . ' characters or less.',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 0,
        'settings' => [
          'rows' => 10,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['expire'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Expires on'))
      ->setDescription(t('The time when this event expires.'))
      ->setDefaultValueCallback('Drupal\beacon\Entity\Event::getDefaultExpireTimestamp')
      ->setSetting('admin_only', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => -3,
        'settings' => [
          'date_format' => 'medium',
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return t('Event %event', ['%event' => $this->uuid()]);
  }

  /**
   * {@inheritdoc}
   */
  public static function getParentReferenceFieldName() {
    return 'channel';
  }

  /**
   * Default value callback for 'expire' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getDefaultExpireTimestamp() {
    return [strtotime('+2 weeks')];
  }

}
