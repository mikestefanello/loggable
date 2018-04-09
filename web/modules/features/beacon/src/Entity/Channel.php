<?php

namespace Drupal\beacon\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Channel entity.
 *
 * @ingroup beacon
 *
 * @ContentEntityType(
 *   id = "channel",
 *   label = @Translation("Channel"),
 *   label_collection = @Translation("Channels"),
 *   label_singular = @Translation("channel"),
 *   label_plural = @Translation("channels"),
 *   label_count = @PluralTranslation(
 *     singular = "@count channel",
 *     plural = "@count channels"
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\beacon\BeaconEntityListBuilder",
 *     "views_data" = "Drupal\beacon\Entity\ChannelViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\beacon\Form\ChannelForm",
 *       "add" = "Drupal\beacon\Form\ChannelForm",
 *       "edit" = "Drupal\beacon\Form\ChannelForm",
 *       "delete" = "Drupal\beacon\Form\ChannelDeleteForm",
 *     },
 *     "access" = "Drupal\beacon\ChannelAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\beacon\BeaconEntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "channel",
 *   admin_permission = "administer channel entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *   },
 *   links = {
 *     "canonical" = "/channel/{channel}",
 *     "add-form" = "/channel/add",
 *     "edit-form" = "/channel/{channel}/edit",
 *     "delete-form" = "/channel/{channel}/delete",
 *     "collection" = "/admin/structure/channel",
 *   },
 *   field_ui_base_route = "entity.channel.collection"
 * )
 */
class Channel extends BeaconContentEntityBase implements ChannelInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the channel.'))
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

    $fields['url'] = BaseFieldDefinition::create('link')
      ->setLabel(t('URL'))
      ->setDescription(t('The URL of the channel.'))
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
          'rel' => '0'
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'link_default',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('A description of the channel.'))
      ->setSettings([
        'max_length' => 500,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);

    return $fields;
  }

}
