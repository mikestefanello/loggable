<?php

namespace Drupal\beacon\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for Alert type plugins.
 */
abstract class AlertTypeBase extends PluginBase implements AlertTypeInterface {

  use StringTranslationTrait;

  /**
   * The plugin settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Store the settings.
    $this->settings = array_merge($this->getDefaultSettings(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(FormStateInterface $form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateSettingsForm(FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitSettingsForm(FormStateInterface $form_state) {

  }

  /**
   * Return an array of default settings.
   *
   * @return array
   *   An array of default settings.
   */
  public function getDefaultSettings() {
    return [];
  }

}
