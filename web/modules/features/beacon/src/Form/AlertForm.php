<?php

namespace Drupal\beacon\Form;

use Drupal\beacon\Plugin\AlertTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Alert edit forms.
 *
 * @ingroup beacon
 */
class AlertForm extends BeaconContentEntityForm {

  /**
   * The alert type plugin manager.
   *
   * @var \Drupal\beacon\Plugin\AlertTypeManager
   */
  protected $alertTypeManager;

  /**
   * Constructs a AlertForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\beacon\Plugin\AlertTypeManager $alert_type_manager
   *   The alert type plugin manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, AlertTypeManager $alert_type_manager) {
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
    $this->alertTypeManager = $alert_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('plugin.manager.alert_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    // Gather the alert type plugins.
    $alert_types = [];
    foreach ($this->alertTypeManager->getDefinitions() as $alert_type_id => $alert_type) {
      $alert_types[$alert_type_id] = $alert_type['label'];
    }

    // Sort the types by name.
    asort($alert_types);

    // Convert type to a select list with alert type options.
    $form['type']['widget'][0]['value']['#options'] = $alert_types;
    $form['type']['widget'][0]['value']['#type'] = 'select';
    $form['type']['widget'][0]['value']['#size'] = NULL;

    // Add AJAX so the plugin settings form is reloaded.
    $form['type']['widget'][0]['value']['#ajax'] = [
      'callback' => '::addAlertTypeSettingsAjax',
      'wrapper' => 'alert-type-settings',
      'effect' => 'fade',
    ];

    // Add alert type settings.
    $form['alert_type_settings'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'alert-type-settings',
      ],
      'settings_form' => [],
    ];

    // Check if an alert type was selected.
    if ($alert_type = $form_state->getValue('type')) {
      $alert_type = $alert_type[0]['value'];
    }
    else {
      $alert_type = $entity->type->value;
    }

    // Check if an alert type was provided.
    if ($alert_type) {
      // Load the alert type plugin.
      $plugin = $this->alertTypeManager->createInstanceFromAlert($entity, $alert_type);

      // Add the settings form.
      $form['alert_type_settings']['settings_form'] = $plugin->settingsForm($form_state);
      $form['alert_type_settings']['settings_form']['#tree'] = TRUE;
    }

    // Add details wrappers.
    $this->addDetails($form, 'info', $this->t('Alert info'), ['name', 'channel', 'enabled']);
    $this->addDetails($form, 'type', $this->t('Alert type'), ['type', 'alert_type_settings']);
    $this->addDetails($form, 'filters', $this->t('Event filters'), ['event_types', 'event_severity']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entity = parent::validateForm($form, $form_state);

    // Check for an alert type.
    if ($entity->type->value) {
      // Allow the alert type plugin to validate the form.
      $this->alertTypeManager->createInstanceFromAlert($entity)->validateSettingsForm($form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Allow the alert type plugin to submit the form.
    $this->alertTypeManager->createInstanceFromAlert($entity)->submitSettingsForm($form_state);

    // Extract the current settings.
    $settings = $entity->settings->value;

    // Unserialize, if needed.
    $settings = $settings ? unserialize($settings) : [];

    // Add the settings for the select alert type plugin.
    $settings[$entity->type->value] = $form_state->getValue('settings_form');

    // Inject the settings value.
    $entity->set('settings', serialize($settings));

    // Save the entity.
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created alert %label.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved alert %label.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.channel.canonical', ['channel' => $entity->channel->entity->uuid()]);
  }

  /**
   * AJAX callback for toggling the alert type plugin settings form.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @return array
   *   The form component to render.
   */
  public function addAlertTypeSettingsAjax(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
    return $form['type_wrapper']['alert_type_settings'];
  }

}
