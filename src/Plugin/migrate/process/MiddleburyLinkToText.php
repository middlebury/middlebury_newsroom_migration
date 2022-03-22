<?php

namespace Drupal\middlebury_newsroom_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Change link fields into text fields.
 *
 * @MigrateProcessPlugin(
 *   id = "middlebury_link_process_text"
 * )
 */
class MiddleburyLinkToText extends ProcessPluginBase {

  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $html = $value;

    if (is_array($value)) {
      $html = '';

      for ($i = 0; $i < count($value); $i++) {
        $link = $value[$i];
        $html .= '<a href="' . $link['url'] . '">' . $link['title'] . '</a>';

        if ($i != count($value) - 1) {
          $html .= "<br><br>";
        }
      }
    }

    return $html;
  }

}
