<?php

declare(strict_types = 1);

namespace Drupal\solution\Entity;

use Drupal\collection\Entity\CollectionInterface;
use Drupal\collection\Exception\MissingCollectionException;
use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\joinup_group\Exception\MissingGroupException;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfInterface;

/**
 * Entity subclass for the 'solution' bundle.
 */
class Solution extends Rdf implements SolutionInterface {

  use JoinupBundleClassFieldAccessTrait;

  /**
   * {@inheritdoc}
   */
  public function getCollection(): CollectionInterface {
    try {
      /** @var \Drupal\collection\Entity\CollectionInterface $group */
      $group = $this->getGroup();
    }
    catch (MissingGroupException $exception) {
      throw new MissingCollectionException("Solution {$exception->getEntity()->id()} missing a parent collection.", 0, $exception);
    }
    return $group;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup(): RdfInterface {
    $field_item = $this->getFirstItem('collection');
    if ($field_item->isEmpty()) {
      throw (new MissingGroupException())->setEntity($this);
    }
    return $field_item->entity;
  }

}
