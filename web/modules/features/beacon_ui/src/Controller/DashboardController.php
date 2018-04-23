<?php

namespace Drupal\beacon_ui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Database\Driver\mysql\Connection;

/**
 * Class DashboardController.
 *
 * Provide the dashboard page.
 */
class DashboardController extends ControllerBase {

  /**
   * The page cache lifetime.
   *
   * @var int
   */
  const CACHE_MAX_AGE = 900;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Constructs a new DashboardController object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Database\Driver\mysql\Connection $database
   *   The database.
   */
  public function __construct(AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager, EntityFieldManager $entity_field_manager, Connection $database) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('database')
    );
  }

  /**
   * The dashboard page.
   *
   * @return array
   *   A renderable page array.
   */
  public function page() {
    // Add the caching rules.
    $build = [
      '#theme' => 'beacon_dashboard',
      '#cache' => [
        'context' => [
          'user',
        ],
        'tags' => [
          'user.channels:' . $this->currentUser->id(),
        ],
        'max-age' => self::CACHE_MAX_AGE,
      ],
    ];

    // Load the user's channels.
    $channels = $this->getUserChannels();

    // Check if there are no channels.
    if (!$channels) {
      // Add a message.
      $build['#no_channels'] = [
        '#markup' => t('You currently have no channels. Add one to get started.'),
      ];

      // Stop here.
      return $build;
    }

    // Add the channel count.
    $build['#channel_count'] = count($channels);

    // Add a chart for the number of events per channel per day over the last week.
    $build['#channel_event_per_day_count_chart'] = [
      '#theme' => 'chartjs',
      '#id' => 'channel-event-per-day-count-chart',
      '#config' => [
        'type' => 'line',
        'data' => [
          'labels' => [],
          'datasets' => [],
        ],
        'options' => [
          'maintainAspectRatio' => FALSE,
          'responsive' => TRUE,
          'legend' => [
            'position' => 'top',
          ],
          'tooltips' => [
            'mode' => 'index',
            'intersect' => TRUE,
          ],
          'hover' => [
            'mode' => 'nearest',
            'intersect' => FALSE,
          ],
          'scales' => [
            'xAxes' => [[
              'display' => TRUE,
              'scaleLabel' => [
                'display' => TRUE,
                'labelString' => t('Day'),
              ],
            ]],
            'yAxes' => [[
              'display' => TRUE,
              'ticks' => [
                'beginAtZero' => TRUE,
                'labelString' => t('Events'),
              ],
            ]],
          ],
        ],
      ],
    ];

    // Load the event counts per channel per day.
    $counts = $this->getChannelEventCountPerDay();

    // Count the events from today.
    $build['#event_today_count'] = 0;

    // Generate a date for today.
    $today = format_date(time(), 'custom', 'Y-m-d');

    // Add the counts to the chart.
    foreach ($counts as $channel_id => $data) {
      // Determine if we should populate the day labels.
      $populate_day_labels = empty($build['#channel_event_per_day_count_chart']['#config']['data']['labels']);

      // Reverse the days.
      $data = array_reverse($data);

      // Iterate the days.
      foreach ($data as $day => $count) {
        // Check if the day labels need to be populated.
        if ($populate_day_labels) {
          $build['#channel_event_per_day_count_chart']['#config']['data']['labels'][] = substr($day, 5);
        }

        // Check if this is for today.
        if ($day == $today) {
          // Add to today's count.
          $build['#event_today_count'] += $count;
        }
      }

      // Generate a random color.
      $color = $this->randomColor();

      // Add the data.
      $build['#channel_event_per_day_count_chart']['#config']['data']['datasets'][] = [
        'label' => $channels[$channel_id]->label(),
        'fill' => FALSE,
        'backgroundColor' => $color,
        'borderColor' => $color,
        'data' => array_values($data),
      ];
    }

    // Add a chart for the number of events per channel.
    $build['#channel_event_count_chart'] = [
      '#theme' => 'chartjs',
      '#id' => 'channel-event-count-chart',
      '#config' => [
        'type' => 'bar',
        'data' => [
          'labels' => [],
          'datasets' => [
            [
              'label' => t('Events'),
              'data' => [],
              'borderWidth' => 1,
              'backgroundColor' => 'rgba(255, 159, 64, 0.3)',
              'borderColor' => 'rgb(255, 159, 64)',
            ],
          ],
        ],
        'options' => [
          'maintainAspectRatio' => FALSE,
          'responsive' => TRUE,
          'legend' => [
            'position' => 'bottom',
          ],
          'scales' => [
            'xAxes' => [[
              'display' => TRUE,
              'scaleLabel' => [
                'display' => FALSE,
                'labelString' => t('Channel'),
              ],
            ]],
            'yAxes' => [[
              'display' => TRUE,
              'ticks' => [
                'beginAtZero' => TRUE,
              ],
            ]],
          ],
        ],
      ],
    ];

    // Count all events.
    $build['#event_count'] = 0;

    // Add the channels.
    foreach ($this->getChannelEventCounts() as $channel_id => $count) {
      // Add the channel.
      $build['#channel_event_count_chart']['#config']['data']['labels'][] = $channels[$channel_id]->label();

      // Add the count.
      $build['#channel_event_count_chart']['#config']['data']['datasets'][0]['data'][] = $count;

      // Add to the total event count.
      $build['#event_count'] += $count;
    }

    // Add a chart for the number of events per severity.
    $build['#event_severity_count_chart'] = [
      '#theme' => 'chartjs',
      '#id' => 'event-severity-count-chart',
      '#config' => [
        'type' => 'polarArea',
        'data' => [
          'labels' => [],
          'datasets' => [
            [
              'label' => t('Events'),
              'data' => [],
              'borderWidth' => 1,
              'backgroundColor' => [
                'rgba(255, 99, 132, 0.3)',
              	'rgba(255, 159, 64, 0.3)',
              	'rgba(255, 205, 86, 0.3)',
              	'rgba(75, 192, 192, 0.3)',
              	'rgba(54, 162, 235, 0.3)',
              	'rgba(153, 102, 255, 0.3)',
              	'rgba(201, 203, 207, 0.3)'
              ],
            ],
          ],
        ],
        'options' => [
          'maintainAspectRatio' => FALSE,
          'responsive' => TRUE,
          'legend' => [
            'position' => 'right',
          ],
          'scale' => [
            'ticks' => [
              'beginAtZero' => TRUE,
            ],
            'reverse' => FALSE,
          ],
        ],
      ],
    ];

    // Add the severity counts and labels.
    foreach ($this->getEventSeverityCounts() as $severity => $count) {
      // Add the channel.
      $build['#event_severity_count_chart']['#config']['data']['labels'][] = $severity;

      // Add the count.
      $build['#event_severity_count_chart']['#config']['data']['datasets'][0]['data'][] = $count;
    }

    // Count the enabled alerts.
    $build['#enabled_alerts_count'] = $this->getEnabledAlertCount();

    return $build;
  }

  /**
   * Generate a random color string for a chart.
   *
   * @param float $alpha
   *   The transparency value. Defauts to 0.5.
   * @return string
   *   A color string for ChartJS.
   */
  public function randomColor(float $alpha = 0.5) {
    return 'rgba(' . rand(0, 255) . ', ' . rand(0, 255) . ', ' . rand(0, 255) . ', ' . $alpha . ')';
  }

  /**
   * Get a count of enabled alerts.
   *
   * @return int
   *   A count of enabled alerts for the given user.
   */
  public function getEnabledAlertCount() {
    $query = $this->database->select('alert');
    $query->addExpression('COUNT(id)', 'count');
    $query->condition('enabled', 1);
    $query->condition('user_id', $this->currentUser->id());
    return $query->execute()->fetchField();
  }

  /**
   * Get the counts of events per-channel, per-day, for the last week.
   *
   * @return array
   *   An associative array of count data, keyed by the channel ID. The nested array
   *   is keyed by the day in the format Y-m-d.
   */
  public function getChannelEventCountPerDay() {
    $counts = [];

    // Initialize the counts.
    foreach ($this->getUserChannels() as $channel_id => $channel) {
      $counts[$channel_id] = [];

      // Default to 0 for each of the last 7 days.
      for ($i = 0; $i < 7; $i++) {
        $counts[$channel_id][format_date(strtotime("-{$i} days"), 'custom', 'Y-m-d')] = 0;
      }
    }

    // Query the database to get counts per channel, per day for the last week.
    $query = $this->database->select('event');
    $query->addField('event', 'channel');
    $query->addExpression('DATE(FROM_UNIXTIME(created))', 'createdDate');
    $query->addExpression('COUNT(id)', 'eventCount');
    $query->condition('user_id', $this->currentUser->id());
    $query->condition('created', strtotime('midnight', strtotime('-1 week')), '>');
    $query->groupBy('channel');
    $query->groupBy('DATE(FROM_UNIXTIME(created))');

    // Execute the query.
    $results = $query->execute();

    // Iterate the results.
    foreach ($results as $result) {
      $counts[$result->channel][$result->createdDate] = $result->eventCount;
    }

    return $counts;
  }

  /**
   * Get event counts for each severity type.
   *
   * @return array
   *   An array of event counts, keyed by severity label.
   */
  public function getEventSeverityCounts() {
    // Get the allowed severity values.
    $severity_values = $this->entityFieldManager
      ->getFieldStorageDefinitions('event')['severity']
      ->getSetting('allowed_values');

    // Query to find the counts for each severity value.
    $query = $this->database->select('event');
    $query->addField('event', 'severity');
    $query->addExpression('COUNT(id)', 'count');
    $query->condition('event.user_id', $this->currentUser->id());
    $query->groupBy('severity');
    $results = $query->execute()->fetchAllKeyed();

    // Replace the values with labels.
    foreach ($results as $severity => $count) {
      $results[$severity_values[$severity]] = $count;
      unset($results[$severity]);
    }

    return $results;
  }

  /**
   * Get all channels that the current user owns.
   *
   * @return array
   *   An array of channel entities.
   */
  public function getUserChannels() {
    $channels = &drupal_static(__METHOD__, NULL);

    // Check if the channels haven't been loaded yet.
    if ($channels === NULL) {
      // Initialize the cache.
      $channels = [];

      // Query to find the channels.
      $query = $this->database->select('channel');
      $query->addField('channel', 'id');
      $query->condition('user_id', $this->currentUser->id());
      $query->orderBy('name');

      // Execute the query.
      $results = $query->execute()->fetchCol();

      // Load the channels.
      if ($results) {
        $channels = $this->entityTypeManager
          ->getStorage('channel')
          ->loadMultiple($results);
      }
    }

    return $channels;
  }

  /**
   * Get event counts per-channel.
   *
   * @return array
   *   An array of event counts, keyed by channel ID.
   */
  public function getChannelEventCounts() {
    $counts = [];

    // Load the user channels.
    $channels = $this->getUserChannels();

    // Stop if there are no channels.
    if (!$channels) {
      return [];
    }

    // Query to find the counts.
    $query = $this->database->select('event');
    $query->addField('event', 'channel');
    $query->addExpression('COUNT(id)', 'count');
    $query->condition('event.user_id', $this->currentUser->id());
    $query->groupBy('channel');
    $results = $query->execute()->fetchAllKeyed();

    // Iterate the channels.
    foreach ($channels as $channel) {
      // Add the counts.
      $counts[$channel->id()] = isset($results[$channel->id()]) ? $results[$channel->id()] : 0;
    }

    return $counts;
  }

}
