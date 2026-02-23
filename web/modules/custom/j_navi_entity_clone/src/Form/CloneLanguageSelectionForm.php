<?php

namespace Drupal\j_navi_entity_clone\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\j_navi_entity_clone\Service\EntityCloneService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Form for selecting which language to clone.
 */
class CloneLanguageSelectionForm extends FormBase {

  /**
   * The entity clone service.
   *
   * @var \Drupal\j_navi_entity_clone\Service\EntityCloneService
   */
  protected $cloneService;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The entity being cloned.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The selected mapping.
   *
   * @var array
   */
  protected $mapping;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityCloneService $clone_service, LanguageManagerInterface $language_manager) {
    $this->cloneService = $clone_service;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('j_navi_entity_clone.clone_service'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'j_navi_entity_clone_language_selection_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, EntityInterface $entity = NULL, array $mapping = []) {
    $this->entity = $entity;
    $this->mapping = $mapping;

    // Check if entity is translatable.
    if (!$entity->isTranslatable()) {
      $this->messenger()->addWarning($this->t('This entity is not translatable. Only the default language will be cloned.'));
      // Redirect directly to clone.
      return $this->redirect('j_navi_entity_clone.clone_entity', [
        'entity_type' => $entity->getEntityTypeId(),
        'entity' => $entity->id(),
      ]);
    }

    $form['description'] = [
      '#markup' => '<p>' . $this->t('Select which language to clone, or clone all translations.') . '</p>',
    ];

    // Get available translations.
    $languages = $entity->getTranslationLanguages();
    $options = [];
    
    foreach ($languages as $langcode => $language) {
      $options[$langcode] = $language->getName();
    }

    // Check if clone_all_translations is enabled in mapping.
    $clone_all_enabled = $mapping['clone_all_translations'] ?? FALSE;

    if ($clone_all_enabled && count($options) > 1) {
      $form['clone_mode'] = [
        '#type' => 'radios',
        '#title' => $this->t('Clone mode'),
        '#options' => [
          'single' => $this->t('Clone a single language'),
          'all' => $this->t('Clone all translations'),
        ],
        '#default_value' => 'single',
        '#required' => TRUE,
      ];

      $form['language'] = [
        '#type' => 'select',
        '#title' => $this->t('Language to clone'),
        '#options' => $options,
        '#default_value' => $entity->language()->getId(),
        '#states' => [
          'visible' => [
            ':input[name="clone_mode"]' => ['value' => 'single'],
          ],
          'required' => [
            ':input[name="clone_mode"]' => ['value' => 'single'],
          ],
        ],
      ];

      $form['all_translations_info'] = [
        '#type' => 'item',
        '#markup' => '<div class="messages messages--status">' . 
          $this->t('All @count translations will be cloned. Each translation will be created in unpublished state.', [
            '@count' => count($options),
          ]) . '</div>',
        '#states' => [
          'visible' => [
            ':input[name="clone_mode"]' => ['value' => 'all'],
          ],
        ],
      ];
    }
    else {
      $form['language'] = [
        '#type' => 'select',
        '#title' => $this->t('Language to clone'),
        '#options' => $options,
        '#default_value' => $entity->language()->getId(),
        '#required' => TRUE,
        '#description' => $this->t('Select which language version to clone.'),
      ];

      if (!$clone_all_enabled && count($options) > 1) {
        $form['info'] = [
          '#type' => 'item',
          '#markup' => '<div class="messages messages--warning">' . 
            $this->t('To clone all translations at once, enable "Clone all translations" in the mapping configuration.') . 
            '</div>',
        ];
      }
    }

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
    $clone_mode = $form_state->getValue('clone_mode');
    $language = $form_state->getValue('language');
    
    // Determine if we should clone all translations.
    $clone_all = ($clone_mode === 'all');
    
    // Update mapping to reflect user's choice.
    if ($clone_all) {
      $this->mapping['clone_all_translations'] = TRUE;
      $language_code = NULL; // Will use entity's default language as base.
    }
    else {
      $this->mapping['clone_all_translations'] = FALSE;
      $language_code = $language;
    }

    try {
      $cloned_entity = $this->cloneService->cloneEntity($this->entity, $this->mapping, $language_code);
      
      if ($clone_all) {
        $this->messenger()->addStatus($this->t('Entity and all translations cloned successfully. The new @type and its translations have been created in unpublished state.', [
          '@type' => $cloned_entity->getEntityType()->getLabel(),
        ]));
      }
      else {
        $this->messenger()->addStatus($this->t('Entity cloned successfully (@language). The new @type has been created in unpublished state.', [
          '@type' => $cloned_entity->getEntityType()->getLabel(),
          '@language' => $this->languageManager->getLanguage($language_code)->getName(),
        ]));
      }
      
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
