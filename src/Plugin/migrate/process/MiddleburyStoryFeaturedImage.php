<?php

namespace Drupal\middlebury_newsroom_migration\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\middlebury_newsroom_migration\MediaCreator;

/**
 * This plugin attempts to migrate the upper right content into an image entity.
 *
 * @MigrateProcessPlugin(
 *   id = "middlebury_story_featured_image"
 * )
 */
class MiddleburyStoryFeaturedImage extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The media creator.
   *
   * @var \Drupal\middlebury_newsroom_migration\MediaCreator
   */
  protected $mediaCreator;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MediaCreator $media_creator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mediaCreator = $media_creator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('middlebury_newsroom_migration.media_creator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $url = $title = $alt = '';

    $caption = $row->getSourceProperty('field_altcaption');
    if (!empty($caption) && is_array($caption)) {
      $caption = $caption[0]['value'];
    }
    else {
      $caption = '';
    }

    $imageurl = $row->getSourceProperty('field_imageurl');
    if (!empty($imageurl) && is_array($imageurl)) {
      $url = $imageurl[0]['value'];
    }
    else {
      $media = $row->getSourceProperty('field_news_image');
      if (!empty($media) && is_array($media)) {
        $mm_media = Database::getConnection('default', 'migrate')->query('SELECT n.title as title, m.field_multimedia_fid as field_multimedia_fid, b.body_value as body_value FROM {node} n JOIN {field_data_field_multimedia} m ON (n.nid = m.entity_id) JOIN {field_data_body} b ON (n.nid = b.entity_id) WHERE n.nid=:nid', [':nid' => $media[0]['nid']])->fetchObject();

        if (!empty($mm_media->field_multimedia_fid) && is_numeric($mm_media->field_multimedia_fid)) {
          $url = $this->fetchSourceFromFid($mm_media->field_multimedia_fid);
        }
        else {
          return NULL;
        }

        if (!empty($mm_media->title) && is_string($mm_media->title)) {
          $title = $mm_media->title;
        }

        if (!empty($mm_media->body_value) && is_string($mm_media->body_value)) {
          $caption = $mm_media->body_value;
        }
      }
      else {
        return NULL;
      }
    }

    try {
      $file = $this->mediaCreator->createFile($url);
      return $this->mediaCreator->createImage($file, $caption, $title, $alt);
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Fetches the source path of a mm_media file based on a given fid.
   *
   * @param string $fid
   *   The File ID parsed from the mm_media shortcode.
   *
   * @return string
   *   The URL of the file.
   */
  private function fetchSourceFromFid($fid) {
    $file_record = Database::getConnection('default', 'migrate')->query('SELECT * FROM {file_managed} WHERE fid=:fid', [':fid' => $fid])->fetchObject();

    // Manually add the system path to fetch file from remote location.
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

}
