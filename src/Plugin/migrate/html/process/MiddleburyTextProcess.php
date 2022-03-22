<?php

namespace Drupal\middlebury_newsroom_migration\Plugin\migrate\html\process;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate_html_to_paragraphs\Plugin\migrate\html\process\TextProcess;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\middlebury_newsroom_migration\MediaCreator;
use GuzzleHttp\Exception\ClientException;
use Drupal\migrate\MigrateException;
use Drupal\Core\Url;

/**
 * Migration HTML - text processor.
 *
 * @MigrateHtmlProcessPlugin(
 *   id = "middlebury_html_process_text"
 * )
 */
class MiddleburyTextProcess extends TextProcess implements ContainerFactoryPluginInterface {

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
  public function createParagraph($value) {
    $text_format = $this->getTextFormat();

    // Cleanup HTML, ugly things which the text formats do not filter out.
    // Replace non breaking space to spaces.
    // =====================================
    // In order to be handled correctly in the next steps.
    $value = str_ireplace('&nbsp;', ' ', $value);

    // Remove commonly used occurrences.
    // =================================
    // E.g.:
    // - align="center"
    // - style="vertical-align: baseline;".
    $value = preg_replace('/(\s(align|style)=".*")/i', '', $value);

    // Clean the value so we can check if it is empty.
    $value = check_markup($value, $text_format);

    // Copy and update file-links.
    if (preg_match_all('#href=["\']([^"\']*(system/files|media/view)[^"\']*)["\']#i', $value, $link_matches, PREG_SET_ORDER)) {
      foreach ($link_matches as $link_match) {
        // Verify that this is a local URL and not to another site that might
        // run Drupal.
        if (preg_match('/sledge\.middlebury\.edu|www\.middlebury\.edu/', $link_match[1])) {
          // Download the file and replace the url.
          try {
            $file = $this->mediaCreator->createFile($link_match[1]);
            $value = str_replace($link_match[1], $file->createFileUrl(), $value);
          }
          catch (ClientException $e) {
            $this->logMessage($e->getMessage(), MigrationInterface::MESSAGE_ERROR);
          }
        }
      }
    }

    // Remove commonly used HTML tags which are empty (useless).
    // =========================================================
    // E.g.:
    // - <p></p>
    // - <div></div>
    // - <strong></strong>.
    $html_tags = '(a|b|blockquote|div|em|h1|h2|h3|h4|h5|h6|label|li|ol|p|span|strong|ul)';
    $value = preg_replace('/<' . $html_tags . '>(\s|&nbsp;)*<\/' . $html_tags . '>/i', '', $value);

    // Trim off extra white space.
    $value = trim($value);

    // Ignore empty strings.
    if (empty($value)) {
      return NULL;
    }

    $paragraph = Paragraph::create([
      'id' => NULL,
      'type' => $this->getBundle(),
      $this->getFieldName() => [
        'value' => $value,
        'format' => $text_format,
      ],
    ]);
    $paragraph->save();

    return $paragraph;
  }

}
