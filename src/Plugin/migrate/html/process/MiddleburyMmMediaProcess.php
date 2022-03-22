<?php

namespace Drupal\middlebury_newsroom_migration\Plugin\migrate\html\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate_html_to_paragraphs\Plugin\migrate\html\process\HtmlTagProcess;
use Drupal\media\Entity\Media;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\File\FileSystemInterface;

/**
 * Migration HTML - mm_media processor.
 *
 * @MigrateHtmlProcessPlugin(
 *   id = "middlebury_html_process_mm_media"
 * )
 */
class MiddleburyMmMediaProcess extends HtmlTagProcess {

  /**
   * The fid attribute of the mm_media shortcode.
   *
   * @var string|null
   */
  protected $fid = NULL;

  /**
   * The type attribute of the mm_media shortcode.
   *
   * @var string|null
   */
  protected $type = NULL;

  /**
   * The alt attribute of the mm_media shortcode.
   *
   * @var string|null
   */
  protected $alt = NULL;

  /**
   * {@inheritdoc}
   */
  public function process(MigrateExecutableInterface $migrate_executable, array $tag) {
    $this->migrateExecutable = $migrate_executable;

    // Processing the fid attribute.
    if (isset($tag['fid']) && !empty($tag['fid'])) {
      $this->setFid($tag['fid']);
    }

    // Processing the type attribute.
    if (isset($tag['type']) && !empty($tag['type'])) {
      $this->setType($tag['type']);
    }

    // Processing the alt attribute.
    if (isset($tag['alt']) && !empty($tag['alt'])) {
      $this->setAlt($tag['alt']);
    }

    // Processing the title attribute.
    if (isset($tag['title']) && !empty($tag['title'])) {
      $this->setTitle($tag['title']);
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function createParagraph($value) {

    if ($this->getType() == 'media') {

      $fid = $this->getFid();
      $url = $this->getFilePath($fid);

      if ($data = file_get_contents($url)) {

        @$directory = 'public://stories/';

        \Drupal::service('file_system')->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

        $file = file_save_data($data, $directory . basename($url), FileSystemInterface::EXISTS_REPLACE);

        if ($file) {
          $file->setMimeType('image/jpeg');

          $file->setFilename(basename($url));

          // Create an image media entity.
          $image = Media::create([
            'name' => $file->getFilename(),
            'bundle' => 'image',
            'field_caption' => NULL,
            'field_credit' => NULL,
            'field_image' => [
              'target_id' => $file->id(),
              'alt' => $this->getAlt(),
              'title' => $file->getFilename(),
            ],
          ]);
          $image->save();

          $paragraph = Paragraph::create([
            'id' => NULL,
            'type' => 'image',
            'field_image' => [
              'target_id' => $image->id(),
            ],
          ]);
          $paragraph->save();

          return $paragraph;
        }
      }
    }

  }

  /**
   * Look up a file path by its fid.
   *
   * @param int $fid
   *   The fid from the mm_media tag.
   *
   * @return string
   *   Url of the file path.
   */
  private function getFilePath($fid) {
    $file_record = Database::getConnection('default', 'migrate')->query('SELECT * FROM {file_managed} WHERE fid=:fid', [':fid' => $fid])->fetchObject();

    $parsed_url = parse_url($file_record->uri);

    // Manually add the system path to fetch file from remote location.
    $url = "https://www.middlebury.edu/system/files/";
    if (!isset($parsed_url['path'])) {
      $url .= str_replace("%2F", "/", urlencode($parsed_url['host']));
    }
    elseif (isset($parsed_url['host']) && isset($parsed_url['path'])) {
      $url .= $parsed_url['host'] . str_replace("%2F", "/", urlencode($parsed_url['path']));
    }

    return $url;
  }

  /**
   * Set the fid attribute value.
   *
   * @param string $fid
   *   Fid attribute.
   */
  protected function setFid($fid) {
    $this->fid = $fid;
  }

  /**
   * Return the fid attribute value.
   *
   * @return string|null
   *   Fid attribute or null if not set.
   */
  public function getFid() {
    return $this->fid;
  }

  /**
   * Set the type attribute value.
   *
   * @param string $type
   *   Type attribute.
   */
  protected function setType($type) {
    $this->type = $type;
  }

  /**
   * Return the type attribute value.
   *
   * @return string|null
   *   Type attribute or null if not set.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Set the alt attribute value.
   *
   * @param string $alt
   *   Alt attribute.
   */
  protected function setAlt($alt) {
    $this->alt = $alt;
  }

  /**
   * Return the alt attribute value.
   *
   * @return string|null
   *   Alt attribute or null if not set.
   */
  public function getAlt() {
    return $this->alt;
  }

  /**
   * Set the title attribute value.
   *
   * @param string $title
   *   Title attribute.
   */
  protected function setTitle($title) {
    $this->title = $title;
  }

  /**
   * Return the title attribute value.
   *
   * @return string|null
   *   Title attribute or null if not set.
   */
  public function getTitle() {
    return $this->title;
  }

}
