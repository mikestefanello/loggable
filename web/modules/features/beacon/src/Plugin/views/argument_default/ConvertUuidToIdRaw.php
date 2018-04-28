<?php

namespace Drupal\beacon\Plugin\views\argument_default;

use Drupal\views\Plugin\views\argument_default\Raw;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Default argument plugin to convert a raw UUID value from the URL.
 *
 * The value is converted to an entity ID.
 *
 * @ingroup views_argument_default_plugins
 *
 * @ViewsArgumentDefault(
 *   id = "convert_uuid_to_id_raw",
 *   title = @Translation("Raw UUID value from URL converted to entity ID")
 * )
 */
class ConvertUuidToIdRaw extends Raw {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs a CovertUuidToIdRaw object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The alias manager.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AliasManagerInterface $alias_manager, CurrentPathStack $current_path, EntityRepositoryInterface $entity_repository) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $alias_manager, $current_path);
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('path.alias_manager'),
      $container->get('path.current'),
      $container->get('entity.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['entity_type_id'] = NULL;
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['entity_type_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Entity type ID'),
      '#default_value' => $this->options['entity_type_id'],
      '#description' => $this->t('Select the entity type ID of the UUID in the URL.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getArgument() {
    if ($arg = parent::getArgument()) {
      $entity = $this
        ->entityRepository
        ->loadEntityByUuid($this->options['entity_type_id'], $arg);

      if ($entity) {
        return $entity->id();
      }
    }
  }

}
