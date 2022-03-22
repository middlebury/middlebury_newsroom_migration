<?php

namespace Drupal\middlebury_newsroom_migration\Plugin\migrate\html\parser;

use Drupal\migrate_html_to_paragraphs\Plugin\migrate\html\parser\HtmlTagParser;

/**
 * Migration HTML - img parser.
 *
 * @MigrateHtmlParserPlugin(
 *   id = "middlebury_html_parser_video"
 * )
 */
class MiddleburyVideoParser extends HtmlTagParser {

  /**
   * {@inheritdoc}
   */
  protected function definePattern() {
    return '/\[video[^>]*\]/iSu';
  }

  /**
   * {@inheritdoc}
   */
  protected function parseTag($tag) {
    $data = [
      'type' => 'video',
      'tag' => $tag,
      'image' => $this->parseTagImage($tag),
      'url' => $this->parseTagUrl($tag),
    ];

    return $data;
  }

  /**
   * Helper to parse the title from the img tag.
   *
   * @param string $tag
   *   The img tag.
   *
   * @return int|null
   *   Returns the title or NULL if not found.
   */
  protected function parseTagImage($tag) {
    return $this->parseTagByPattern($tag, '/image:([^\s]*)/iSu');
  }

  /**
   * Helper to parse the src from the img tag.
   *
   * @param string $tag
   *   The img tag.
   *
   * @return string|null
   *   The parsed source (src).
   */
  protected function parseTagUrl($tag) {
    return $this->parseTagByPattern($tag, '/video:([^\s\]]*)/iSu');
  }

  /**
   * {@inheritdoc}
   */
  public function getCorrespondingProcessorPluginId() {
    return 'middlebury_html_process_video';
  }

}
