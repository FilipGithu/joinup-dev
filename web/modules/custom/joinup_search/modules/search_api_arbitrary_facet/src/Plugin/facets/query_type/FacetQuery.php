<?php

declare(strict_types = 1);

namespace Drupal\search_api_arbitrary_facet\Plugin\facets\query_type;

use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\facets\Plugin\facets\query_type\SearchApiString;
use Drupal\facets\Result\Result;
use Drupal\search_api_arbitrary_facet\Plugin\ArbitraryFacetInterface;
use Drupal\search_api_arbitrary_facet\Plugin\ArbitraryFacetManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides supports for facets generated by arbitrary queries.
 *
 * @see https://wiki.apache.org/solr/SimpleFacetParameters#facet.query_:_Arbitrary_Query_Faceting
 *
 * @FacetsQueryType(
 *   id = "facet_query",
 *   label = @Translation("Arbitrary facet query"),
 * )
 */
class FacetQuery extends SearchApiString implements ContainerFactoryPluginInterface {

  /**
   * The arbitrary facet plugin manager.
   *
   * @var \Drupal\search_api_arbitrary_facet\Plugin\ArbitraryFacetManager
   */
  protected $arbitraryFacetPluginManager;

  /**
   * The arbitrary facet plugin instance that is used in this plugin.
   *
   * @var \Drupal\search_api_arbitrary_facet\Plugin\ArbitraryFacetInterface
   */
  protected $arbitraryFacetPluginInstance;

  /**
   * Constructs a FacetQuery object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\search_api_arbitrary_facet\Plugin\ArbitraryFacetManager $arbitrary_facet_plugin_manager
   *   The arbitrary facet plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ArbitraryFacetManager $arbitrary_facet_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->arbitraryFacetPluginManager = $arbitrary_facet_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.arbitrary_facet')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    $query = $this->query;
    if (empty($query)) {
      return;
    }

    // Set the options for the actual query.
    $options = &$query->getOptions();

    $operator = $this->facet->getQueryOperator();
    $field_identifier = $this->facet->getFieldIdentifier();
    $exclude = $this->facet->getExclude();
    $options['search_api_arbitrary_facet'][$field_identifier] = [
      'limit' => $this->facet->getHardLimit(),
      'operator' => $operator,
      'min_count' => $this->facet->getMinCount(),
      'missing' => FALSE,
      'arbitrary_facet_plugin' => $this->getArbitraryFacetPluginId(),
    ];
    // Add the filter to the query if there are active values.
    $active_items = $this->facet->getActiveItems();
    $facet_definition = $this->getArbitraryFacetDefinition();
    if (count($active_items)) {
      $filter = $query->createConditionGroup($operator, ['arbitrary:' . $field_identifier]);

      foreach ($active_items as $active_item) {
        if (!isset($facet_definition[$active_item])) {
          throw new \Exception("Unknown active item: " . $active_item);
        }
        $active_filter = $facet_definition[$active_item];
        $field_name = $active_filter['field_name'];
        $condition = $active_filter['field_condition'];
        $operator = isset($active_filter['field_operator']) ? $active_filter['field_operator'] : NULL;
        $exclude = $exclude ? '<>' : '=';
        $filter->addCondition($field_name, $condition, $operator ?: $exclude);
      }
      $query->addConditionGroup($filter);
    }

    // Add the cacheability metadata of the facet to the query.
    $facet = $this->getArbitraryFacetPluginInstance();
    if ($query instanceof RefinableCacheableDependencyInterface && $facet instanceof CacheableDependencyInterface) {
      $query->addCacheableDependency($facet);
    }
  }

  /**
   * Returns the definition of the arbitrary facet selected in the facet widget.
   *
   * @return array
   *   The facet definition.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   Thrown when the plugin that is selected in the system does not exist.
   */
  protected function getArbitraryFacetDefinition(): array {
    return $this->getArbitraryFacetPluginInstance()->getFacetDefinition();
  }

  /**
   * Returns an instance of the arbitrary facet selected in the facet widget.
   *
   * @return \Drupal\search_api_arbitrary_facet\Plugin\ArbitraryFacetInterface
   *   The arbitrary facet plugin instance.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   Thrown when the plugin that is selected in the system does not exist.
   */
  protected function getArbitraryFacetPluginInstance(): ArbitraryFacetInterface {
    if (empty($this->arbitraryFacetPluginInstance)) {
      $this->arbitraryFacetPluginInstance = $this->arbitraryFacetPluginManager->createInstance($this->getArbitraryFacetPluginId());
    }
    return $this->arbitraryFacetPluginInstance;
  }

  /**
   * Returns the plugin id selected in the widget.
   *
   * @return string
   *   The plugin id of the arbitrary facet type in use.
   */
  protected function getArbitraryFacetPluginId(): string {
    return $this
      ->facet
      ->getWidgetInstance()
      ->getConfiguration()['arbitrary_facet_plugin'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $query_operator = $this->facet->getQueryOperator();
    $facet_definition = $this->getArbitraryFacetDefinition();

    if (!empty($this->results)) {
      $facet_results = [];
      foreach ($this->results as $result) {
        $result_filter = trim($result['filter'], '"');
        if (!isset($facet_definition[$result_filter])) {
          continue;
        }
        if ($result['count'] || $query_operator == 'or') {
          $count = $result['count'];
          $label = $facet_definition[$result_filter]['label'];
          $result = new Result($this->facet, $result_filter, $label, $count);
          $facet_results[] = $result;
        }
      }
      $this->facet->setResults($facet_results);
    }

    return $this->facet;
  }

}
