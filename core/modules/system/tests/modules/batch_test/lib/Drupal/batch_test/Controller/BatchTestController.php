<?php
/**
 * @file
 * Contains \Drupal\batch_test\Controller\BatchTestController.
 */

namespace Drupal\batch_test\Controller;


/**
 * Controller routines for batch tests.
 */
class BatchTestController {

  /**
   * Redirects successfuly.
   *
   * @return array
   *   Render array containing success message.
   */
  public function testRedirect() {
    return array(
      'success' => array(
        '#type' => 'markup',
        '#markup' => 'Redirection successful.',
      )
    );
  }

  /**
   * Fires a batch process without a form submission.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *   A redirect response if the batch is progressive. No return value otherwise.
   */
  public function testLargePercentage() {
    batch_test_stack(NULL, TRUE);

    batch_set(_batch_test_batch_5());
    return batch_process('batch-test/redirect');
  }

  /**
   * Submits a form within a batch programmatically.
   *
   * @param int $value
   *   Some value passed to a custom batch callback.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *   A redirect response if the batch is progressive. No return value otherwise.
   */
  public function testNestedDrupalFormSubmit($value = 1) {
    // Set the batch and process it.
    $batch['operations'] = array(
      array('_batch_test_nested_drupal_form_submit_callback', array($value)),
    );
    batch_set($batch);
    return batch_process('batch-test/redirect');
  }

  /**
   * Fires a batch process without a form submission.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *   A redirect response if the batch is progressive. No return value otherwise.
   */
  public function testNoForm() {
    batch_test_stack(NULL, TRUE);

    batch_set(_batch_test_batch_1());
    return batch_process('batch-test/redirect');

  }

  /**
   * Submits the 'Chained' form programmatically.
   *
   * Programmatic form: the page submits the 'Chained' form through
   * drupal_form_submit().
   *
   * @param int $value
   *   Some value passed to a the chained form.
   *
   * @return array
   *   Render array containing markup.
   */
  function testProgrammatic($value = 1) {
    $form_state = array(
      'values' => array('value' => $value)
    );
    drupal_form_submit('batch_test_chained_form', $form_state);
    return array(
      'success' => array(
        '#type' => 'markup',
        '#markup' => 'Got out of a programmatic batched form.',
      )
    );
  }

  /**
   * Runs a batch for testing theme used on the progress page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   *   A redirect response if the batch is progressive. No return value otherwise.
   */
  public function testThemeBatch() {
    batch_test_stack(NULL, TRUE);
    $batch = array(
      'operations' => array(
        array('_batch_test_theme_callback', array()),
      ),
    );
    batch_set($batch);
    return batch_process('batch-test/redirect');
  }

}
