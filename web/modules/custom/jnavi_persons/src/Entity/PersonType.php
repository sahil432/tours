<?php

declare(strict_types=1);

namespace Drupal\jnavi_persons\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\jnavi_persons\Form\PersonTypeForm;
use Drupal\jnavi_persons\PersonTypeListBuilder;

/**
 * Defines the Person type configuration entity.
 */
#[ConfigEntityType(
  id: 'jnavi_person_type',
  label: new TranslatableMarkup('Person type'),
  label_collection: new TranslatableMarkup('Person types'),
  label_singular: new TranslatableMarkup('person type'),
  label_plural: new TranslatableMarkup('persons types'),
  config_prefix: 'jnavi_person_type',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => PersonTypeListBuilder::class,
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
    'form' => [
      'add' => PersonTypeForm::class,
      'edit' => PersonTypeForm::class,
      'delete' => EntityDeleteForm::class,
    ],
  ],
  links: [
    'add-form' => '/admin/structure/jnavi_person_types/add',
    'edit-form' => '/admin/structure/jnavi_person_types/manage/{jnavi_person_type}',
    'delete-form' => '/admin/structure/jnavi_person_types/manage/{jnavi_person_type}/delete',
    'collection' => '/admin/structure/jnavi_person_types',
  ],
  admin_permission: 'administer jnavi_person types',
  bundle_of: 'jnavi_person',
  label_count: [
    'singular' => '@count person type',
    'plural' => '@count persons types',
  ],
  config_export: [
    'id',
    'label',
    'uuid',
  ],
)]
final class PersonType extends ConfigEntityBundleBase {

  /**
   * The machine name of this person type.
   */
  protected string $id;

  /**
   * The human-readable name of the person type.
   */
  protected string $label;

}
