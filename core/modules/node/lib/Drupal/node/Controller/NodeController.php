<?php

/**
 * @file
 * Contains \Drupal\node\Controller\NodeController.
 */

namespace Drupal\node\Controller;

use Drupal\Component\Utility\String;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\NodeInterface;

/**
 * Returns responses for Node routes.
 */
class NodeController extends ControllerBase {

  /**
   * @todo Remove node_admin_nodes().
   */
  public function contentOverview() {
    module_load_include('admin.inc', 'node');
    return node_admin_nodes();
  }

  /**
   * @todo Remove node_add_page().
   */
  public function addPage() {
    module_load_include('pages.inc', 'node');
    return node_add_page();
  }

  /**
   * @todo Remove node_add().
   */
  public function add(EntityInterface $node_type) {
    module_load_include('pages.inc', 'node');
    return node_add($node_type);
  }

  /**
   * Displays a node revision.
   *
   * @param int $node_revision
   *   The node revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function revisionShow($node_revision) {
    $node = $this->entityManager()->getStorageController('node')->loadRevision($node_revision);
    $page = $this->buildPage($node);
    unset($page['nodes'][$node->id()]['#cache']);

    return $page;
  }

  /**
   * Page title callback for a node revision.
   *
   * @param int $node_revision
   *   The node revision ID.
   *
   * @return string
   *   The page title.
   */
  public function revisionPageTitle($node_revision) {
    $node = $this->entityManager()->getStorageController('node')->loadRevision($node_revision);
    return $this->t('Revision of %title from %date', array('%title' => $node->label(), '%date' => format_date($node->getRevisionCreationTime())));
  }

  /**
   * @todo Remove node_revision_overview().
   */
  public function revisionOverview(NodeInterface $node) {
    module_load_include('pages.inc', 'node');
    return node_revision_overview($node);
  }

  /**
   * Displays a node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node we are displaying.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  public function page(NodeInterface $node) {
    $build = $this->buildPage($node);

    foreach ($node->uriRelationships() as $rel) {
      $uri = $node->uri($rel);
      // Set the node path as the canonical URL to prevent duplicate content.
      $build['#attached']['drupal_add_html_head_link'][] = array(
        array(
        'rel' => $rel,
        'href' => $this->urlGenerator()->generateFromPath($uri['path'], $uri['options']),
        )
        , TRUE);

      if ($rel == 'canonical') {
        // Set the non-aliased canonical path as a default shortlink.
        $build['#attached']['drupal_add_html_head_link'][] = array(
          array(
            'rel' => 'shortlink',
            'href' => $this->urlGenerator()->generateFromPath($uri['path'], array_merge($uri['options'], array('alias' => TRUE))),
          )
        , TRUE);
      }
    }

    return $build;
  }

  /**
   * The _title_callback for the node.view route.
   *
   * @param NodeInterface $node
   *   The current node.
   *
   * @return string
   *   The page title.
   */
  public function pageTitle(NodeInterface $node) {
    return String::checkPlain($node->label());
  }

  /**
   * Builds a node page render array.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node we are displaying.
   *
   * @return array
   *   An array suitable for drupal_render().
   */
  protected function buildPage(NodeInterface $node) {
    return array('nodes' => $this->entityManager()->getViewBuilder('node')->view($node));
  }

}
