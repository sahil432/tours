<?php

declare(strict_types=1);

namespace Drupal\jnavi_persons;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the person entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class PersonAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    return match($operation) {
      'view' => AccessResult::allowedIfHasPermission($account, 'view jnavi_person'),
      'update' => AccessResult::allowedIfHasPermission($account, 'edit jnavi_person'),
      'delete' => AccessResult::allowedIfHasPermission($account, 'delete jnavi_person'),
      'delete revision' => AccessResult::allowedIfHasPermission($account, 'delete jnavi_person revision'),
      'view all revisions', 'view revision' => AccessResult::allowedIfHasPermissions($account, ['view jnavi_person revision', 'view jnavi_person']),
      'revert' => AccessResult::allowedIfHasPermissions($account, ['revert jnavi_person revision', 'edit jnavi_person']),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['create jnavi_person', 'administer jnavi_person types'], 'OR');
  }

}
