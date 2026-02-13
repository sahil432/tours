<?php

namespace Drupal\itinerary_helper\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Form alter hooks for Itinerary Helper.
 */
class ItineraryHooks {
  use StringTranslationTrait;

  public function __construct(
    private readonly RouteMatchInterface $routeMatch,
  ) {}

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
    $route_name = $this->routeMatch->getRouteName();
    // Disable widgets on both routes.
    if (in_array($route_name, [
      'entity.booking_contact.edit_form',
      'bookable_calendar.booking_contact.create',
    ])) {

      // Disable party_size widget if present.
      if (isset($form['party_size']['widget'][0]['value'])) {
        $form['party_size']['widget'][0]['value']['#disabled'] = TRUE;
      }
    }

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

  /**
   * Implements hook_menu_local_tasks_alter().
   */
  #[Hook('menu_local_tasks_alter')]
  public function alterLocalTasks(array &$local_tasks) {
    // Hiding Local task menu for bookable calender.
    $remove = [
      'bookable_calendar.opening_instances',
      'bookable_calendar.booking_contacts',
    ];

    // Ensure tabs and secondary level exist.
    if (!isset($local_tasks['tabs']) || !isset($local_tasks['tabs'][1])) {
      return;
    }

    foreach ($remove as $plugin_id) {
      if (isset($local_tasks['tabs'][1][$plugin_id])) {
        unset($local_tasks['tabs'][1][$plugin_id]);
      }
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_booking_contact_add_form_alter')]
  public function formBookingContactAddFormAlter(
    array &$form,
    FormStateInterface $form_state,
  ) {
    $form['actions']['submit']['#submit'][] = [$this, 'bookingRedirectSubmit'];
  }

  /**
   * Submit handler to redirect back to opening after booking.
   */
  public function bookingRedirectSubmit(array &$form, FormStateInterface $form_state): void {
    $instance = $this->routeMatch->getParameter('opening_instance');
    if ($instance) {
      $form_state->setRedirect(
        'entity.bookable_calendar_opening.canonical',
        ['bookable_calendar_opening' => $instance->booking_opening->target_id]
      );
    }
  }

  /**
  * Implements hook_form_FORM_ID_alter().
  */
  #[Hook('form_bookable_calendar_opening_edit_form_alter')]
  #[Hook('form_bookable_calendar_opening_add_form_alter')]
  public function formBookingopeningAddFormAlter(
    array &$form,
    FormStateInterface $form_state,
  ) {
    $form['#validate'][] = [$this, 'calenderOpeningValidate'];
  }

  /**
   * Validate Callback.
   */
  public function calenderOpeningValidate(array &$form, FormStateInterface $form_state) {
    $formValues = $form_state->getValues();
    $total_slots = $formValues['slots'][0]['value'];
    $roomCount = $formValues['field_2_single_bed_s'][0]['value'] + $formValues['field_king_bed'][0]['value'] + $formValues['field_queen_bed'][0]['value'];
    if ($roomCount !== $total_slots) {
      $form_state->setErrorByName(
        'slots',
        t('Total rooms (%rooms) must equal total slots (%slots).', [
          '%rooms' => $roomCount,
          '%slots' => $total_slots,
        ])
      );
    }
  }

}
