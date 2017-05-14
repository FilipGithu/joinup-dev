<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Provides a distribution migration source plugin.
 *
 * @MigrateSource(
 *   id = "distribution"
 * )
 */
class Distribution extends DistributionBase {

  use FileUrlFieldTrait;
  use StatusTrait;

  /**
   * {@inheritdoc}
   */
  protected $reservedUriTables = ['collection', 'solution', 'release'];

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'uri' => $this->t('URI'),
      'title' => $this->t('Name'),
      'access_url' => $this->t('Access URL'),
      'created_time' => $this->t('Created time'),
      'body' => $this->t('Description'),
      'licence' => $this->t('Licence'),
      'changed_time' => $this->t('Changed time'),
      'technique' => $this->t('Representation technique'),
      'status' => $this->t('Status'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return parent::query()->fields('d', [
      'uri',
      'vid',
      'title',
      'body',
      'created_time',
      'changed_time',
      'licence',
      'file_id',
      'access_url',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $nid = $row->getSourceProperty('nid');
    $vid = $row->getSourceProperty('vid');

    // Representation technique.
    $query = $this->select('term_node', 'tn');
    $query->join('term_data', 'td', 'tn.tid = td.tid');
    $representation_technique = $query
      ->fields('td', ['name'])
      ->condition('tn.nid', $nid)
      ->condition('tn.vid', $vid)
      // The representation technique vocabulary vid is 70.
      ->condition('td.vid', 70)
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('technique', $representation_technique);

    // Resolve 'access_url'.
    $file_source_id_values = $row->getSourceProperty('file_id') ? [['nid' => $nid]] : [];
    $this->setFileUrlTargetId($row, 'access_url', $file_source_id_values, 'distribution_file', 'access_url');

    // Status.
    $this->setStatus($vid, $row);

    return parent::prepareRow($row);
  }

}
