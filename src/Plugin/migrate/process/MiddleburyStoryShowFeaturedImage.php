<?php

namespace Drupal\middlebury_newsroom_migration\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Show the featured image in full display.
 *
 * This plugin attempts to determine whether a story should show the feature
 * image or if it should be hidden to prefer a video or other primary media.
 *
 * @MigrateProcessPlugin(
 *   id = "middlebury_story_show_featured_image"
 * )
 */
class MiddleburyStoryShowFeaturedImage extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value) || empty($value['target_id'])) {
      return TRUE;
    }
    // Hide the featured image if the first paragraph is a video.
    $paragraph = Paragraph::load($value['target_id']);
    if ($paragraph->getType() == 'video') {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

}
