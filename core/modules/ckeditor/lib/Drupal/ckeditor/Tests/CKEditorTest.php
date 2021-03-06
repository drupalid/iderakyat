<?php

/**
 * @file
 * Definition of \Drupal\ckeditor\Tests\CKEditorTest.
 */

namespace Drupal\ckeditor\Tests;

use Drupal\simpletest\DrupalUnitTestBase;
use Drupal\editor\Plugin\EditorManager;
use Drupal\ckeditor\Plugin\Editor\CKEditor;

/**
 * Tests for the 'CKEditor' text editor plugin.
 */
class CKEditorTest extends DrupalUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('system', 'editor', 'ckeditor', 'filter_test');

  /**
   * An instance of the "CKEditor" text editor plugin.
   *
   * @var \Drupal\ckeditor\Plugin\Editor\CKEditor;
   */
  protected $ckeditor;

  /**
   * The Editor Plugin Manager.
   *
   * @var \Drupal\editor\Plugin\EditorManager;
   */
  protected $manager;

  public static function getInfo() {
    return array(
      'name' => 'CKEditor text editor plugin',
      'description' => 'Tests all aspects of the CKEditor text editor plugin.',
      'group' => 'CKEditor',
    );
  }

  function setUp() {
    parent::setUp();

    // Install the Filter module.
    $this->installSchema('system', 'url_alias');
    $this->enableModules(array('user', 'filter'));

    // Create text format, associate CKEditor.
    $filtered_html_format = entity_create('filter_format', array(
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
      'filters' => array(
        'filter_html' => array(
          'status' => 1,
          'settings' => array(
            'allowed_html' => '<h4> <h5> <h6> <p> <br> <strong> <a>',
          )
        ),
      ),
    ));
    $filtered_html_format->save();
    $editor = entity_create('editor', array(
      'format' => 'filtered_html',
      'editor' => 'ckeditor',
    ));
    $editor->save();

    // Create "CKEditor" text editor plugin instance.
    $this->ckeditor = $this->container->get('plugin.manager.editor')->createInstance('ckeditor');
  }

  /**
   * Tests CKEditor::getJSSettings().
   */
  function testGetJSSettings() {
    $editor = entity_load('editor', 'filtered_html');

    // Default toolbar.
    $expected_config = $this->getDefaultInternalConfig() + array(
      'drupalImage_dialogTitleAdd' => 'Insert Image',
      'drupalImage_dialogTitleEdit' => 'Edit Image',
      'drupalLink_dialogTitleAdd' => 'Add Link',
      'drupalLink_dialogTitleEdit' => 'Edit Link',
      'allowedContent' => $this->getDefaultAllowedContentConfig(),
      'toolbar' => $this->getDefaultToolbarConfig(),
      'contentsCss' => $this->getDefaultContentsCssConfig(),
      'extraPlugins' => 'drupalimage,drupallink',
      'language' => 'en',
      'stylesSet' => FALSE,
      'drupalExternalPlugins' => array(
        'drupalimage' => file_create_url('core/modules/ckeditor/js/plugins/drupalimage/plugin.js'),
        'drupallink' => file_create_url('core/modules/ckeditor/js/plugins/drupallink/plugin.js'),
      ),
    );
    ksort($expected_config);
    $this->assertIdentical($expected_config, $this->ckeditor->getJSSettings($editor), 'Generated JS settings are correct for default configuration.');

    // Customize the configuration: add button, have two contextually enabled
    // buttons, and configure a CKEditor plugin setting.
    $this->enableModules(array('ckeditor_test'));
    $this->container->get('plugin.manager.editor')->clearCachedDefinitions();
    $this->ckeditor = $this->container->get('plugin.manager.editor')->createInstance('ckeditor');
    $this->container->get('plugin.manager.ckeditor.plugin')->clearCachedDefinitions();
    $editor->settings['toolbar']['rows'][0][0]['items'][] = 'Strike';
    $editor->settings['toolbar']['rows'][0][0]['items'][] = 'Format';
    $editor->save();
    $expected_config['toolbar'][0]['items'][] = 'Strike';
    $expected_config['toolbar'][0]['items'][] = 'Format';
    $expected_config['format_tags'] = 'p;h4;h5;h6';
    $expected_config['extraPlugins'] .= ',llama_contextual,llama_contextual_and_button';
    $expected_config['drupalExternalPlugins']['llama_contextual'] = file_create_url('core/modules/ckeditor/tests/modules/js/llama_contextual.js');
    $expected_config['drupalExternalPlugins']['llama_contextual_and_button'] = file_create_url('core/modules/ckeditor/tests/modules/js/llama_contextual_and_button.js');
    $expected_config['contentsCss'][] = file_create_url('core/modules/ckeditor/tests/modules/ckeditor_test.css');
    ksort($expected_config);
    $this->assertIdentical($expected_config, $this->ckeditor->getJSSettings($editor), 'Generated JS settings are correct for customized configuration.');

    // Change the allowed HTML tags; the "allowedContent" and "format_tags"
    // settings for CKEditor should automatically be updated as well.
    $format = entity_load('filter_format', 'filtered_html');
    $format->filters('filter_html')->settings['allowed_html'] .= '<pre> <h3>';
    $format->save();
    $expected_config['allowedContent']['pre'] = array('attributes' => TRUE, 'styles' => FALSE, 'classes' => TRUE);
    $expected_config['allowedContent']['h3'] = array('attributes' => TRUE, 'styles' => FALSE, 'classes' => TRUE);
    $expected_config['format_tags'] = 'p;h3;h4;h5;h6;pre';
    $this->assertIdentical($expected_config, $this->ckeditor->getJSSettings($editor), 'Generated JS settings are correct for customized configuration.');

    // Disable the filter_html filter: allow *all *tags.
    $format->setFilterConfig('filter_html', array('status' => 0));
    $format->save();
    $expected_config['allowedContent'] = TRUE;
    $expected_config['format_tags'] = 'p;h1;h2;h3;h4;h5;h6;pre';
    $this->assertIdentical($expected_config, $this->ckeditor->getJSSettings($editor), 'Generated JS settings are correct for customized configuration.');

    // Enable the filter_test_restrict_tags_and_attributes filter.
    $format->setFilterConfig('filter_test_restrict_tags_and_attributes', array(
      'status' => 1,
      'settings' => array(
        'restrictions' => array(
          'allowed' => array(
            'p' => TRUE,
            'a' => array(
              'href' => TRUE,
              'rel' => array('nofollow' => TRUE),
              'class' => array('external' => TRUE),
              'target' => array('_blank' => FALSE),
            ),
            'span' => array(
              'class' => array('dodo' => FALSE),
              'property' => array('dc:*' => TRUE),
              'rel' => array('foaf:*' => FALSE),
            ),
            '*' => array(
              'style' => FALSE,
              'class' => array('is-a-hipster-llama' => TRUE, 'and-more' => TRUE),
              'data-*' => TRUE,
            ),
            'del' => FALSE,
          )
        ),
      ),
    ));
    $format->save();
    $expected_config['allowedContent'] = array(
      'p' => array(
        'attributes' => TRUE,
        'styles' => FALSE,
        'classes' => 'is-a-hipster-llama,and-more',
      ),
      'a' => array(
        'attributes' => 'href,rel,class,target',
        'classes' => 'external',
      ),
      'span' => array(
        'attributes' => 'class,property,rel',
      ),
      '*' => array(
        'attributes' => 'class,data-*',
        'classes' => 'is-a-hipster-llama,and-more',
      ),
      'del' => array(
        'attributes' => FALSE,
        'styles' => FALSE,
        'classes' => FALSE,
      ),
    );
    $expected_config['format_tags'] = 'p';
    ksort($expected_config);
    $this->assertIdentical($expected_config, $this->ckeditor->getJSSettings($editor), 'Generated JS settings are correct for customized configuration.');
  }

  /**
   * Tests CKEditor::buildToolbarJSSetting().
   */
  function testBuildToolbarJSSetting() {
    $editor = entity_load('editor', 'filtered_html');

    // Default toolbar.
    $expected = $this->getDefaultToolbarConfig();
    $this->assertIdentical($expected, $this->ckeditor->buildToolbarJSSetting($editor), '"toolbar" configuration part of JS settings built correctly for default toolbar.');

    // Customize the configuration.
    $editor->settings['toolbar']['rows'][0][0]['items'][] = 'Strike';
    $editor->save();
    $expected[0]['items'][] = 'Strike';
    $this->assertIdentical($expected, $this->ckeditor->buildToolbarJSSetting($editor), '"toolbar" configuration part of JS settings built correctly for customized toolbar.');

    // Enable the editor_test module, customize further.
    $this->enableModules(array('ckeditor_test'));
    $this->container->get('plugin.manager.ckeditor.plugin')->clearCachedDefinitions();
    // Override the label of a toolbar component.
    $editor->settings['toolbar']['rows'][0][0]['name'] = 'JunkScience';
    $editor->settings['toolbar']['rows'][0][0]['items'][] = 'Llama';
    $editor->save();
    $expected[0]['name'] = 'JunkScience';
    $expected[0]['items'][] = 'Llama';
    $this->assertIdentical($expected, $this->ckeditor->buildToolbarJSSetting($editor), '"toolbar" configuration part of JS settings built correctly for customized toolbar with contrib module-provided CKEditor plugin.');
  }

  /**
   * Tests CKEditor::buildContentsCssJSSetting().
   */
  function testBuildContentsCssJSSetting() {
    $editor = entity_load('editor', 'filtered_html');

    // Default toolbar.
    $expected = $this->getDefaultContentsCssConfig();
    $this->assertIdentical($expected, $this->ckeditor->buildContentsCssJSSetting($editor), '"contentsCss" configuration part of JS settings built correctly for default toolbar.');

    // Enable the editor_test module, which implements hook_ckeditor_css_alter().
    $this->enableModules(array('ckeditor_test'));
    $expected[] = file_create_url('core/modules/ckeditor/tests/modules/ckeditor_test.css');
    $this->assertIdentical($expected, $this->ckeditor->buildContentsCssJSSetting($editor), '"contentsCss" configuration part of JS settings built correctly while a hook_ckeditor_css_alter() implementation exists.');

    // @todo test coverage for _ckeditor_theme_css(), by including a custom theme in this test with a "ckeditor_stylesheets" entry in its .info file.
  }

  /**
   * Tests Internal::getConfig().
   */
  function testInternalGetConfig() {
    $editor = entity_load('editor', 'filtered_html');
    $internal_plugin = $this->container->get('plugin.manager.ckeditor.plugin')->createInstance('internal');

    // Default toolbar.
    $expected = $this->getDefaultInternalConfig();
    $expected['allowedContent'] = $this->getDefaultAllowedContentConfig();
    $this->assertIdentical($expected, $internal_plugin->getConfig($editor), '"Internal" plugin configuration built correctly for default toolbar.');

    // Format dropdown/button enabled: new setting should be present.
    $editor->settings['toolbar']['rows'][0][0]['items'][] = 'Format';
    $expected['format_tags'] = 'p;h4;h5;h6';
    $this->assertIdentical($expected, $internal_plugin->getConfig($editor), '"Internal" plugin configuration built correctly for customized toolbar.');
  }

  /**
   * Tests StylesCombo::getConfig().
   */
  function testStylesComboGetConfig() {
    $editor = entity_load('editor', 'filtered_html');
    $stylescombo_plugin = $this->container->get('plugin.manager.ckeditor.plugin')->createInstance('stylescombo');

    // Styles dropdown/button enabled: new setting should be present.
    $editor->settings['toolbar']['rows'][0][0]['items'][] = 'Styles';
    $editor->settings['plugins']['stylescombo']['styles'] = '';
    $editor->save();
    $expected['stylesSet'] = array();
    $this->assertIdentical($expected, $stylescombo_plugin->getConfig($editor), '"StylesCombo" plugin configuration built correctly for customized toolbar.');

    // Configure the optional "styles" setting in odd ways that shouldn't affect
    // the end result.
    $editor->settings['plugins']['stylescombo']['styles'] = "   \n";
    $editor->save();
    $this->assertIdentical($expected, $stylescombo_plugin->getConfig($editor));
    $editor->settings['plugins']['stylescombo']['styles'] = "\r\n  \n  \r  \n ";
    $editor->save();
    $this->assertIdentical($expected, $stylescombo_plugin->getConfig($editor), '"StylesCombo" plugin configuration built correctly for customized toolbar.');

    // Now configure it properly, the end result should change.
    $editor->settings['plugins']['stylescombo']['styles'] = "h1.title|Title\np.mAgical.Callout|Callout";
    $editor->save();
    $expected['stylesSet'] = array(
      array('name' => 'Title', 'element' => 'h1', 'attributes' => array('class' => 'title')),
      array('name' => 'Callout', 'element' => 'p', 'attributes' => array('class' => 'mAgical Callout')),
    );
    $this->assertIdentical($expected, $stylescombo_plugin->getConfig($editor), '"StylesCombo" plugin configuration built correctly for customized toolbar.');

    // Same configuration, but now interspersed with nonsense. Should yield the
    // same result.
    $editor->settings['plugins']['stylescombo']['styles'] = "  h1 .title   |  Title \r \n\r  \np.mAgical  .Callout|Callout\r";
    $editor->save();
    $this->assertIdentical($expected, $stylescombo_plugin->getConfig($editor), '"StylesCombo" plugin configuration built correctly for customized toolbar.');

    // Slightly different configuration: class names are optional.
    $editor->settings['plugins']['stylescombo']['styles'] = "      h1 |  Title ";
    $editor->save();
    $expected['stylesSet'] = array(array('name' => 'Title', 'element' => 'h1'));
    $this->assertIdentical($expected, $stylescombo_plugin->getConfig($editor), '"StylesCombo" plugin configuration built correctly for customized toolbar.');

    // Invalid syntax should cause stylesSet to be set to FALSE.
    $editor->settings['plugins']['stylescombo']['styles'] = "h1";
    $editor->save();
    $expected['stylesSet'] = FALSE;
    $this->assertIdentical($expected, $stylescombo_plugin->getConfig($editor), '"StylesCombo" plugin configuration built correctly for customized toolbar.');
  }

  /**
   * Tests language list availability in CKEditor.
   */
  function testLanguages() {
    // Get CKEditor supported language codes and spot-check.
    $this->enableModules(array('language'));
    config_install_default_config('module', 'language');
    $langcodes = $this->ckeditor->getLangcodes();

    // Language codes transformed with browser mappings.
    $this->assertTrue($langcodes['pt-pt'] == 'pt', '"pt" properly resolved');
    $this->assertTrue($langcodes['zh-hans'] == 'zh-cn', '"zh-hans" properly resolved');

    // Language code both in Drupal and CKEditor.
    $this->assertTrue($langcodes['gl'] == 'gl', '"gl" properly resolved');

    // Language codes only in CKEditor.
    $this->assertTrue($langcodes['en-au'] == 'en-au', '"en-au" properly resolved');
    $this->assertTrue($langcodes['sr-latn'] == 'sr-latn', '"sr-latn" properly resolved');
  }

  /**
   * Tests that CKEditor plugins participate in JS translation.
   */
  function testJSTranslation() {
    $this->enableModules(array('locale'));
    $this->installSchema('locale', 'locales_source');
    $this->installSchema('locale', 'locales_location');
    $editor = entity_load('editor', 'filtered_html');
    $this->ckeditor->getJSSettings($editor);
    $localeStorage = $this->container->get('locale.storage');
    $string = $localeStorage->findString(array('source' => 'Image Properties', 'context' => ''));
    $this->assertTrue(!empty($string), 'String from JavaScript file saved.');
  }

  protected function getDefaultInternalConfig() {
    return array(
      'customConfig' => '',
      'pasteFromWordPromptCleanup' => TRUE,
      'resize_dir' => 'vertical',
      'justifyClasses' => array('align-left', 'align-center', 'align-right', 'align-justify'),
    );
  }

  protected function getDefaultAllowedContentConfig() {
    return array(
      'h4' => array('attributes' => TRUE, 'styles' => FALSE, 'classes' => TRUE),
      'h5' => array('attributes' => TRUE, 'styles' => FALSE, 'classes' => TRUE),
      'h6' => array('attributes' => TRUE, 'styles' => FALSE, 'classes' => TRUE),
      'p' => array('attributes' => TRUE, 'styles' => FALSE, 'classes' => TRUE),
      'br' => array('attributes' => TRUE, 'styles' => FALSE, 'classes' => TRUE),
      'strong' => array('attributes' => TRUE, 'styles' => FALSE, 'classes' => TRUE),
      'a' => array('attributes' => TRUE, 'styles' => FALSE, 'classes' => TRUE),
    );
  }

  protected function getDefaultToolbarConfig() {
    return array(
      array(
        'name' => t('Formatting'),
        'items' => array('Bold', 'Italic',),
      ),
      array(
        'name' => t('Links'),
        'items' => array('DrupalLink', 'DrupalUnlink',),
      ),
      array(
        'name' => t('Lists'),
        'items' => array('BulletedList', 'NumberedList',),
      ),
      array(
        'name' => t('Media'),
        'items' => array('Blockquote', 'DrupalImage',),
      ),
      array(
        'name' => t('Tools'),
        'items' => array('Source',),
      ),
      '/',
    );
  }

  protected function getDefaultContentsCssConfig() {
    return array(
      file_create_url('core/modules/ckeditor/css/ckeditor-iframe.css'),
      file_create_url('core/modules/system/css/system.module.css'),
    );
  }

}
