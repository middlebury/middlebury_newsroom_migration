<?php

namespace Drupal\middlebury_newsroom_migration;

use Drupal\file\Entity\File;

/**
 * A service for managing media importing during a migration.
 */
interface MediaCreatorInterface {

  /**
   * Create a file entity for media hosted on the old site.
   *
   * @param string $url
   *   The original file URL.
   *
   * @return \Drupal\file\Entity\File
   *   The resulting file.
   */
  public function createFile($url);

  /**
   * Create an Image Media entity.
   *
   * @param \Drupal\file\Entity\File $file
   *   The File object.
   * @param string $caption
   *   The value of the caption.
   * @param string $title
   *   The title/name of the file.
   * @param string $alt
   *   The alt-text for the media.
   */
  public function createImage(File $file, $caption, $title, $alt);

}
