<?php

namespace Drupal\middlebury_newsroom_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * This plugin attempts to determine whether a story is a video.
 *
 * @MigrateProcessPlugin(
 *   id = "middlebury_story_is_video"
 * )
 */
class MiddleburyStoryIsVideo extends MiddleburyStoryShowFeaturedImage {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    return !(parent::transform($value, $migrate_executable, $row, $destination_property));
  }

}
