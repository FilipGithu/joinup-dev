<?php

namespace Drupal\joinup_federation_test\Plugin\pipeline\Pipeline;

use Drupal\joinup_federation\JoinupFederationPipelinePluginBase;

/**
 * Provides a pipline testing plugin.
 *
 * @PipelinePipeline(
 *   id = "joinup_federation_invalid_testing_pipeline",
 *   label = @Translation("Joinup federation invalid testing pipeline"),
 *   steps = {},
 * )
 */
class JoinupFederationInvalidTestingPipeline extends JoinupFederationPipelinePluginBase {

  /**
   * Allows the test to override the steps defined in annotation.
   *
   * @param array $steps
   *   Associative array keyed by step plugin ID and having the plugin
   *   configuration as values.
   */
  public function setSteps(array $steps) {
    $this->pluginDefinition['steps'] = $steps;
  }

  /**
   * {@inheritdoc}
   */
  public function getCollection(): ?string {
    return 'http://invalid-collection-id';
  }

}