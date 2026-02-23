<?php

namespace Drupal\j_navi_entity_clone\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\j_navi_entity_clone\Service\EntityCloneService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Form for selecting which mapping to use when cloning.
 */
class CloneSelectionForm extends FormBase {

  /**
   * The entity clone service.
   *
   * @var \Drupal\j_navi_entity_clone\Service\EntityCloneService
   */
  protected $cloneService;

  /**
   * The entity being cloned.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * Available mappings.
   *
   * @var array
   */
  protected $mappings;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityCloneService $clone_service) {
    $this->cloneService = $clone_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('j_navi_entity_clone.clone_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'j_navi_entity_clone_selection_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityInterface $entity = NULL, array $mappings = []) {
    $this->entity = $entity;
    $this->mappings = $mappings;

    $form['description'] = [
      '#markup' => '<p>' . $this->t('Multiple clone mappings are available. Please select the target type:') . '</p>',
    ];

    $options = [];
    foreach ($mappings as $index => $mapping) {
      $target_label = $mapping['target_entity_type'] === 'node' 
        ? $this->t('Content') 
        : $this->t('Taxonomy Term');
      
      $options[$index] = $this->t('@type: @bundle', [
        '@type' => $target_label,
        '@bundle' => $mapping['target_bundle'],
      ]);
    }

    $form['mapping'] = [
      '#type' => 'radios',
      '#title' => $this->t('Clone to'),
      '#options' => $options,
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clone'),
      '#button_type' => 'primary',
    ];

    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#url' => Url::fromRoute('entity.' . $entity->getEntityTypeId() . '.canonical', [
        $entity->getEntityTypeId() => $entity->id(),
      ]),
      '#attributes' => ['class' => ['button']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $mapping_index = $form_state->getValue('mapping');
    $mapping = $this->mappings[$mapping_index];

    try {
      $cloned_entity = $this->cloneService->cloneEntity($this->entity, $mapping);
      
      $this->messenger()->addStatus($this->t('Entity cloned successfully. The new @type has been created in unpublished state.', [
        '@type' => $cloned_entity->getEntityType()->getLabel(),
      ]));
      
      // Redirect to the new entity's edit form.
      $form_state->setRedirect('entity.' . $cloned_entity->getEntityTypeId() . '.edit_form', [
        $cloned_entity->getEntityTypeId() => $cloned_entity->id(),
      ]);
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error cloning entity: @message', [
        '@message' => $e->getMessage(),
      ]));
      
      $form_state->setRedirect('entity.' . $this->entity->getEntityTypeId() . '.canonical', [
        $this->entity->getEntityTypeId() => $this->entity->id(),
      ]);
    }
  }

}
