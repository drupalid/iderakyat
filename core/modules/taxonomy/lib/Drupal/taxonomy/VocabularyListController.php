<?php

/**
 * @file
 * Contains \Drupal\taxonomy\VocabularyListController.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Config\Entity\DraggableListController;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of vocabularies.
 */
class VocabularyListController extends DraggableListController {

  /**
   * {@inheritdoc}
   */
  protected $entitiesKey = 'vocabularies';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'taxonomy_overview_vocabularies';
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);
    $uri = $entity->uri();

    if (isset($operations['edit'])) {
      $operations['edit']['title'] = t('edit vocabulary');
      $operations['edit']['href'] = $uri['path'] . '/edit';
    }

    $operations['list'] = array(
      'title' => t('list terms'),
      'href' => $uri['path'],
      'options' => $uri['options'],
      'weight' => 0,
    );
    $operations['add'] = array(
      'title' => t('add terms'),
      'href' => $uri['path'] . '/add',
      'options' => $uri['options'],
      'weight' => 10,
    );
    unset($operations['delete']);

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Vocabulary name');
    $header['weight'] = t('Weight');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $this->getLabel($entity);
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $entities = $this->load();
    // If there are not multiple vocabularies, disable dragging by unsetting the
    // weight key.
    if (count($entities) <= 1) {
      unset($this->weightKey);
    }
    $build = parent::render();
    $build['#empty'] = t('No vocabularies available. <a href="@link">Add vocabulary</a>.', array('@link' => url('admin/structure/taxonomy/add')));
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['vocabularies']['#attributes'] = array('id' => 'taxonomy');
    $form['actions']['submit']['#value'] = t('Save');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    parent::submitForm($form, $form_state);

    drupal_set_message(t('The configuration options have been saved.'));
  }

}
