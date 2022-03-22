<?php

namespace Drupal\middlebury_newsroom_migration\Plugin\migrate\html\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate_html_to_paragraphs\Plugin\migrate\html\process\HtmlTagProcess;

/**
 * Migration HTML - A fallback processor that can drop content not matched.
 *
 * @MigrateHtmlProcessPlugin(
 *   id = "middlebury_html_process_null"
 * )
 */
class MiddleburyNullProcess extends HtmlTagProcess {

  /**
   * {@inheritdoc}
   */
  public function process(MigrateExecutableInterface $migrate_executable, array $tag) {
    $this->migrateExecutable = $migrate_executable;
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function createParagraph($value) {
    return NULL;
  }

}
