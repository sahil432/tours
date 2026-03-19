<?php

namespace Drupal\j_navi_itinerary_helper\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Cache\Cache;

/**
 * Form alter hooks for Itinerary Helper.
 */
class ItineraryHooks {
  use StringTranslationTrait;

  public function __construct(
    private readonly RouteMatchInterface $routeMatch,
    private readonly EntityTypeManagerInterface $entityTypeManager,
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

      $day_number = $delta + 2;

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
  public function formBookingContactAddFormAlter(array &$form, FormStateInterface $form_state) {
    $instance = $this->routeMatch->getParameter('opening_instance');
    $opening = $instance->get('booking_opening')->entity;
    $calendar = $opening->get('bookable_calendar')->entity;
    $label = strtolower($calendar->label());
    if (str_contains($label, 'hotel')) {
      $form['#validate'][] = [$this, 'roomCapacityValidate'];
    }
    else {
      // Hide room type for non-hotel.
      $form['field_room_type']['#access'] = FALSE;
    }

    // Only hotel should have room type logic.
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

    $entity = $form_state->getFormObject()->getEntity();
    // =========================
    // ADD FORM
    // =========================
    if ($entity->isNew()) {

      $type = \Drupal::request()->query->get('type');
      if (!$type) {
        throw new NotFoundHttpException();
      }
      $options = $form['bookable_calendar']['widget']['#options'] ?? [];
      if (!array_key_exists($type, $options)) {
        throw new NotFoundHttpException();
      }
      // Preselect value.
      $form['bookable_calendar']['widget']['#default_value'] = $type;
    }
    else {
      // Edit form.
      $type = $entity->get('bookable_calendar')->target_id;
    }

    // =========================
    // LOAD CALENDAR ENTITY
    // =========================
    $calendar = $this->entityTypeManager->getStorage('bookable_calendar')->load($type);
    $label = $calendar ? strtolower($calendar->label()) : '';
    switch (TRUE) {
      // HOTEL.
      case str_contains($label, 'hotel'):
        // Show hotel fields.
        // $this->hideHotelFields($form);
        // Add hotel validation.
        $form['#validate'][] = [self::class, 'hotelOpeningValidate'];
        break;

      // BUS.
      case str_contains($label, 'bus'):
        self::hideHotelFields($form);
        break;

      // FLIGHT.
      case str_contains($label, 'flight'):
        self::hideHotelFields($form);
        break;

      default:
        self::hideHotelFields($form);
        break;

    }

    $form['bookable_calendar']['widget']['#disabled'] = TRUE;
    $form['actions']['submit']['#submit'][] = [self::class, 'calenderOpening'];
  }

  /**
   * Submit handler to redirect back to opening after booking.
   */
  public static function calenderOpening(array &$form, FormStateInterface $form_state): void {
    // Invalidate calendar opening instance view cache.
    Cache::invalidateTags(['bookable_calendar_opening_inst_view']);
  }

  /**
   * Hide Hotel Fields.
   */
  private static function hideHotelFields(array &$form): void {
    $form['field_2_single_bed_s']['#access'] = FALSE;
    $form['field_king_bed']['#access'] = FALSE;
    $form['field_queen_bed']['#access'] = FALSE;
  }

  /**
   * Validate Callback.
   */
  public static function hotelOpeningValidate(array &$form, FormStateInterface $form_state) {
    $formValues = $form_state->getValues();
    $total_slots = (int) $formValues['slots'][0]['value'];
    $roomCount = (int) ($formValues['field_2_single_bed_s'][0]['value'] + $formValues['field_king_bed'][0]['value'] + $formValues['field_queen_bed'][0]['value']);
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

  /**
   * Room Capacity Validator.
   */
  public function roomCapacityValidate(array &$form, FormStateInterface $form_state) {
    $room_type = $form_state->getValue('field_room_type')[0]['value'] ?? NULL;
    if ($room_type === '_none' || empty($room_type)) {
      $form_state->setErrorByName(
          'field_room_type',
          $this->t('Select Room type.')
        );
    }
    if ($room_type) {
      /** @var \Drupal\bookable_calendar\Entity\BookableCalendarOpeningInstance $instance */
      $instance = $this->routeMatch->getParameter('opening_instance');
      $opening = $instance->get('booking_opening')->entity;
      $capacity = (int) $opening->get($room_type)->value;
      // Count how many bookings already exist
      // for this opening instance + room type.
      $connection = Database::getConnection();
      $query = $connection->select('booking_contact__field_room_type', 'rt');
      // Join booking_contact.
      $query->join('booking_contact', 'bc', 'bc.id = rt.entity_id');
      // Join booking_contact__booking.
      $query->join('booking_contact__booking', 'bcb', 'bcb.entity_id = bc.id');
      // Join booking entity.
      $query->join('booking', 'b', 'b.id = bcb.booking_target_id');
      $query->condition('rt.deleted', 0);
      $query->condition('bcb.deleted', 0);
      $query->condition('rt.field_room_type_value', $room_type);

      // THIS is the real opening instance filter.
      $query->condition('b.booking_instance', $instance->id());

      $count = (int) $query->countQuery()->execute()->fetchField();
      if ($count >= $capacity) {
        $form_state->setErrorByName(
          'field_room_type',
          $this->t('Selected room type is fully booked for this date.')
        );
      }
    }
  }

  /**
   * Implements hook_menu_local_actions_alter().
   */
  #[Hook('menu_local_actions_alter')]
  public function menuLocalActionsAlter(array &$actions) {
    foreach ($actions as $key => $action) {
      // Target Bookable Calendar Opening add button.
      if ($action['route_name'] === 'entity.bookable_calendar_opening.add_form') {
        // Remove the button
        // (admin/content/bookable-calendar/bookable-calendar-opening)
        unset($actions[$key]);
      }
    }
  }

  /**
   * Booking Add form Alter.
   */
  #[Hook('form_booking_add_form_alter')]
  public function bookingAddFormAlter(array &$form, FormStateInterface $form_state): void {

    $opening_id = \Drupal::request()->query->get('bookable_calendar_opening');

    if (empty($opening_id)) {
      return;
    }

    /** @var \Drupal\bookable_calendar\Entity\BookableCalendarOpening $opening */
    $opening = $this->entityTypeManager->getStorage('bookable_calendar_opening')->load($opening_id);
    if (!$opening) {
      return;
    }
    $calendar = $opening->get('bookable_calendar')->entity;
    $label = strtolower($calendar->label());
    if (str_contains($label, 'hotel')) {
      $form['#validate'][] = [$this, 'roomCapacityBookAddValidate'];
    }
    else {
      // Hide room type for non-hotel.
      $form['field_room_type']['#access'] = FALSE;
    }

    $instanceStorage = \Drupal::entityTypeManager()->getStorage('bookable_calendar_opening_inst');
    $instances = $instanceStorage->loadByProperties([
      'booking_opening' => $opening->id(),
    ]);

    if (!empty($instances)) {
      $instance = reset($instances);
      $form['booking_instance']['widget'][0]['target_id']['#default_value'] = $instance;
      $form['booking_instance']['widget']['#disabled'] = TRUE;
    }
    /*
     * Prefill Booking Calendar
     */
    if (!$opening->get('bookable_calendar')->isEmpty()) {
      $form['booking_calendar']['widget'][0]['target_id']['#default_value'] = $calendar;
      $form['booking_calendar']['widget']['#disabled'] = TRUE;
    }
    /*
     * Prefill Booking Date
     */
    if ($opening && $opening->hasField('date') && !$opening->get('date')->isEmpty()) {
      $start_timestamp = $opening->get('date')->value ?? NULL;
      $end_timestamp   = $opening->get('date')->end_value ?? NULL;
      // Set only if BOTH start and end exist and are numeric.
      if (is_numeric($start_timestamp) && is_numeric($end_timestamp)) {
        $form['booking_date']['widget'][0]['value']['#default_value'] = DrupalDateTime::createFromTimestamp((int) $start_timestamp);
        $form['booking_date']['widget'][0]['end_value']['#default_value'] = DrupalDateTime::createFromTimestamp((int) $end_timestamp);
        $form['booking_date']['widget']['#disabled'] = TRUE;
      }
    }
    $form['actions']['submit']['#submit'][] = [$this, 'bookingAddSubmit'];
  }

  /**
   * Submit handler to redirect back to opening after booking.
   */
  public function bookingAddSubmit(array &$form, FormStateInterface $form_state): void {
    $calender = \Drupal::request()->query->get('bookable_calendar_opening');
    if ($calender) {
      $form_state->setRedirect(
        'entity.bookable_calendar_opening.canonical',
        ['bookable_calendar_opening' => $calender]
      );
    }
  }

  /**
   * Booking Edit form Alter.
   */
  #[Hook('form_booking_edit_form_alter')]
  public function bookingEditFormAlter(array &$form, FormStateInterface $form_state): void {
    $form['booking_date']['widget']['#disabled'] = TRUE;
    $form['booking_calendar']['widget']['#disabled'] = TRUE;
    $form['booking_instance']['widget']['#disabled'] = TRUE;
    $opening_id = \Drupal::request()->query->get('bookable_calendar_opening');
    if (empty($opening_id)) {
      return;
    }

    /** @var \Drupal\bookable_calendar\Entity\BookableCalendarOpening $opening */
    $opening = $this->entityTypeManager->getStorage('bookable_calendar_opening')->load($opening_id);
    if (!$opening) {
      return;
    }
    $calendar = $opening->get('bookable_calendar')->entity;
    $label = strtolower($calendar->label());
    if (str_contains($label, 'hotel')) {
      $form['#validate'][] = [$this, 'roomCapacityBookAddValidate'];
    }
    else {
      // Hide room type for non-hotel.
      $form['field_room_type']['#access'] = FALSE;
    }
  }

  /**
   * Room Capacity Validator.
   */
  public function roomCapacityBookAddValidate(array &$form, FormStateInterface $form_state) {
    $room_type = $form_state->getValue('field_room_type')[0]['value'] ?? NULL;
    if ($room_type === '_none' || empty($room_type)) {
      $form_state->setErrorByName(
          'field_room_type',
          $this->t('Select Room type.')
        );
      return;
    }

    $opening_id = \Drupal::request()->query->get('bookable_calendar_opening');
    $opening = $this->entityTypeManager->getStorage('bookable_calendar_opening')->load($opening_id);
    if (!$opening) {
      return;
    }

    $capacity = (int) $opening->get($room_type)->value;

    // Get the booking entity (if editing).
    $booking = $form_state->getFormObject()->getEntity();
    $is_new = $booking->isNew();

    // If editing, check if room type actually changed.
    if (!$is_new) {
      $original_room_type = $booking->get('field_room_type')->value ?? NULL;
      // If room type hasn't changed, no need to validate capacity.
      if ($original_room_type === $room_type) {
        return;
      }
    }

    // Count how many bookings already exist for this opening + room type.
    $connection = Database::getConnection();
    $query = $connection->select('booking__field_room_type', 'rt');
    $query->join('booking', 'b', 'b.id = rt.entity_id');
    $query->join('bookable_calendar_opening_inst', 'boi', 'boi.id = b.booking_instance');
    $query->condition('rt.deleted', 0);
    $query->condition('rt.field_room_type_value', $room_type);
    $query->condition('boi.booking_opening', $opening_id);

    // If editing, exclude current booking from count.
    if (!$is_new) {
      $query->condition('b.id', $booking->id(), '<>');
    }

    $count = (int) $query->countQuery()->execute()->fetchField();

    if ($count >= $capacity) {
      $form_state->setErrorByName(
        'field_room_type',
        $this->t('Selected room type is fully booked for this date.')
      );
    }
  }

  /**
   * Implements hook_entity_operation_alter().
   */
  #[Hook('entity_operation_alter')]
  public function entityOperationAlter(array &$operations, EntityInterface $entity): void {
    // Add bookable_calendar_opening query parameter to booking operations.
    if ($entity->getEntityTypeId() === 'booking') {
      $booking_instance = $entity->get('booking_instance')->entity;
      if ($booking_instance) {
        $opening_id = $booking_instance->get('booking_opening')->target_id;
        if ($opening_id) {
          foreach ($operations as &$operation) {
            if (isset($operation['url'])) {
              $operation['url']->setOption('query', [
                'bookable_calendar_opening' => $opening_id,
              ]);
            }
          }
        }
      }
    }
  }

}
