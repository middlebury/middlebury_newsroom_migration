<?php

namespace Drupal\middlebury_newsroom_migration\Plugin\migrate\html\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate_html_to_paragraphs\Plugin\migrate\html\process\HtmlTagImgProcess;
use Drupal\media\Entity\Media;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\middlebury_newsroom_migration\MediaCreator;

/**
 * Migration HTML - text processor.
 *
 * @MigrateHtmlProcessPlugin(
 *   id = "middlebury_html_process_video"
 * )
 */
class MiddleburyVideoProcess extends HtmlTagImgProcess implements ContainerFactoryPluginInterface {

  /**
   * The image attribute of the video tag.
   *
   * @var string|null
   */
  protected $image;

  /**
   * The url attribute of the video tag.
   *
   * @var string|null
   */
  protected $url;

  /**
   * The title attribute of the video tag.
   *
   * @var string|null
   */
  protected $title;

  /**
   * The alt attribute of the video tag.
   *
   * @var string|null
   */
  protected $alt;

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
  public function process(MigrateExecutableInterface $migrate_executable, array $tag) {
    $this->migrateExecutable = $migrate_executable;

    if (isset($tag['image'])) {
      $this->setImage($tag['image']);
    }

    if (isset($tag['url'])) {
      $this->setUrl($tag['url']);
    }

    if (isset($tag['title'])) {
      $this->setTitle($tag['title']);
    }

    if (isset($tag['alt'])) {
      $this->setAlt($tag['alt']);
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function createParagraph($value) {
    $image_id = NULL;
    $caption = '';
    if ($this->getImage()) {
      try {
        $image_file = $this->mediaCreator->createFile($this->getImage());
        $image = $this->mediaCreator->createImage(
          $image_file,
          '',
          $this->getTitle(),
          $this->getAlt()
        );
        $image_id = $image->id();
        $caption = $image->field_caption->value;
      }
      catch (\Exception $e) {
        // Maybe we should report loading errors here...
      }
    }

    // Create a video media entity.
    $video = Media::create([
      'bundle' => 'video',
      'field_video_image' => $image_id,
      'field_media_video_embed_field' => $this->getUrl(),
      // The image caption was previously sourced by
      // MiddleburyStoryFeaturedImage.
      'field_video_blurb' => preg_replace('/<p>|<\/p>/', '', $caption),
    ]);
    $video->save();
    $paragraph = Paragraph::create([
      'id' => NULL,
      'type' => 'video',
      'field_video' => $video->id(),
    ]);
    $paragraph->save();

    return $paragraph;
  }

  /**
   * Return the image attribute value.
   *
   * @return string|null
   *   Image attribute or null if not set.
   */
  public function getImage() {
    return $this->image;
  }

  /**
   * Set the image attribute value.
   *
   * @param string $image
   *   Image attribute.
   */
  protected function setImage($image) {
    $this->image = $image;
  }

  /**
   * Return the url attribute value.
   *
   * @return string|null
   *   Url attribute or null if not set.
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * Set the url attribute value.
   *
   * @param string $url
   *   Url attribute.
   */
  protected function setUrl($url) {
    $this->url = $url;
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
   * Return the alt attribute value.
   *
   * @return string|null
   *   Alt attribute or null if not set.
   */
  public function getAlt() {
    return $this->alt;
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

}
