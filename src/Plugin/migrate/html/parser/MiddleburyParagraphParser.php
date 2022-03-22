<?php

namespace Drupal\middlebury_newsroom_migration\Plugin\migrate\html\parser;

use Drupal\migrate_html_to_paragraphs\Plugin\migrate\html\parser\HtmlTagParser;

/**
 * Migration HTML - paragraph parser.
 *
 * @MigrateHtmlParserPlugin(
 *   id = "middlebury_html_parser_paragraph"
 * )
 */
class MiddleburyParagraphParser extends HtmlTagParser {

  /**
   * {@inheritdoc}
   */
  protected function definePattern() {
    return '/<p[^>]*>.*<\/p>/iSu';
  }

  /**
   * {@inheritdoc}
   */
  protected function parseTag($tag) {
    $data = [
      'type' => 'p',
      'tag' => $tag,
    ];

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getCorrespondingProcessorPluginId() {
    return 'middlebury_html_process_text';
  }

}
