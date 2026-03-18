<?php

declare(strict_types=1);

namespace Drupal\jnavi_reservations;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a reservation entity type.
 */
interface ReservationInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
