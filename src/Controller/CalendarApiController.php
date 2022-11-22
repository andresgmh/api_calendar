<?php

namespace Drupal\api_calendar\Controller;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\api_calendar\Client\Calendar;

/**
 * Calendar API controller.
 */
class CalendarApiController extends ControllerBase {

  /**
   * Calendar client.
   *
   * @var \Drupal\api_calendar\Client\Calendar
   */
  protected Calendar $calendar;

  /**
   * Construct a new Calendar API controller.
   *
   * @param \Drupal\api_calendar\Client\Calendar $calendar
   *   The Calendar manager.
   */
  public function __construct(Calendar $calendar) {
    $this->calendar = $calendar;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('api_calendar.calendar')
    );
  }

  /**
   * Get Events Data.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   HTTP Request.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   Json response.
   *
   * @throws \Exception
   */
  public function getEvents(Request $request) {
    $query_string_data = $this->getQueryStringData($request);

    if (empty($query_string_data['start'])) {
      throw new BadRequestHttpException('Please provide a valid Start date parameter');
    }

    if (empty($query_string_data['end'])) {
      throw new BadRequestHttpException('Please provide a valid End date parameter.');
    }

    $start = $query_string_data['start'];
    $end = $query_string_data['end'];

    $response = $this->calendar->getEvents($start, $end);
    $response = new CacheableJsonResponse($response);
    $this->setCacheableDependency($response);
    return $response;
  }

  /**
   * Sets the cacheable dependency on url.query_args.
   *
   * @param \Drupal\Core\Cache\CacheableJsonResponse $response
   *   Cacheable JSON response to modify.
   */
  private function setCacheableDependency(CacheableJsonResponse $response) {
    $response->addCacheableDependency(CacheableMetadata::createFromRenderArray([
      '#cache' => [
        // 24 Hours.
        'max-age' => 86400,
        'contexts' => [
          'url',
        ],
      ],
    ]));
  }

  /**
   * Helper function, extracts querystring variables into an array.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   HTTP Request.
   *
   * @return array
   *   An array holding any querystring values.
   */
  private function getQueryStringData(Request $request) {
    $data = [];
    parse_str($request->getQueryString(), $data);
    return $data;
  }

}
