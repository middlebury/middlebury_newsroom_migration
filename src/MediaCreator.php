<?php

namespace Drupal\middlebury_newsroom_migration;

use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\File\FileSystemInterface;

/**
 * Create media entities for migrated files.
 */
class MediaCreator implements MediaCreatorInterface {

  /**
   * The client interface.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Cache of created files.
   *
   * @var array
   */
  protected $createdFiles = [];

  /**
   * Cache of created media entities.
   *
   * @var array
   */
  protected $createdMedia = [];

  /**
   * The Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The File-System service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  protected $fileSystem;

  /**
   * Constructs a new MediaCreator object.
   */
  public function __construct(ClientInterface $http_client, EntityTypeManagerInterface $entity_type_manager, FileSystem $file_system) {
    $this->httpClient = $http_client;
    $this->entityTypeManager = $entity_type_manager;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('entity_type.manager'),
      $container->get('file_system')
    );
  }

  /**
   * Create a file entity for media hosted on the old site.
   *
   * @param string $url
   *   The original file URL.
   *
   * @return \Drupal\file\Entity\File
   *   The resulting file.
   */
  public function createFile($url) {
    if (isset($this->createdFiles[$url])) {
      return $this->entityTypeManager->getStorage('file')->load($this->createdFiles[$url]);
    }
    // Create the file if it doesn't exist yet.
    $response = $this->httpClient->request('GET', $url);
    $directory = 'public://stories/';
    $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);
    // Clean the filename.
    $filename = urldecode(rawurldecode($this->fileSystem->basename($url)));
    // Trim off any URL params.
    $filename = preg_replace('/\?.*$/', '', $filename);
    $base = pathinfo($filename, PATHINFO_FILENAME);
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    $base = preg_replace('/[^a-z0-9_\.-]/i', '_', $base);
    // Shorten multiple underscores.
    $base = preg_replace('/_+/', '_', $base);
    // Remove leading & trailing underscores.
    $base = preg_replace('/^_|_$/', '', $base);
    $filename = $base . '.' . $extension;
    $file = file_save_data($response->getBody(), $directory . $filename, FileSystemInterface::EXISTS_REPLACE);
    $file->setMimeType($response->getHeader('Content-Type')[0]);
    $file->setFilename($filename);
    // Record the id for reuse.
    $this->createdFiles[$url] = $file->id();
    return $file;
  }

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
  public function createImage(File $file, $caption, $title, $alt) {
    // Run existing filter through caption field.
    check_markup($caption, 'blurb_html');

    if (empty($title)) {
      $title = $file->getFilename();
    }
    // Reuse the media file if it has already been created.
    if (isset($this->createdMedia[$file->id()])) {
      return Media::load($this->createdMedia[$file->id()]);
    }
    // Create an image media entity.
    $image_entity = Media::create([
      'name' => $file->getFilename(),
      'bundle' => 'image',
      'field_caption' => [
        'value' => $caption,
        'format' => 'blurb_html',
      ],
      'field_credit' => NULL,
      'field_image' => [
        'target_id' => $file->id(),
        'alt' => $alt,
        'title' => $title,
      ],
    ]);
    $image_entity->save();
    $this->createdMedia[$file->id()] = $image_entity->id();

    return $image_entity;
  }

}
