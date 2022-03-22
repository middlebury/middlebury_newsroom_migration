<?php

namespace Drupal\middlebury_newsroom_migration\Plugin\migrate\html\parser;

use Drupal\migrate_html_to_paragraphs\Plugin\migrate\html\parser\ImgParser;

/**
 * Migration HTML - img parser.
 *
 * @MigrateHtmlParserPlugin(
 *   id = "middlebury_html_parser_img"
 * )
 */
class MiddleburyImgParser extends ImgParser {

  /**
   * {@inheritdoc}
   */
  protected function definePattern() {
    // Include wrapper link-tags in our pattern so that we can avoid
    // duplicating images that are part of video-links.
    return '/(<a.*>)?(<img[^>]*>)(<\/a>)?/iSu';
  }

  /**
   * {@inheritdoc}
   */
  public function getCorrespondingProcessorPluginId() {
    return 'middlebury_html_process_img';
  }

  /**
   * {@inheritdoc}
   */
  protected function parseTag($tag) {
    // Extract just the image-tag portion.
    preg_match($this->definePattern(), $tag, $matches);
    $img_tag = $matches[2];
    $data = parent::parseTag($img_tag);
    $data['link_tag'] = $matches[1];

    // Ignore icons for file-links as these shouldn't create new image
    // paragraphs.
    if (preg_match('#^common/2010/images/icons/#i', $data['src'])) {
      return NULL;
    }

    // Skip images that are part of video links -- these will be picked up in
    // the video paragraph.
    if (preg_match('/open_video|youtube|vimeo|middmedia/i', $data['link_tag'])) {
      return NULL;
    }

    return $data;
  }

}
