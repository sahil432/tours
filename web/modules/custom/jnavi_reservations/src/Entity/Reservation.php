<?php

declare(strict_types=1);

namespace Drupal\jnavi_reservations\Entity;

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
use Drupal\jnavi_reservations\Form\ReservationForm;
use Drupal\jnavi_reservations\ReservationAccessControlHandler;
use Drupal\jnavi_reservations\ReservationInterface;
use Drupal\jnavi_reservations\ReservationListBuilder;
use Drupal\user\EntityOwnerTrait;
use Drupal\views\EntityViewsData;

/**
 * Defines the reservation entity class.
 */
#[ContentEntityType(
  id: 'jnavi_reservation',
  label: new TranslatableMarkup('Reservation'),
  label_collection: new TranslatableMarkup('Reservations'),
  label_singular: new TranslatableMarkup('reservation'),
  label_plural: new TranslatableMarkup('reservations'),
  entity_keys: [
    'id' => 'id',
    'revision' => 'revision_id',
    'langcode' => 'langcode',
    'bundle' => 'bundle',
    'label' => 'id',
    'owner' => 'uid',
    'published' => 'status',
    'uuid' => 'uuid',
    'name' => 'name',
  ],
  handlers: [
    'view_builder' => 'Drupal\Core\Entity\EntityViewBuilder',
    'list_builder' => ReservationListBuilder::class,
    'views_data' => EntityViewsData::class,
    'access' => ReservationAccessControlHandler::class,
    'form' => [
      'add' => ReservationForm::class,
      'edit' => ReservationForm::class,
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
    'view' => '/jnavi-reservation/{jnavi_reservation}',
    'collection' => '/admin/content/jnavi-reservation',
    'add-form' => '/jnavi-reservation/add/{jnavi_reservation_type}',
    'add-page' => '/jnavi-reservation/add',
    'canonical' => '/jnavi-reservation/{jnavi_reservation}',
    'edit-form' => '/jnavi-reservation/{jnavi_reservation}/edit',
    'delete-form' => '/jnavi-reservation/{jnavi_reservation}/delete',
    'delete-multiple-form' => '/admin/content/jnavi-reservation/delete-multiple',
    'revision' => '/jnavi-reservation/{jnavi_reservation}/revision/{jnavi_reservation_revision}/view',
    'revision-delete-form' => '/jnavi-reservation/{jnavi_reservation}/revision/{jnavi_reservation_revision}/delete',
    'revision-revert-form' => '/jnavi-reservation/{jnavi_reservation}/revision/{jnavi_reservation_revision}/revert',
    'version-history' => '/jnavi-reservation/{jnavi_reservation}/revisions',
  ],
  admin_permission: 'administer jnavi_reservation types',
  collection_permission: 'access jnavi_reservation overview',
  bundle_entity_type: 'jnavi_reservation_type',
  bundle_label: new TranslatableMarkup('Reservation type'),
  base_table: 'jnavi_reservation',
  data_table: 'jnavi_reservation_field_data',
  revision_table: 'jnavi_reservation_revision',
  revision_data_table: 'jnavi_reservation_field_revision',
  translatable: TRUE,
  show_revision_ui: TRUE,
  label_count: [
    'singular' => '@count reservations',
    'plural' => '@count reservations',
  ],
  field_ui_base_route: 'entity.jnavi_reservation_type.edit_form',
  revision_metadata_keys: [
    'revision_user' => 'revision_uid',
    'revision_created' => 'revision_timestamp',
    'revision_log_message' => 'revision_log',
  ],
)]
class Reservation extends EditorialContentEntityBase implements ReservationInterface {

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
    return $this->get('name')->value ?? $this->id();
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
      ->setDescription(t('The time that the reservation was created.'))
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
      ->setDescription(t('The time that the reservation was last edited.'));

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the reservation.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'label' => 'above',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
