<?php

declare(strict_types=1);

namespace Drupal\jnavi_reservations\Hook;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for Reservation entity.
 */
class ReservationHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function reservationTheme(): array {
    return [
      'jnavi_reservation' => ['render element' => 'elements'],
    ];
  }

  /**
   * Implements hook_gin_content_form_routes().
   */
  #[Hook('gin_content_form_routes')]
  public function ginContentFormRoutes(): array {
    return [
      'entity.jnavi_reservation.add_form',
      'entity.jnavi_reservation.edit_form',
      'entity.jnavi_reservation.content_translation_add',
    ];
  }

  /**
   * Implements hook_entity_predelete().
   *
   * Deletes all reservations when a reservation type is deleted.
   */
  #[Hook('entity_predelete')]
  public function entityPredelete(EntityInterface $entity): void {
    if ($entity->getEntityTypeId() === 'jnavi_reservation_type') {
      $reservation_ids = \Drupal::entityQuery('jnavi_reservation')
        ->condition('bundle', $entity->id())
        ->accessCheck(FALSE)
        ->execute();

      if (!empty($reservation_ids)) {
        $storage = \Drupal::entityTypeManager()->getStorage('jnavi_reservation');
        $reservations = $storage->loadMultiple($reservation_ids);
        $storage->delete($reservations);
      }
    }
  }

}
