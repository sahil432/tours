<?php

namespace Drupal\entity_clone_mapper\Service;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Psr\Log\LoggerInterface;

/**
 * Service for cloning entities with field mapping.
 */
class EntityCloneService {

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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs an EntityCloneService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    AccountProxyInterface $current_user,
    LoggerInterface $logger,
    LanguageManagerInterface $language_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->currentUser = $current_user;
    $this->logger = $logger;
    $this->languageManager = $language_manager;
  }

  /**
   * Clone an entity based on mapping configuration.
   *
   * @param \Drupal\Core\Entity\EntityInterface $source_entity
   *   The source entity to clone.
   * @param array $mapping
   *   The mapping configuration.
   * @param string|null $language_code
   *   Optional language code. If provided, clones specific translation.
   *   If NULL, clones current language or all translations based on config.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The cloned entity.
   *
   * @throws \Exception
   */
  public function cloneEntity(EntityInterface $source_entity, array $mapping, $language_code = NULL) {
    $target_entity_type = $mapping['target_entity_type'];
    $target_bundle = $mapping['target_bundle'];
    $field_mappings = $mapping['field_mappings'] ?? [];
    $clone_all_translations = $mapping['clone_all_translations'] ?? FALSE;

    // Determine which language to use as base.
    if ($language_code) {
      $base_language = $language_code;
    }
    else {
      $base_language = $source_entity->language()->getId();
    }

    // Check if source entity is translatable and has the requested language.
    if ($source_entity->isTranslatable()) {
      if ($source_entity->hasTranslation($base_language)) {
        $source_entity = $source_entity->getTranslation($base_language);
      }
    }

    // Create new entity.
    $storage = $this->entityTypeManager->getStorage($target_entity_type);
    
    $values = [];
    
    // Set bundle field and language.
    if ($target_entity_type === 'node') {
      $values['type'] = $target_bundle;
      $values['status'] = 0; // Unpublished.
      $values['uid'] = $this->currentUser->id();
      $values['langcode'] = $base_language;
    }
    elseif ($target_entity_type === 'taxonomy_term') {
      $values['vid'] = $target_bundle;
      $values['status'] = 0; // Unpublished.
      $values['uid'] = $this->currentUser->id();
      $values['langcode'] = $base_language;
    }

    $target_entity = $storage->create($values);

    // Map fields for the base language.
    $this->mapFieldsForLanguage($source_entity, $target_entity, $field_mappings, $base_language);

    // Save the base entity.
    $target_entity->save();

    // Handle additional translations if requested and source is translatable.
    if ($clone_all_translations && $source_entity->isTranslatable() && $target_entity->isTranslatable()) {
      $this->cloneTranslations($source_entity, $target_entity, $field_mappings, $base_language);
    }

    $this->logger->info('Cloned @source_type:@source_bundle (@source_id) to @target_type:@target_bundle (@target_id) [Language: @language]', [
      '@source_type' => $source_entity->getEntityTypeId(),
      '@source_bundle' => $source_entity->bundle(),
      '@source_id' => $source_entity->id(),
      '@target_type' => $target_entity->getEntityTypeId(),
      '@target_bundle' => $target_entity->bundle(),
      '@target_id' => $target_entity->id(),
      '@language' => $base_language,
    ]);

    return $target_entity;
  }

  /**
   * Clone translations from source to target entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $source_entity
   *   The source entity.
   * @param \Drupal\Core\Entity\EntityInterface $target_entity
   *   The target entity.
   * @param array $field_mappings
   *   Field mappings configuration.
   * @param string $base_language
   *   The base language code to skip.
   */
  protected function cloneTranslations(EntityInterface $source_entity, EntityInterface $target_entity, array $field_mappings, $base_language) {
    $translation_languages = $source_entity->getTranslationLanguages();
    
    foreach ($translation_languages as $langcode => $language) {
      // Skip the base language as it's already cloned.
      if ($langcode === $base_language) {
        continue;
      }
      
      try {
        // Get the source translation.
        $source_translation = $source_entity->getTranslation($langcode);
        
        // Add translation to target entity.
        if (!$target_entity->hasTranslation($langcode)) {
          $target_translation = $target_entity->addTranslation($langcode);
          
          // Map fields for this translation.
          $this->mapFieldsForLanguage($source_translation, $target_translation, $field_mappings, $langcode);
          
          // Set translation metadata.
          if ($target_translation->hasField('content_translation_source')) {
            $target_translation->set('content_translation_source', $base_language);
          }
          if ($target_translation->hasField('content_translation_outdated')) {
            $target_translation->set('content_translation_outdated', FALSE);
          }
          if ($target_translation->hasField('content_translation_status')) {
            $target_translation->set('content_translation_status', 0); // Unpublished.
          }
          
          $target_translation->save();
          
          $this->logger->info('Cloned translation @language for entity @id', [
            '@language' => $langcode,
            '@id' => $target_entity->id(),
          ]);
        }
      }
      catch (\Exception $e) {
        $this->logger->error('Error cloning translation @language: @message', [
          '@language' => $langcode,
          '@message' => $e->getMessage(),
        ]);
      }
    }
  }

  /**
   * Map fields for a specific language.
   *
   * @param \Drupal\Core\Entity\EntityInterface $source_entity
   *   The source entity (or translation).
   * @param \Drupal\Core\Entity\EntityInterface $target_entity
   *   The target entity (or translation).
   * @param array $field_mappings
   *   Field mappings configuration.
   * @param string $langcode
   *   The language code being processed.
   */
  protected function mapFieldsForLanguage(EntityInterface $source_entity, EntityInterface $target_entity, array $field_mappings, $langcode) {
    foreach ($field_mappings as $source_field_name => $target_field_name) {
      // Skip if no target field mapped.
      if (empty($target_field_name)) {
        continue;
      }

      // Skip if source field doesn't exist or is empty.
      if (!$source_entity->hasField($source_field_name)) {
        continue;
      }

      $source_field = $source_entity->get($source_field_name);
      
      if ($source_field->isEmpty()) {
        continue;
      }

      // Skip if target field doesn't exist.
      if (!$target_entity->hasField($target_field_name)) {
        $this->logger->warning('Target field @field does not exist on @bundle', [
          '@field' => $target_field_name,
          '@bundle' => $target_entity->bundle(),
        ]);
        continue;
      }

      // Skip if field is not translatable and we're not on base language.
      $target_field_definition = $target_entity->getFieldDefinition($target_field_name);
      if ($langcode !== $target_entity->language()->getId() && !$target_field_definition->isTranslatable()) {
        continue;
      }

      try {
        $this->mapField($source_field, $target_entity, $target_field_name, $langcode);
      }
      catch (\Exception $e) {
        $this->logger->error('Error mapping field @source to @target: @message', [
          '@source' => $source_field_name,
          '@target' => $target_field_name,
          '@message' => $e->getMessage(),
        ]);
      }
    }
  }

  /**
   * Map a field from source to target.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $source_field
   *   The source field.
   * @param \Drupal\Core\Entity\EntityInterface $target_entity
   *   The target entity.
   * @param string $target_field_name
   *   The target field name.
   * @param string $langcode
   *   The language code being processed.
   */
  protected function mapField(FieldItemListInterface $source_field, EntityInterface $target_entity, $target_field_name, $langcode = NULL) {
    $source_field_definition = $source_field->getFieldDefinition();
    $target_field_definition = $target_entity->get($target_field_name)->getFieldDefinition();
    
    $source_type = $source_field_definition->getType();
    $target_type = $target_field_definition->getType();

    // Handle entity reference fields (including paragraphs and taxonomy).
    if (in_array($source_type, ['entity_reference', 'entity_reference_revisions'])) {
      $this->mapEntityReferenceField($source_field, $target_entity, $target_field_name, $langcode);
      return;
    }

    // Handle simple field types - direct copy if types match.
    if ($source_type === $target_type) {
      $values = [];
      foreach ($source_field as $delta => $item) {
        $values[$delta] = $item->getValue();
      }
      $target_entity->set($target_field_name, $values);
      return;
    }

    // Handle text fields (try to extract main value).
    if (in_array($target_type, ['string', 'string_long', 'text', 'text_long', 'text_with_summary'])) {
      $values = [];
      foreach ($source_field as $delta => $item) {
        $item_value = $item->getValue();
        
        // Extract value field.
        if (isset($item_value['value'])) {
          $values[$delta] = ['value' => $item_value['value']];
          
          // Add format if target supports it.
          if (in_array($target_type, ['text', 'text_long', 'text_with_summary']) && isset($item_value['format'])) {
            $values[$delta]['format'] = $item_value['format'];
          }
        }
        elseif (is_string($item_value)) {
          $values[$delta] = ['value' => $item_value];
        }
      }
      
      if (!empty($values)) {
        $target_entity->set($target_field_name, $values);
      }
      return;
    }

    // For other field types, try direct assignment.
    try {
      $target_entity->set($target_field_name, $source_field->getValue());
    }
    catch (\Exception $e) {
      $this->logger->warning('Could not map field @source (@source_type) to @target (@target_type): @message', [
        '@source' => $source_field->getName(),
        '@source_type' => $source_type,
        '@target' => $target_field_name,
        '@target_type' => $target_type,
        '@message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Map entity reference fields (including paragraphs and taxonomy).
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $source_field
   *   The source field.
   * @param \Drupal\Core\Entity\EntityInterface $target_entity
   *   The target entity.
   * @param string $target_field_name
   *   The target field name.
   * @param string $langcode
   *   The language code being processed.
   */
  protected function mapEntityReferenceField(FieldItemListInterface $source_field, EntityInterface $target_entity, $target_field_name, $langcode = NULL) {
    $source_field_definition = $source_field->getFieldDefinition();
    $target_field_definition = $target_entity->get($target_field_name)->getFieldDefinition();
    
    $source_settings = $source_field_definition->getSettings();
    $target_settings = $target_field_definition->getSettings();
    
    $source_target_type = $source_settings['target_type'] ?? NULL;
    $target_target_type = $target_settings['target_type'] ?? NULL;

    $values = [];

    foreach ($source_field as $delta => $item) {
      $referenced_entity = $item->entity;
      
      if (!$referenced_entity) {
        continue;
      }

      // Handle paragraphs - clone them with language support.
      if ($source_target_type === 'paragraph' && $target_target_type === 'paragraph') {
        // If we're processing a translation and paragraph is translatable,
        // get the translated version.
        if ($langcode && $referenced_entity->isTranslatable() && $referenced_entity->hasTranslation($langcode)) {
          $referenced_entity = $referenced_entity->getTranslation($langcode);
        }
        
        $cloned_paragraph = $this->cloneParagraph($referenced_entity, $langcode);
        $values[$delta] = ['target_id' => $cloned_paragraph->id(), 'target_revision_id' => $cloned_paragraph->getRevisionId()];
      }
      // Handle taxonomy terms - reference the same terms.
      elseif ($source_target_type === 'taxonomy_term' && $target_target_type === 'taxonomy_term') {
        // Check if vocabularies match or if target accepts any vocabulary.
        $source_bundles = $source_settings['handler_settings']['target_bundles'] ?? [];
        $target_bundles = $target_settings['handler_settings']['target_bundles'] ?? [];
        
        $term_vid = $referenced_entity->bundle();
        
        // If target accepts this vocabulary or accepts all vocabularies, add it.
        if (empty($target_bundles) || isset($target_bundles[$term_vid])) {
          $values[$delta] = ['target_id' => $referenced_entity->id()];
        }
      }
      // Handle other entity references - reference the same entity if types match.
      elseif ($source_target_type === $target_target_type) {
        $values[$delta] = ['target_id' => $referenced_entity->id()];
      }
    }

    if (!empty($values)) {
      $target_entity->set($target_field_name, $values);
    }
  }

  /**
   * Clone a paragraph entity recursively.
   *
   * @param \Drupal\paragraphs\Entity\Paragraph $paragraph
   *   The paragraph to clone.
   * @param string $langcode
   *   Optional language code for translation handling.
   *
   * @return \Drupal\paragraphs\Entity\Paragraph
   *   The cloned paragraph.
   */
  protected function cloneParagraph($paragraph, $langcode = NULL) {
    // Create a duplicate of the paragraph.
    $duplicate = $paragraph->createDuplicate();
    
    // Set language if specified.
    if ($langcode) {
      $duplicate->set('langcode', $langcode);
    }
    
    // Handle nested paragraphs.
    foreach ($duplicate->getFields() as $field_name => $field) {
      $field_definition = $field->getFieldDefinition();
      $field_type = $field_definition->getType();
      
      if ($field_type === 'entity_reference_revisions') {
        $settings = $field_definition->getSettings();
        $target_type = $settings['target_type'] ?? NULL;
        
        if ($target_type === 'paragraph') {
          $cloned_values = [];
          
          foreach ($field as $delta => $item) {
            $nested_paragraph = $item->entity;
            if ($nested_paragraph) {
              // Get the correct language version of the nested paragraph.
              if ($langcode && $nested_paragraph->isTranslatable() && $nested_paragraph->hasTranslation($langcode)) {
                $nested_paragraph = $nested_paragraph->getTranslation($langcode);
              }
              
              $cloned_nested = $this->cloneParagraph($nested_paragraph, $langcode);
              $cloned_values[$delta] = [
                'target_id' => $cloned_nested->id(),
                'target_revision_id' => $cloned_nested->getRevisionId(),
              ];
            }
          }
          
          if (!empty($cloned_values)) {
            $duplicate->set($field_name, $cloned_values);
          }
        }
      }
    }
    
    $duplicate->save();
    
    return $duplicate;
  }

}
