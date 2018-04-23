<?php

namespace Drupal\beacon_billing\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Stripe\Subscription as StripeSubscription;

/**
 * Defines the Subscription entity.
 *
 * @ingroup beacon_billing
 *
 * @ContentEntityType(
 *   id = "subscription",
 *   label = @Translation("Subscription"),
 *   label_collection = @Translation("Subscriptions"),
 *   label_singular = @Translation("subscription"),
 *   label_plural = @Translation("subscriptions"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\beacon_billing\SubscriptionListBuilder",
 *     "views_data" = "Drupal\beacon_billing\Entity\SubscriptionViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\beacon_billing\Form\SubscriptionForm",
 *       "add" = "Drupal\beacon_billing\Form\SubscriptionForm",
 *       "edit" = "Drupal\beacon_billing\Form\SubscriptionForm",
 *       "delete" = "Drupal\beacon_billing\Form\SubscriptionDeleteForm",
 *     },
 *     "access" = "Drupal\beacon_billing\SubscriptionAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\beacon_billing\SubscriptionHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "subscriptions",
 *   admin_permission = "administer subscription entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "email",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/subscription/{subscription}",
 *     "add-form" = "/admin/structure/subscription/add",
 *     "edit-form" = "/admin/structure/subscription/{subscription}/edit",
 *     "delete-form" = "/admin/structure/subscription/{subscription}/delete",
 *     "collection" = "/admin/structure/subscription",
 *   },
 *   field_ui_base_route = "entity.subscription.collection"
 * )
 */
class Subscription extends ContentEntityBase implements SubscriptionInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Clear the billing cache.
    // TODO: Can we inject this?
    \Drupal::service('beacon_billing')->clearCache($this);
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function hasCard() {
    return (bool) $this->get('cc_last_4')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isSuspended() {
    return in_array($this->getStatus(), [StripeSubscription::STATUS_CANCELED, StripeSubscription::STATUS_UNPAID]);
  }

  /**
   * {@inheritdoc}
   */
  public function getSubscriptionId() {
    return $this->get('subscription_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomerId() {
    return $this->get('customer_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Full name'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDescription(t('The email address used to receive billing notifications and alerts.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'email_default',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['address'] = BaseFieldDefinition::create('address')
      ->setLabel(t('Address'))
      ->setRequired(TRUE)
      ->setSetting('fields', [
        'administrativeArea' => 'administrativeArea',
        'locality' => 'locality',
        'dependentLocality' => 'dependentLocality',
        'postalCode' => 'postalCode',
        'addressLine1' => 'addressLine1',
        'addressLine2' => 'addressLine2',
        'sortingCode' => 0,
        'organization' => 0,
        'givenName' => 0,
        'additionalName' => 0,
        'familyName' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'address_default',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'address_default',
        'weight' => -3,
        'settings' => [
          'default_country' => 'US',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Subscription entity.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setSetting('admin_only', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 2,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['customer_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer ID'))
      ->setDescription(t('The external customer ID.'))
      ->setSetting('admin_only', TRUE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['subscription_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subscription ID'))
      ->setDescription(t('The external subscription ID.'))
      ->setSetting('admin_only', TRUE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['cc_last_4'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Credit card last 4 digits'))
      ->setDescription(t('The last 4 digits of the saved credit card.'))
      ->setSetting('admin_only', TRUE)
      ->setSetting('max_length', 4)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setDescription(t('The status of the subscription.'))
      ->setSetting('admin_only', TRUE)
      ->setSettings([
        'allowed_values' => [
          StripeSubscription::STATUS_ACTIVE => 'Active',
          StripeSubscription::STATUS_CANCELED => 'Canceled',
          StripeSubscription::STATUS_PAST_DUE => 'Past due',
          StripeSubscription::STATUS_TRIALING => 'Trialing',
          StripeSubscription::STATUS_UNPAID => 'Unpaid',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 7,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
