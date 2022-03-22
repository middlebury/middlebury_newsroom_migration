<?php

namespace Drupal\middlebury_newsroom_migration\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;

/**
 * Drupal 7 node source from database, altered to not publish recycled nodes.
 *
 * @MigrateSource(
 *   id = "middlebury_newsroom_nodes",
 *   source_module = "node"
 * )
 */
class MiddleburyNewsroomNodes extends FieldableEntity {

  /**
   * The join options between the node and the node_revisions table.
   */
  const JOIN = 'n.vid = nr.vid';

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Select node in its last revision.
    $query = $this->select('node_revision', 'nr')
      ->fields('n', [
        'nid',
        'type',
        'language',
        'status',
        'created',
        'changed',
        'comment',
        'promote',
        'sticky',
        'tnid',
        'translate',
      ])
      ->fields('nr', [
        'vid',
        'title',
        'log',
        'timestamp',
      ]);
    $query->addField('n', 'uid', 'node_uuid');
    $query->addField('nr', 'uid', 'revision_uid');
    $query->innerJoin('node', 'n', static::JOIN);

    if (isset($this->configuration['node_type'])) {
      $query->condition('n.type', $this->configuration['node_type']);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Get Field API field values.
    foreach (array_keys($this->getFields('node', $row->getSourceProperty('type'))) as $field) {
      $nid = $row->getSourceProperty('nid');
      $vid = $row->getSourceProperty('vid');
      $row->setSourceProperty($field, $this->getFieldValues('node', $field, $nid, $vid));
    }

    if ($this->isRecycled($nid)) {
      return FALSE;
    }

    if (!$this->isNewsroom($nid)) {
      return FALSE;
    }

    // Make sure we always have a translation set.
    if ($row->getSourceProperty('tnid') == 0) {
      $row->getSourceProperty('tnid', $row->getSourceProperty('nid'));
    }
    return parent::prepareRow($row);
  }

  /**
   * This checks if a node in the legacy database is recycled.
   */
  private function isRecycled($nid) {
    $query = $this->select('mm_recycle', 'r')
      ->fields('r', ['id'])
      ->condition('r.id', $nid);

    return $query->execute()->fetchObject();
  }

  /**
   * This checks if a node in the legacy database is in the newsroom.
   */
  private function isNewsroom($nid) {
    $query = $this->select('mm_node2tree', 't');
    $query->join('mm_tree_parents', 'p', 't.mmtid = p.mmtid');
    $query
      ->fields('t', ['nid'])
      ->condition('t.nid', $nid)
      ->condition('p.parent', '73507');

    return $query->execute()->fetchObject();
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'nid' => $this->t('Node ID'),
      'type' => $this->t('Type'),
      'title' => $this->t('Title'),
      'node_uid' => $this->t('Node authored by (uid)'),
      'revision_uid' => $this->t('Revision authored by (uid)'),
      'created' => $this->t('Created timestamp'),
      'changed' => $this->t('Modified timestamp'),
      'status' => $this->t('Published'),
      'promote' => $this->t('Promoted to the front page'),
      'sticky' => $this->t('Sticky at top of lists'),
      'revision' => $this->t('Crate new revision'),
      'language' => $this->t('Language (fr, en, ...)'),
      'tnid' => $this->t('The translation set id for this node'),
      'timestamp' => $this->t('The timestamp the latest revision of this node was created.'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['nid']['type'] = 'integer';
    $ids['nid']['alias'] = 'n';
    return $ids;
  }

}
