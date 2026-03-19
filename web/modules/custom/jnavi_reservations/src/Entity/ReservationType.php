<?php

declare(strict_types=1);

namespace Drupal\jnavi_reservations\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;
use Drupal\Core\Entity\Attribute\ConfigEntityType;
use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\jnavi_reservations\Form\ReservationTypeForm;
use Drupal\jnavi_reservations\ReservationTypeListBuilder;

/**
 * Defines the Reservation type configuration entity.
 */
#[ConfigEntityType(
  id: 'jnavi_reservation_type',
  label: new TranslatableMarkup('Reservation type'),
  label_collection: new TranslatableMarkup('Reservation types'),
  label_singular: new TranslatableMarkup('reservation type'),
  label_plural: new TranslatableMarkup('reservations types'),
  config_prefix: 'jnavi_reservation_type',
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => ReservationTypeListBuilder::class,
    'route_provider' => [
      'html' => AdminHtmlRouteProvider::class,
    ],
    'form' => [
      'add' => ReservationTypeForm::class,
      'edit' => ReservationTypeForm::class,
      'delete' => EntityDeleteForm::class,
    ],
  ],
  links: [
    'add-form' => '/admin/structure/jnavi_reservation_types/add',
    'edit-form' => '/admin/structure/jnavi_reservation_types/manage/{jnavi_reservation_type}',
    'delete-form' => '/admin/structure/jnavi_reservation_types/manage/{jnavi_reservation_type}/delete',
    'collection' => '/admin/structure/jnavi_reservation_types',
  ],
  admin_permission: 'administer jnavi_reservation types',
  bundle_of: 'jnavi_reservation',
  label_count: [
    'singular' => '@count reservation type',
    'plural' => '@count reservations types',
  ],
  config_export: [
    'id',
    'label',
    'uuid',
  ],
)]
final class ReservationType extends ConfigEntityBundleBase {

  /**
   * The machine name of this reservation type.
   */
  protected string $id;

  /**
   * The human-readable name of the reservation type.
   */
  protected string $label;

}
