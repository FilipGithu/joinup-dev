<?php

declare(strict_types = 1);

namespace Drupal\joinup\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Url;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'AddContentBlock' block.
 *
 * @Block(
 *   id = "add_content_block",
 *   admin_label = @Translation("Add content"),
 *   context = {
 *     "og" = @ContextDefinition("entity", label = @Translation("Organic group"))
 *   }
 * )
 */
class AddContentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The asset release route context service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextProviderInterface
   */
  protected $assetReleaseContext;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a AddContentBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Plugin\Context\ContextProviderInterface $asset_release_context
   *   The asset release route context service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContextProviderInterface $asset_release_context, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->assetReleaseContext = $asset_release_context;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('asset_release.asset_release_route_context'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $links = [];

    /** @var \Drupal\rdf_entity\RdfInterface $group */
    $group = $this->getContext('og')->getContextValue();
    $group_type = $group->bundle();
    $route_parameters = ['rdf_entity' => $group->id()];

    // Add a link to add a custom page.
    $this->addGroupNodeLink($links, $group, 'custom_page');

    if ($group_type === 'collection') {
      $solution_url = Url::fromRoute('solution.collection_solution.add', $route_parameters);
      if ($solution_url->access()) {
        $links['solution'] = [
          '#type' => 'link',
          '#title' => $this->t('Add solution'),
          '#url' => $solution_url,
          '#attributes' => ['class' => ['circle-menu__link']],
        ];
      }
    }

    if ($group_type === 'solution') {
      $release_url = Url::fromRoute('asset_release.solution_asset_release.add', $route_parameters);

      if ($release_url->access()) {
        $links['asset_release'] = [
          '#type' => 'link',
          '#title' => $this->t('Add release'),
          '#url' => $release_url,
          '#attributes' => ['class' => ['circle-menu__link']],
        ];
      }

      $distribution_url = Url::fromRoute('asset_distribution.asset_release_asset_distribution.add', $route_parameters);
      if ($distribution_url->access()) {
        $links['asset_distribution'] = [
          '#type' => 'link',
          '#title' => $this->t('Add distribution'),
          '#url' => $distribution_url,
          '#attributes' => ['class' => ['circle-menu__link']],
        ];
      }
    }

    // 'Add news' link.
    $this->addGroupNodeLink($links, $group, 'news');
    // 'Add discussion' link.
    $this->addGroupNodeLink($links, $group, 'discussion');
    // 'Add document' link.
    $this->addGroupNodeLink($links, $group, 'document');
    // 'Add event' link.
    $this->addGroupNodeLink($links, $group, 'event');

    if (!empty($this->assetReleaseContext)) {
      /** @var \Drupal\Core\Plugin\Context\Context[] $asset_release_contexts */
      $asset_release_contexts = $this->assetReleaseContext->getRuntimeContexts(['asset_release']);
      if ($asset_release_contexts && $asset_release_contexts['asset_release']->hasContextValue()) {
        $distribution_url = Url::fromRoute('asset_distribution.asset_release_asset_distribution.add', [
          'rdf_entity' => $asset_release_contexts['asset_release']->getContextValue()->id(),
        ]);
        if ($distribution_url->access()) {
          $links['asset_distribution'] = [
            '#type' => 'link',
            '#title' => $this->t('Add distribution'),
            '#url' => $distribution_url,
            '#attributes' => ['class' => ['circle-menu__link']],
          ];
        }
      }
    }

    $licence_url = Url::fromRoute('joinup_licence.add');
    if ($licence_url->access()) {
      $links['licence'] = [
        '#title' => $this->t('Add licence'),
        '#url' => $licence_url,
      ];
    }

    // 'Add glossary term' link.
    $this->addGroupNodeLink($links, $group, 'glossary');

    if (empty($links)) {
      return [];
    }

    // Render the links as an unordered list, styled as buttons.
    $build = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
    ];

    foreach ($links as $key => $link) {
      $link += [
        '#type' => 'link',
        '#attributes' => ['class' => ['circle-menu__link']],
      ];
      $build['#items'][$key] = $link;
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    // We need to invalidate the cache whenever the parent group changes since
    // the available options in the add content block depend on certain settings
    // of the parent collection, such as the workflow status and the content
    // creation option.
    /** @var \Drupal\rdf_entity\RdfInterface $group */
    $group = $this->getContext('og')->getContextValue();
    return Cache::mergeTags(parent::getCacheTags(), $group->getCacheTagsToInvalidate());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $context = parent::getCacheContexts();
    // The links are only visible for certain roles on certain collections.
    // Normally cache contexts are added automatically but these links depend on
    // an optional context which we manage ourselves.
    return Cache::mergeContexts($context, [
      'asset_release',
      // We vary by the RDF entity type that is in the current context (asset
      // release, collection or solution) because the options shown in the menu
      // are different for each of these bundles.
      'og_group_context',
      // We vary by OG role since a non-member is not allowed to add content.
      'og_role',
      // We vary by user role since a moderator has the ability to add licenses.
      'user.roles',
    ]);
  }

  /**
   * Adds a link for group node content.
   *
   * @param array $links
   *   The list of links to be uodated.
   * @param \Drupal\rdf_entity\RdfInterface $group
   *   The group entity.
   * @param string $node_type_id
   *   The node type ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown when the user entity plugin definition is invalid.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown when the user entity type is not defined.
   */
  protected function addGroupNodeLink(array &$links, RdfInterface $group, string $node_type_id): void {
    $route_parameters = [
      'rdf_entity' => $group->id(),
      'node_type' => $node_type_id,
    ];

    $page_url = Url::fromRoute('joinup_group.add_content', $route_parameters);
    if ($page_url->access()) {
      /** @var \Drupal\node\NodeTypeInterface $node_type */
      $node_type = $this->entityTypeManager->getStorage('node_type')->load($node_type_id);
      $links[$node_type_id] = [
        '#type' => 'link',
        '#title' => $this->t('Add @label', ['@label' => $node_type->getSingularLabel()]),
        '#url' => $page_url,
        '#attributes' => ['class' => ['circle-menu__link']],
      ];
    }
  }

}
