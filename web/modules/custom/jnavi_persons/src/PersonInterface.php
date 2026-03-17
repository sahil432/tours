<?php

declare(strict_types=1);

namespace Drupal\jnavi_persons;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a person entity type.
 */
interface PersonInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
