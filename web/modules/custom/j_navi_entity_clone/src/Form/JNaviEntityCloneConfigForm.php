<?php

namespace Drupal\j_navi_entity_clone\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure J Navi Entity Clone settings.
 */
class JNaviEntityCloneConfigForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['j_navi_entity_clone.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'j_navi_entity_clone_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('j_navi_entity_clone.settings');
    $mappings = $config->get('mappings') ?? [];

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    $form['description'] = [
      '#markup' => '<p>' . $this->t('Configure entity cloning mappings. Define which entity types and bundles can be cloned to other types, and map their fields.') . '</p>',
    ];

    // Get number of mappings from form state or config.
    $num_mappings = $form_state->get('num_mappings');
    if ($num_mappings === NULL) {
      $num_mappings = !empty($mappings) ? count($mappings) : 1;
      $form_state->set('num_mappings', $num_mappings);
    }

    $form['mappings'] = [
      '#type' => 'container',
      '#prefix' => '<div id="mappings-wrapper">',
      '#suffix' => '</div>',
    ];

    for ($i = 0; $i < $num_mappings; $i++) {
      $mapping = $mappings[$i] ?? [];

      $form['mappings'][$i] = [
        '#type' => 'details',
        '#title' => $this->t('Mapping @num', ['@num' => $i + 1]),
        '#open' => TRUE,
      ];

      $form['mappings'][$i]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enable this mapping'),
        '#default_value' => $mapping['enabled'] ?? TRUE,
      ];

      // Source entity type selection.
      $form['mappings'][$i]['source_entity_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Source Entity Type'),
        '#options' => [
          'node' => $this->t('Content (Node)'),
          'taxonomy_term' => $this->t('Taxonomy Term'),
        ],
        '#default_value' => $mapping['source_entity_type'] ?? 'node',
        '#required' => TRUE,
        '#ajax' => [
          'callback' => '::updateBundleOptions',
          'wrapper' => 'mappings-wrapper',
          'event' => 'change',
        ],
      ];

      // Source bundle selection.
      $source_entity_type = $form_state->getValue(['mappings', $i, 'source_entity_type'])
        ?? $mapping['source_entity_type'] ?? 'node';

      $source_bundles = $this->getBundleOptions($source_entity_type);

      $form['mappings'][$i]['source_bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Source Bundle'),
        '#options' => $source_bundles,
        '#default_value' => $mapping['source_bundle'] ?? '',
        '#required' => TRUE,
        '#ajax' => [
          'callback' => '::updateFieldMappings',
          'wrapper' => 'mappings-wrapper',
          'event' => 'change',
        ],
      ];

      // Target entity type selection.
      $form['mappings'][$i]['target_entity_type'] = [
        '#type' => 'select',
        '#title' => $this->t('Target Entity Type'),
        '#options' => [
          'node' => $this->t('Content (Node)'),
          'taxonomy_term' => $this->t('Taxonomy Term'),
        ],
        '#default_value' => $mapping['target_entity_type'] ?? 'node',
        '#required' => TRUE,
        '#ajax' => [
          'callback' => '::updateBundleOptions',
          'wrapper' => 'mappings-wrapper',
          'event' => 'change',
        ],
      ];

      // Target bundle selection.
      $target_entity_type = $form_state->getValue(['mappings', $i, 'target_entity_type'])
        ?? $mapping['target_entity_type'] ?? 'node';

      $target_bundles = $this->getBundleOptions($target_entity_type);

      $form['mappings'][$i]['target_bundle'] = [
        '#type' => 'select',
        '#title' => $this->t('Target Bundle'),
        '#options' => $target_bundles,
        '#default_value' => $mapping['target_bundle'] ?? '',
        '#required' => TRUE,
        '#ajax' => [
          'callback' => '::updateFieldMappings',
          'wrapper' => 'mappings-wrapper',
          'event' => 'change',
        ],
      ];

      // Multilingual options.
      $form['mappings'][$i]['clone_all_translations'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Clone all translations'),
        '#description' => $this->t('If enabled, all available translations of the source entity will be cloned to the target entity. Both source and target must be translatable.'),
        '#default_value' => $mapping['clone_all_translations'] ?? FALSE,
      ];

      // Field mappings.
      $source_bundle = $form_state->getValue(['mappings', $i, 'source_bundle'])
        ?? $mapping['source_bundle'] ?? NULL;
      $target_bundle = $form_state->getValue(['mappings', $i, 'target_bundle'])
        ?? $mapping['target_bundle'] ?? NULL;

      if ($source_bundle && $target_bundle) {
        $form['mappings'][$i]['field_mappings'] = [
          '#type' => 'details',
          '#title' => $this->t('Field Mappings'),
          '#open' => TRUE,
        ];

        $source_fields = $this->getFieldOptions($source_entity_type, $source_bundle);
        $target_fields = $this->getFieldOptions($target_entity_type, $target_bundle);

        // Add "Do not map" option.
        $target_fields = ['' => $this->t('- Do not map -')] + $target_fields;

        $field_mappings = $mapping['field_mappings'] ?? [];

        foreach ($source_fields as $source_field_name => $source_field_label) {
          $form['mappings'][$i]['field_mappings'][$source_field_name] = [
            '#type' => 'select',
            '#title' => $source_field_label,
            '#options' => $target_fields,
            '#default_value' => $field_mappings[$source_field_name] ?? '',
            '#description' => $this->t('Source field: @field', ['@field' => $source_field_name]),
          ];
        }
      }

      $form['mappings'][$i]['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove this mapping'),
        '#submit' => ['::removeMapping'],
        '#ajax' => [
          'callback' => '::updateBundleOptions',
          'wrapper' => 'mappings-wrapper',
        ],
        '#name' => 'remove_' . $i,
        '#mapping_index' => $i,
        '#limit_validation_errors' => [],
      ];
    }

    $form['add_mapping'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another mapping'),
      '#submit' => ['::addMapping'],
      '#ajax' => [
        'callback' => '::updateBundleOptions',
        'wrapper' => 'mappings-wrapper',
      ],
      '#limit_validation_errors' => [],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * AJAX callback to update bundle options.
   */
  public function updateBundleOptions(array &$form, FormStateInterface $form_state) {
    return $form['mappings'];
  }

  /**
   * AJAX callback to update field mappings.
   */
  public function updateFieldMappings(array &$form, FormStateInterface $form_state) {
    return $form['mappings'];
  }

  /**
   * Submit handler to add a new mapping.
   */
  public function addMapping(array &$form, FormStateInterface $form_state) {
    $num_mappings = $form_state->get('num_mappings');
    $form_state->set('num_mappings', $num_mappings + 1);
    $form_state->setRebuild();
  }

  /**
   * Submit handler to remove a mapping.
   */
  public function removeMapping(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $mapping_index = $triggering_element['#mapping_index'];

    $mappings = $form_state->getValue('mappings');
    unset($mappings[$mapping_index]);
    $mappings = array_values($mappings);

    $form_state->setValue('mappings', $mappings);
    $form_state->set('num_mappings', count($mappings));
    $form_state->setRebuild();
  }

  /**
   * Get bundle options for an entity type.
   */
  protected function getBundleOptions($entity_type_id) {
    $options = [];

    if ($entity_type_id === 'node') {
      $bundles = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
      foreach ($bundles as $bundle) {
        $options[$bundle->id()] = $bundle->label();
      }
    }
    elseif ($entity_type_id === 'taxonomy_term') {
      $bundles = $this->entityTypeManager->getStorage('taxonomy_vocabulary')->loadMultiple();
      foreach ($bundles as $bundle) {
        $options[$bundle->id()] = $bundle->label();
      }
    }

    return $options;
  }

  /**
   * Get field options for an entity bundle.
   */
  protected function getFieldOptions($entity_type_id, $bundle) {
    $options = [];

    $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);

    foreach ($field_definitions as $field_name => $field_definition) {
      // Skip base fields that shouldn't be mapped.
      if (in_array($field_name, ['uid', 'created', 'changed', 'uuid', 'revision_uid', 'revision_timestamp'])) {
        continue;
      }

      $options[$field_name] = $field_definition->getLabel() . ' (' . $field_definition->getType() . ')';
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $mappings = $form_state->getValue('mappings') ?? [];

    // Clean up mappings - remove empty ones.
    $mappings = array_filter($mappings, function ($mapping) {
      return !empty($mapping['source_bundle']) && !empty($mapping['target_bundle']);
    });

    // Re-index array.
    $mappings = array_values($mappings);

    $this->config('j_navi_entity_clone.settings')
      ->set('mappings', $mappings)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
