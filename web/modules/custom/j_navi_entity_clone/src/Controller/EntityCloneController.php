<?php

namespace Drupal\j_navi_entity_clone\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\j_navi_entity_clone\Service\EntityCloneService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Controller for cloning entities.
 */
class EntityCloneController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity clone service.
   *
   * @var \Drupal\j_navi_entity_clone\Service\EntityCloneService
   */
  protected $cloneService;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityCloneService $clone_service) {
    $this->entityTypeManager = $entity_type_manager;
    $this->cloneService = $clone_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('j_navi_entity_clone.clone_service')
    );
  }

  /**
   * Clone an entity to another type.
   *
   * @param string $entity_type
   *   The entity type ID.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to clone.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Render array or redirect.
   */
  public function cloneEntity($entity_type, EntityInterface $entity) {
    // Get available mappings for this entity.
    $config = $this->config('j_navi_entity_clone.settings');
    $mappings = $config->get('mappings') ?? [];
    
    $available_mappings = [];
    foreach ($mappings as $index => $mapping) {
      if ($mapping['enabled'] && 
          $mapping['source_entity_type'] === $entity_type && 
          $mapping['source_bundle'] === $entity->bundle()) {
        $available_mappings[$index] = $mapping;
      }
    }
    
    if (empty($available_mappings)) {
      $this->messenger()->addError($this->t('No clone mappings are configured for this entity type.'));
      return $this->redirect('entity.' . $entity_type . '.canonical', [$entity_type => $entity->id()]);
    }
    
    // If only one mapping, check for multilingual handling.
    if (count($available_mappings) === 1) {
      $mapping = reset($available_mappings);
      
      // If entity is translatable and has multiple translations, show language selection.
      if ($entity->isTranslatable() && count($entity->getTranslationLanguages()) > 1) {
        return $this->formBuilder()->getForm(
          'Drupal\j_navi_entity_clone\Form\CloneLanguageSelectionForm',
          $entity,
          $mapping
        );
      }
      
      // Otherwise, clone directly.
      return $this->performClone($entity, $mapping);
    }
    
    // Multiple mappings - show selection form.
    return $this->formBuilder()->getForm(
      'Drupal\j_navi_entity_clone\Form\CloneSelectionForm',
      $entity,
      $available_mappings
    );
  }

  /**
   * Perform the clone operation.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The source entity.
   * @param array $mapping
   *   The mapping configuration.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response.
   */
  protected function performClone(EntityInterface $entity, array $mapping) {
    try {
      $cloned_entity = $this->cloneService->cloneEntity($entity, $mapping);
      
      $this->messenger()->addStatus($this->t('Entity cloned successfully. The new @type has been created in unpublished state.', [
        '@type' => $cloned_entity->getEntityType()->getLabel(),
      ]));
      
      // Redirect to the new entity's edit form.
      return new RedirectResponse(
        Url::fromRoute('entity.' . $cloned_entity->getEntityTypeId() . '.edit_form', [
          $cloned_entity->getEntityTypeId() => $cloned_entity->id(),
        ])->toString()
      );
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t('Error cloning entity: @message', [
        '@message' => $e->getMessage(),
      ]));
      
      return $this->redirect('entity.' . $entity->getEntityTypeId() . '.canonical', [
        $entity->getEntityTypeId() => $entity->id(),
      ]);
    }
  }

}
