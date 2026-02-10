<?php

namespace Drupal\itinerary_helper\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Form alter hooks for Itinerary Helper.
 */
class ItineraryHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_form_alter().
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $form_id
   *   The form ID.
   *
   * @return void
   *   No return value.
   */
  #[Hook('form_alter')]
  public function itineraryHelperFormAlter(array &$form, FormStateInterface $form_state, string $form_id) : void {

    if (!isset($form['field_trip_itinerary']['widget'])) {
      return;
    }

    foreach ($form['field_trip_itinerary']['widget'] as $delta => &$element) {
      if (!is_numeric($delta)) {
        continue;
      }

      $day_number = $delta + 1;

      if (isset($element['top']['type']['label']['#markup'])) {
        $original = strip_tags($element['top']['type']['label']['#markup']);
        $element['top']['type']['label']['#markup'] = $this->t(
              '@label @number',
              [
                '@label' => $original,
                '@number' => $day_number,
              ]
          );
      }
    }
  }

}
