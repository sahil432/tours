<?php

declare(strict_types=1);

namespace Drupal\jnavi_persons\Entity;

use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Form\DeleteMultipleForm;
use Drupal\Core\Entity\Form\RevisionDeleteForm;
use Drupal\Core\Entity\Form\RevisionRevertForm;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\Entity\Routing\RevisionHtmlRouteProvider;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\jnavi_persons\Form\PersonForm;
use Drupal\jnavi_persons\PersonAccessControlHandler;
use Drupal\jnavi_persons\PersonInterface;
use Drupal\jnavi_persons\PersonListBuilder;
use Drupal\user\EntityOwnerTrait;
use Drupal\views\EntityViewsData;

/**
 * Defines the person entity class.
 */
#[ContentEntityType(
  id: 'jnavi_person',
  label: new TranslatableMarkup('Person'),
  label_collection: new TranslatableMarkup('Persons'),
  label_singular: new TranslatableMarkup('person'),
  label_plural: new TranslatableMarkup('persons'),
  entity_keys: [
    'id' => 'id',
    'revision' => 'revision_id',
    'langcode' => 'langcode',
    'bundle' => 'bundle',
    'label' => 'id',
    'owner' => 'uid',
    'published' => 'status',
    'uuid' => 'uuid',
    'name' => 'name'
  ],
  handlers: [
    'view_builder' => 'Drupal\Core\Entity\EntityViewBuilder',
    'list_builder' => PersonListBuilder::class,
    'views_data' => EntityViewsData::class,
    'access' => PersonAccessControlHandler::class,
    'form' => [
      'add' => PersonForm::class,
      'edit' => PersonForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'delete-multiple-confirm' => DeleteMultipleForm::class,
      'revision-delete' => RevisionDeleteForm::class,
      'revision-revert' => RevisionRevertForm::class,
    ],
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
      'revision' => RevisionHtmlRouteProvider::class,
    ],
  ],
  links: [
    'view' => '/jnavi-person/{jnavi_person}',
    'collection' => '/admin/content/jnavi-person',
    'add-form' => '/jnavi-person/add/{jnavi_person_type}',
    'add-page' => '/jnavi-person/add',
    'canonical' => '/jnavi-person/{jnavi_person}',
    'edit-form' => '/jnavi-person/{jnavi_person}/edit',
    'delete-form' => '/jnavi-person/{jnavi_person}/delete',
    'delete-multiple-form' => '/admin/content/jnavi-person/delete-multiple',
    'revision' => '/jnavi-person/{jnavi_person}/revision/{jnavi_person_revision}/view',
    'revision-delete-form' => '/jnavi-person/{jnavi_person}/revision/{jnavi_person_revision}/delete',
    'revision-revert-form' => '/jnavi-person/{jnavi_person}/revision/{jnavi_person_revision}/revert',
    'version-history' => '/jnavi-person/{jnavi_person}/revisions',
  ],
  admin_permission: 'administer jnavi_person types',
  collection_permission: 'access jnavi_person overview',
  bundle_entity_type: 'jnavi_person_type',
  bundle_label: new TranslatableMarkup('Person type'),
  base_table: 'jnavi_person',
  data_table: 'jnavi_person_field_data',
  revision_table: 'jnavi_person_revision',
  revision_data_table: 'jnavi_person_field_revision',
  translatable: TRUE,
  show_revision_ui: TRUE,
  label_count: [
    'singular' => '@count persons',
    'plural' => '@count persons',
  ],
  field_ui_base_route: 'entity.jnavi_person_type.edit_form',
  revision_metadata_keys: [
    'revision_user' => 'revision_uid',
    'revision_created' => 'revision_timestamp',
    'revision_log_message' => 'revision_log',
  ],
)]
class Person extends EditorialContentEntityBase implements PersonInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    $name_array = $this->get('field_name')->getValue()[0] ?? [];
    return \Drupal::service('name.formatter')->format($name_array, 'format_id');
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->getTitle();
  }

  /**
   * {@inheritDoc}
   */
  public function label(): string {
    return $this->getTitle() ?? $this->id();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setRevisionable(TRUE)
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the person was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the person was last edited.'));

    return $fields;
  }

}
