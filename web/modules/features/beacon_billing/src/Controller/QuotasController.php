<?php

namespace Drupal\beacon_billing\Controller;

use Drupal\beacon\BeaconStats;
use Drupal\beacon_billing\BeaconBilling;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class QuotasController.
 *
 * Provide the quotas page.
 */
class QuotasController extends ControllerBase {

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
   * The beacon billing service.
   *
   * @var \Drupal\beacon_billing\BeaconBilling
   */
  protected $beaconBilling;

  /**
   * Constructs a new QuotasController object.
   *
   * @param \Drupal\beacon\BeaconStats $stats
   *   The beacon stats service.
   * @param \Drupal\beacon_billing\BeaconBilling $beacon_billing
   *   The beacon billing service.
   */
  public function __construct(BeaconStats $stats, BeaconBilling $beacon_billing) {
    $this->stats = $stats;
    $this->beaconBilling = $beacon_billing;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('beacon.stats'),
      $container->get('beacon_billing')
    );
  }

  /**
   * The quotas page.
   *
   * @return array
   *   A renderable page array.
   */
  public function page() {
    // Load the user's subscription.
    $subscription = $this->beaconBilling->getUserSubscription();

    // Add the caching rules.
    $build = [
      '#theme' => 'beacon_billing_quotas',
      '#cache' => [
        'keys' => ['quotas'],
        'contexts' => [
          'user',
        ],
        'tags' => array_merge([
          'user.channels:' . $this->stats->getCurrentUserId(),
        ], $subscription->getCacheTags()),
        'max-age' => self::CACHE_MAX_AGE,
      ],
    ];

    // Stop if the user does not have a subscription..
    if (!$subscription) {
      // Add a message.
      $build['#no_content'] = [
        '#markup' => t('You currently do not have a subscription.'),
      ];

      // Stop here.
      return $build;
    }

    // Load the user's subscription plan.
    $plan = $this->beaconBilling->getUserSubscriptionPlanDefinition();

    // Load the user's channels.
    $channels = $this->stats->getUserChannels();

    // Check if there are no channels.
    if (!$channels) {
      // Add a message.
      $build['#no_content'] = [
        '#markup' => t('You currently have no channels.'),
      ];

      // Stop here.
      return $build;
    }

    // Add a chart for the number of events per channel.
    $build['#channel_event_quota_chart'] = [
      '#theme' => 'chartjs',
      '#id' => 'channel-event-quota-chart',
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
            'display' => FALSE,
          ],
          'scales' => [
            'xAxes' => [
              [
                'display' => TRUE,
                'scaleLabel' => [
                  'display' => FALSE,
                  'labelString' => t('Channel'),
                ],
              ],
            ],
            'yAxes' => [
              [
                'display' => TRUE,
                'ticks' => [
                  'beginAtZero' => TRUE,
                  'max' => $plan['quotaEvents'],
                  'min' => 0,
                ],
              ],
            ],
          ],
        ],
      ],
    ];

    // Add the channels.
    foreach ($this->stats->getChannelEventCounts() as $channel_id => $count) {
      // Add the channel.
      $build['#channel_event_quota_chart']['#config']['data']['labels'][] = $channels[$channel_id]->label();

      // Add the count.
      $build['#channel_event_quota_chart']['#config']['data']['datasets'][0]['data'][] = $count;
    }

    // Add a chart for the number of alerts per channel.
    $build['#channel_alert_quota_chart'] = [
      '#theme' => 'chartjs',
      '#id' => 'channel-alert-quota-chart',
      '#config' => [
        'type' => 'bar',
        'data' => [
          'labels' => [],
          'datasets' => [
            [
              'label' => t('Alerts'),
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
            'display' => FALSE,
          ],
          'scales' => [
            'xAxes' => [
              [
                'display' => TRUE,
                'scaleLabel' => [
                  'display' => FALSE,
                  'labelString' => t('Channel'),
                ],
              ],
            ],
            'yAxes' => [
              [
                'display' => TRUE,
                'ticks' => [
                  'beginAtZero' => TRUE,
                  'max' => $plan['quotaAlerts'],
                  'min' => 0,
                  'fixedStepSize' => ($plan['quotaAlerts'] > 14) ? 2 : 1,
                ],
              ],
            ],
          ],
        ],
      ],
    ];

    // Add the channels.
    foreach ($this->stats->getChannelAlertCounts() as $channel_id => $count) {
      // Add the channel.
      $build['#channel_alert_quota_chart']['#config']['data']['labels'][] = $channels[$channel_id]->label();

      // Add the count.
      $build['#channel_alert_quota_chart']['#config']['data']['datasets'][0]['data'][] = $count;
    }

    return $build;
  }

}
