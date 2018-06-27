<?php

namespace Drupal\pipeline;

/**
 * Reusable code for pipeline step plugins that are running in batch process.
 *
 * @see \Drupal\pipeline\Plugin\PipelineStepWithBatchInterface
 */
trait PipelineStepWithBatchTrait {

  /**
   * {@inheritdoc}
   */
  public function setBatchValue($key, $value) {
    $this->pipeline->getCurrentState()->setBatchValue($key, $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBatchValue($key) {
    return $this->pipeline->getCurrentState()->getBatchValue($key);
  }

  /**
   * {@inheritdoc}
   */
  public function onBatchProcessCompleted() {
    // Ensure the method with no action for all steps.
    return $this;
  }

}
