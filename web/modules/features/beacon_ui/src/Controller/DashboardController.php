<?php

namespace Drupal\beacon_ui\Controller;

use Drupal\beacon\BeaconStats;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
   * The beacon stats service.
   *
   * @var \Drupal\beacon\BeaconStats
   */
  protected $stats;

  /**
   * Constructs a new DashboardController object.
   *
   * @param \Drupal\beacon\BeaconStats $stats
   *   The beacon stats service.
   */
  public function __construct(BeaconStats $stats) {
    $this->stats = $stats;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('beacon.stats')
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
        'contexts' => [
          'user',
        ],
        'tags' => [
          'user.channels:' . $this->stats->getCurrentUserId(),
        ],
        'max-age' => self::CACHE_MAX_AGE,
      ],
    ];

    // Load the user's channels.
    $channels = $this->stats->getUserChannels();

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
    $build['#channel_count'] = number_format(count($channels));

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
    $counts = $this->stats->getChannelEventCountPerDay();

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

      $build['#event_today_count'] = number_format($build['#event_today_count']);

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
    foreach ($this->stats->getChannelEventCounts() as $channel_id => $count) {
      // Add the channel.
      $build['#channel_event_count_chart']['#config']['data']['labels'][] = $channels[$channel_id]->label();

      // Add the count.
      $build['#channel_event_count_chart']['#config']['data']['datasets'][0]['data'][] = $count;

      // Add to the total event count.
      $build['#event_count'] += $count;
    }

    $build['#event_count'] = number_format($build['#event_count']);

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
    foreach ($this->stats->getEventSeverityCounts() as $severity => $count) {
      // Add the channel.
      $build['#event_severity_count_chart']['#config']['data']['labels'][] = $severity;

      // Add the count.
      $build['#event_severity_count_chart']['#config']['data']['datasets'][0]['data'][] = $count;
    }

    // Count the enabled alerts.
    $build['#enabled_alerts_count'] = number_format($this->stats->getEnabledAlertCount());

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

}
