<?php

namespace Drupal\api_calendar\Client;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Calendar events manager.
 */
class Calendar {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Construct a new Calendar events manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get events given a date range.
   *
   * @param string $start_date
   *   The start date.
   * @param string $end_date
   *   The end date.
   *
   * @return array
   *   An array of events.
   */
  public function getEvents($start_date, $end_date) {
    $events = [];
    $start_date = new DrupalDateTime($start_date);
    $start_date->setTimezone(new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $start_date = $start_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);

    $end_date = new DrupalDateTime($end_date);
    $end_date->setTimezone(new \DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE));
    $end_date = $end_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);

    // Get nodes event content type.
    $storage = $this->entityTypeManager->getStorage('node');
    $query = $storage->getQuery()
      ->condition('type', 'event')
      ->condition('status', 1)
      ->sort('field_event_date.value', 'ASC')
      ->condition('field_event_date.value', $start_date, '>=')
      ->condition('field_event_date.end_value', $end_date, '<=');

    $result = $query->execute();

    if (count($result) > 0) {
      $nodes = $storage->loadMultiple($result);
      foreach ($nodes as $node) {
        $date = $node->get('field_event_date')->getValue();
        $start = $date[0]['value'];
        $end = $date[0]['end_value'];
        $start_date = date('Y-m-d', strtotime($start));
        $end_date = date('Y-m-d', strtotime($end));
        $month = date('M', strtotime($start));
        $day = date('d', strtotime($start));
        $events[] = [
          'title' => $node->label(),
          'start' => $start_date,
          'end' => $end_date,
          'extendedProps' => [
            'location' => $node->get('field_event_location_name')->value,
            'month' => $month,
            'day' => $day,
          ],
        ];

      }
    }

    return $events;
  }

}
