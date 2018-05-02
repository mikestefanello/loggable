<?php

namespace Drupal\beacon;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Database\Driver\mysql\Connection;

/**
 * Class BeaconStats.
 *
 * Contains helper functions to generate stat-driven pages.
 *
 * TODO: Switch to all Entity queries rather than directly using the DB.
 */
class BeaconStats {

  /**
   * Constructs a new BeaconStats object.
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
   * Get the current user's ID.
   *
   * @return int
   *   The current user's ID.
   */
  public function getCurrentUserId() {
    return $this->currentUser->id();
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
    $query->condition('user_id', $this->getCurrentUserId());
    return $query->execute()->fetchField();
  }

  /**
   * Get the counts of events per-channel, per-day, for the last week.
   *
   * @param int $days
   *   The amount of days to look back. Defaults to 7.
   *
   * @return array
   *   An associative array of count data, keyed by the channel ID. The nested
   *   array is keyed by the day in the format Y-m-d.
   */
  public function getChannelEventCountPerDay($days = 7) {
    $counts = [];

    // Initialize the counts.
    foreach ($this->getUserChannels() as $channel_id => $channel) {
      $counts[$channel_id] = [];

      // Default to 0 for each of the last days.
      for ($i = 0; $i < $days; $i++) {
        $counts[$channel_id][format_date(strtotime("-{$i} days"), 'custom', 'Y-m-d')] = 0;
      }
    }

    // Query the database to get counts per channel, per day for the last week.
    $query = $this->database->select('event');
    $query->addField('event', 'channel');
    $query->addExpression('DATE(FROM_UNIXTIME(created))', 'createdDate');
    $query->addExpression('COUNT(id)', 'eventCount');
    $query->condition('user_id', $this->getCurrentUserId());
    $query->condition('created', strtotime('midnight', strtotime("-{$days} days")), '>');
    $query->groupBy('channel');
    $query->groupBy('DATE(FROM_UNIXTIME(created))');

    // Execute the query.
    $results = $query->execute();

    // Iterate the results.
    foreach ($results as $result) {
      $counts[$result->channel][$result->createdDate] = (int) $result->eventCount;
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
    $query->condition('event.user_id', $this->getCurrentUserId());
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
      $query->condition('user_id', $this->getCurrentUserId());
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
    return $this->getChannelEntityCounts('event');
  }

  /**
   * Get alert counts per-channel.
   *
   * @return array
   *   An array of alert counts, keyed by channel ID.
   */
  public function getChannelAlertCounts() {
    return $this->getChannelEntityCounts('alert');
  }

  /**
   * Get entity counts per-channel of the current user's channels.
   *
   * @param string $entity_type_id
   *   The entity type ID to count within each of the current user's channels.
   *
   * @return array
   *   An array of entity counts, keyed by channel ID.
   */
  private function getChannelEntityCounts($entity_type_id) {
    $counts = [];

    // Load the user channels.
    $channels = $this->getUserChannels();

    // Stop if there are no channels.
    if (!$channels) {
      return [];
    }

    // Query to find the counts.
    $query = $this->database->select($entity_type_id);
    $query->addField($entity_type_id, 'channel');
    $query->addExpression('COUNT(id)', 'count');
    $query->condition("{$entity_type_id}.user_id", $this->getCurrentUserId());
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
