<?php

namespace Drupal\middlebury_newsroom_migration\Plugin\migrate\html\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\migrate_html_to_paragraphs\Plugin\migrate\html\process\ImgProcess;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\media\Entity\Media;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\File\FileSystemInterface;

/**
 * Migration HTML - Image processor.
 *
 * @MigrateHtmlProcessPlugin(
 *   id = "middlebury_html_process_img"
 * )
 */
class MiddleburyImgProcess extends ImgProcess implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TranslationInterface $stringTranslation) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setStringTranslation($stringTranslation);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function createParagraph($value) {
    if ($this->getFileId()) {
      $file = $this->getFile();

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
        'type' => $this->getBundle(),
        $this->getFieldName() => [
          'target_id' => $image->id(),
          'alt' => $this->getAlt(),
          'title' => $this->getTitle(),
        ],
      ]);
      $paragraph->save();

      return $paragraph;
    }

    return NULL;
  }

  /**
   * Create a file entity.
   *
   * @param string $source
   *   The uri of the file that needs to be created, assuming that there is
   *   no need to copy the actual file.
   * @param string $target_folder
   *   The target directory URI where the file should be copied to.
   * @param int $replace
   *   (optional) The replace behavior when the destination file already exists.
   *   Possible values include:
   *   - FileSystemInterface::EXISTS_REPLACE: Replace the existing file. If a
   *     managed file with the destination name exists, then its database entry
   *     will be updated. If no database entry is found, then a new one will be
   *     created.
   *   - FileSystemInterface::EXISTS_RENAME: (default) Append
   *     _{incrementing number} until the filename is unique.
   *   - FileSystemInterface::EXISTS_ERROR: Do nothing and return FALSE.
   *
   * @return \Drupal\file\FileInterface|false
   *   The file entity object or false if the file could not be created.
   */
  protected function createFileByUri($source, $target_folder, $replace = FileSystemInterface::EXISTS_RENAME) {
    // Check if the file isn't already migrated.
    if ($existing = $this->loadFileFromMigrateMapping($source)) {
      if (is_subclass_of($existing, 'Drupal\file\FileInterface')) {
        return $existing;
      }
    }

    // Create file object from remote URL.
    try {
      // Using HTTP-client in case of using proxy server.
      $client = \Drupal::httpClient();
      $request = $client->get($source);
      $data = $request->getBody()->getContents();
      $file_name = \Drupal::service('file_system')->basename($source);

      // Patch added by Adam Franco. 2018-01-12.
      // Clean the filename.
      $file_name = urldecode(rawurldecode($file_name));
      // Trim off any URL params.
      $file_name = preg_replace('/\?.*$/', '', $file_name);
      $base = pathinfo($file_name, PATHINFO_FILENAME);
      $extension = pathinfo($file_name, PATHINFO_EXTENSION);
      $base = preg_replace('/[^a-z0-9_\.-]/i', '_', $base);
      // Shorten multiple underscores.
      $base = preg_replace('/_+/', '_', $base);
      // Remove leading & trailing underscores.
      $base = preg_replace('/^_|_$/', '', $base);
      $file_name = $base . '.' . $extension;

      $file = $this->createFile($data, $target_folder . '/' . $file_name, $replace);
    }
    catch (\Exception $e) {
      $file = FALSE;
      $this->logMessage(
        $this->t('Unable to create file from source @source', ['@source' => $source]),
        MigrationInterface::MESSAGE_ERROR
      );
    }

    // HTML inline file migrate mapping.
    $this->saveMigrateMapping($source, $file);

    return $file;
  }

}
