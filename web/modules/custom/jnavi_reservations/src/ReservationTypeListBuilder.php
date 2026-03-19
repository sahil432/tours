<?php

declare(strict_types=1);

namespace Drupal\jnavi_reservations;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of reservation type entities.
 *
 * @see \Drupal\jnavi_reservations\Entity\ReservationType
 */
final class ReservationTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Label');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['label'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build = parent::render();

    $build['table']['#empty'] = $this->t(
      'No reservation types available. <a href=":link">Add reservation type</a>.',
      [':link' => Url::fromRoute('entity.jnavi_reservation_type.add_form')->toString()],
    );

    return $build;
  }

}
