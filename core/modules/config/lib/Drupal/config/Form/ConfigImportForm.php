<?php

/**
 * @file
 * Contains \Drupal\config\Form\ConfigImportForm.
 */

namespace Drupal\config\Form;

use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Component\Archiver\ArchiveTar;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the configuration import form.
 */
class ConfigImportForm extends FormBase {

  /**
   * The configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * Constructs a new ConfigImportForm.
   *
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The configuration storage.
   */
  public function __construct(StorageInterface $config_storage) {
    $this->configStorage = $config_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.storage.staging')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['description'] = array(
      '#markup' => '<p>' . $this->t('Use the upload button below.') . '</p>',
    );
    $form['import_tarball'] = array(
      '#type' => 'file',
      '#title' => $this->t('Select your configuration export file'),
      '#description' => $this->t('This form will redirect you to the import configuration screen.'),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    if (!empty($_FILES['files']['error']['import_tarball'])) {
      form_set_error('import_tarball', $this->t('The import tarball could not be uploaded.'));
    }
    else {
      $form_state['values']['import_tarball'] = $_FILES['files']['tmp_name']['import_tarball'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    if ($path = $form_state['values']['import_tarball']) {
      $this->configStorage->deleteAll();
      try {
        $archiver = new ArchiveTar($path, 'gz');
        $files = array();
        foreach ($archiver->listContent() as $file) {
          $files[] = $file['filename'];
        }
        $archiver->extractList($files, config_get_config_directory(CONFIG_STAGING_DIRECTORY));
        drupal_set_message($this->t('Your configuration files were successfully uploaded, ready for import.'));
        $form_state['redirect'] = 'admin/config/development/configuration';
      }
      catch (\Exception $e) {
        form_set_error('import_tarball', $this->t('Could not extract the contents of the tar file. The error message is <em>@message</em>', array('@message' => $e->getMessage())));
      }
      drupal_unlink($path);
    }
  }

}

