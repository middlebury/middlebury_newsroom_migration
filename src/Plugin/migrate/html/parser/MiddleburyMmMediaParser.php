<?php

namespace Drupal\middlebury_newsroom_migration\Plugin\migrate\html\parser;

use Drupal\migrate_html_to_paragraphs\Plugin\migrate\html\parser\HtmlTagParser;

/**
 * Migration HTML - mm_media parser.
 *
 * @MigrateHtmlParserPlugin(
 *   id = "middlebury_html_parser_mm_media"
 * )
 */
class MiddleburyMmMediaParser extends HtmlTagParser {

  /**
   * {@inheritdoc}
   */
  protected function definePattern() {
    return '/\[\[[^\]]*\"fid\"[^\]]*\]\]/iSu';
  }

  /**
   * {@inheritdoc}
   */
  protected function parseTag($tag) {
    $data = [
      'type' => 'mm_media',
      'tag' => $tag,
      'fid' => $this->parseTagFid($tag),
      'type' => $this->parseTagType($tag),
      'view_mode' => $this->parseTagViewMode($tag),
      'alt' => $this->parseTagAlt($tag),
      'title' => $this->parseTagTitle($tag),
      'height' => $this->parseTagHeight($tag),
      'width' => $this->parseTagWidth($tag),
      'class' => $this->parseTagClass($tag),
    ];

    return $data;
  }

  /**
   * Helper to parse the fid from the mm_media shortcode.
   *
   * @param string $tag
   *   The mm_media tag.
   *
   * @return string|null
   *   The parsed file id (fid).
   */
  protected function parseTagFid($tag) {
    return $this->parseTagByPattern($tag, '/"fid":"([0-9]+)"/iSu');
  }

  /**
   * Helper to parse the type from the mm_media shortcode.
   *
   * @param string $tag
   *   The mm_media tag.
   *
   * @return string|null
   *   The parsed type.
   */
  protected function parseTagType($tag) {
    return $this->parseTagByPattern($tag, '/"type":"([a-z]+)"/iSu');
  }

  /**
   * Helper to parse the view_mode from the mm_media shortcode.
   *
   * @param string $tag
   *   The mm_media tag.
   *
   * @return string|null
   *   The parsed view mode.
   */
  protected function parseTagViewMode($tag) {
    return $this->parseTagByPattern($tag, '/"view_mode":"([a-z]+)"/iSu');
  }

  /**
   * Helper to parse the alt from the mm_media shortcode.
   *
   * @param string $tag
   *   The mm_media tag.
   *
   * @return string|null
   *   The parsed alt.
   */
  protected function parseTagAlt($tag) {
    return $this->parseTagByPattern($tag, '/"alt":"([^"]*)"/iSu');
  }

  /**
   * Helper to parse the title from the mm_media shortcode.
   *
   * @param string $tag
   *   The mm_media_tag.
   *
   * @return string|null
   *   The parsed title.
   */
  protected function parseTagTitle($tag) {
    return $this->parseTagByPattern($tag, '/"title":"([^"]*)"/iSu');
  }

  /**
   * Helper to parse the height from the mm_media shortcode.
   *
   * @param string $tag
   *   The mm_media tag.
   *
   * @return string|null
   *   The parsed height.
   */
  protected function parseTagHeight($tag) {
    return $this->parseTagByPattern($tag, '/"height":"([0-9]+)"/iSu');
  }

  /**
   * Helper to parse the width from the mm_media shortcode.
   *
   * @param string $tag
   *   The mm_media tag.
   *
   * @return string|null
   *   The parsed width.
   */
  protected function parseTagWidth($tag) {
    return $this->parseTagByPattern($tag, '/"width":"([0-9]+)"/iSu');
  }

  /**
   * Helper to parse the class from the mm_media shortcode.
   *
   * @param string $tag
   *   The mm_media tag.
   *
   * @return string|null
   *   The parsed class.
   */
  protected function parseTagClass($tag) {
    return $this->parseTagByPattern($tag, '/"class":"([^"]*)"/iSu');
  }

  /**
   * {@inheritdoc}
   */
  public function getCorrespondingProcessorPluginId() {
    return 'middlebury_html_process_mm_media';
  }

}
