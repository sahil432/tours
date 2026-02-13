<?php

namespace Drupal\itinerary_helper\Decorator;

use Drupal\bookable_calendar\Renderer;
use Drupal\bookable_calendar\BookableCalendarOpeningInstanceInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Overriding service method.
 */
class RendererDecorator extends Renderer {

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    AccountInterface $current_user,
    Connection $connection,
    LanguageManagerInterface $languageManager,
    DateFormatterInterface $dateFormatter,
  ) {
    // Properly initialize parent typed properties.
    parent::__construct(
      $entityTypeManager,
      $current_user,
      $connection,
      $languageManager,
      $dateFormatter
    );
  }

  /**
   * Override only this method.
   */
  public function instanceBookLink(BookableCalendarOpeningInstanceInterface $instance): array {
    $slots_available = $instance->slotsAvailable();

    // Show only text when no slots.
    if ($slots_available === 0) {
      return [
        '#type' => 'markup',
        '#markup' => '<span class="availability__text fully-booked">' .
        $this->t('No Slots Available') .
        '</span>',
      ];
    }

    // Default behavior.
    return parent::instanceBookLink($instance);
  }

}
