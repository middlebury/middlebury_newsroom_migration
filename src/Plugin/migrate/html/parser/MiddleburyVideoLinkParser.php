<?php

namespace Drupal\middlebury_newsroom_migration\Plugin\migrate\html\parser;

use Drupal\migrate_html_to_paragraphs\Plugin\migrate\html\parser\HtmlTagParser;

/**
 * Migration HTML - img parser.
 *
 * @MigrateHtmlParserPlugin(
 *   id = "middlebury_html_parser_videolink"
 * )
 */
class MiddleburyVideoLinkParser extends HtmlTagParser {

  /**
   * {@inheritdoc}
   */
  protected function definePattern() {
    return '/<a[^>]* href="[^"]*(youtube\.com|vimeo\.com|middmedia\.middlebury\.edu)[^"]*"[^>]*>.+<\/a>/iSu';
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
      'title' => $this->parseTagTitle($tag),
      'alt' => $this->parseTagAlt($tag),
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
    return $this->parseTagByPattern($tag, '/src="([^"]*)"/iSu');
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
    $url = $this->parseTagByPattern($tag, '/href="([^"]*)"/iSu');
    // Convert to 'watch' urls.
    if (preg_match('/https?:\/\/www.youtube.com\/v\/([^&"]+)/', $url, $m)) {
      $url = 'https://www.youtube.com/watch?v=' . $m[1];
    }
    return $url;
  }

  /**
   * Helper to parse the title from the a tag.
   *
   * @param string $tag
   *   The a tag.
   *
   * @return string|null
   *   Returns the title or NULL if not found.
   */
  protected function parseTagTitle($tag) {
    return $this->parseTagByPattern($tag, '/title="([^"]*)"/iSu');
  }

  /**
   * Helper to parse the alt-text from the a tag.
   *
   * @param string $tag
   *   The a tag.
   *
   * @return string|null
   *   Returns the title or NULL if not found.
   */
  protected function parseTagAlt($tag) {
    return $this->parseTagByPattern($tag, '/alt="([^"]*)"/iSu');
  }

  /**
   * {@inheritdoc}
   */
  public function getCorrespondingProcessorPluginId() {
    return 'middlebury_html_process_video';
  }

}
