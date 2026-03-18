<?php

declare(strict_types=1);

namespace Drupal\jnavi_reservations;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the reservation entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class ReservationAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return match($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view jnavi_reservation'),
      'update' => AccessResult::allowedIfHasPermission($account, 'edit jnavi_reservation'),
      'delete' => AccessResult::allowedIfHasPermission($account, 'delete jnavi_reservation'),
      'delete revision' => AccessResult::allowedIfHasPermission($account, 'delete jnavi_reservation revision'),
      'view all revisions', 'view revision' => AccessResult::allowedIfHasPermissions($account, ['view jnavi_reservation revision', 'view jnavi_reservation']),
      'revert' => AccessResult::allowedIfHasPermissions($account, ['revert jnavi_reservation revision', 'edit jnavi_reservation']),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['create jnavi_reservation', 'administer jnavi_reservation types'], 'OR');
  }

}
