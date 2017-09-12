<?php

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Tester\Exception\PendingException;

use Drupal\field\FieldConfigInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Field\Entity\BaseFieldOverride;
use Drupal\Core\Field\FieldConfigBase;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines application features from the specific context.
 */
class ContentTypesContext extends RawDrupalContext implements SnippetAcceptingContext {

  protected $nodeDefinitionSettings = [
    'Machine name' => [
      'field_type' => 'contentType',
      'field_key' => 'type',
    ],
    'Name' => [
      'field_type' => 'contentType',
      'field_key' => 'name',
    ],
    'Description' => [
      'field_type' => 'contentType',
      'field_key' => 'description',
    ],
    'Explanation or submission guidelines' => [
      'field_type' => 'contentType',
      'field_key' => 'help',
    ],
    'Title field label' => [
      'field_type' => 'overrideableBaseFields',
      'field_key' => 'title',
    ],
    'Preview before submitting' => [
      'field_type' => 'contentType',
      'field_key' => 'preview_mode',
    ],
    'Published' => [
      'field_type' => 'overrideableBaseFields',
      'field_key' => 'status',
    ],
    'Promoted to front page' => [
      'field_type' => 'overrideableBaseFields',
      'field_key' => 'promote',
    ],
    'Sticky at top of lists' => [
      'field_type' => 'overrideableBaseFields',
      'field_key' => 'sticky',
    ],
    'Create new revision' => [
      'field_type' => 'contentType',
      'field_key' => 'new_revision',
    ],
    'Display author and date information' => [
      'field_type' => 'contentType',
      'field_key' => 'display_submitted',
    ],
    "Enable Workbench Access control for %s content." => [
      'field_type' => 'contentType',
      'field_key' => [
        'third_party_settings' => [
          'workbench_access' => [
            'workbench_access_status',
          ],
        ],
      ],
    ],
    // Note:  Available Menus is checked with separate defined steps.
    "Enable scheduled publishing for this content type" => [
      'field_type' => 'contentType',
      'field_key' => [
        'third_party_settings' => [
          'scheduler' => [
            'publish_enable',
          ],
        ],
      ],
    ],
    "Enable scheduled unpublishing for this content type" => [
      'field_type' => 'contentType',
      'field_key' => [
        'third_party_settings' => [
          'scheduler' => [
            'unpublish_enable',
          ],
        ],
      ],
    ],
  ];

  protected $previewSettings = [
    0 => 'Disabled',
    1 => 'Optional',
    2 => 'Required',
  ];

  protected $contentTypes;

  protected $contentType;

  protected $baseFieldTypes;

  protected $baseFields;

  protected $overrideableBaseFields;

  protected $attachedFields;

  protected $nodeDefinitions;

  /**
   * Initializes context.
   *
   * Every scenario gets its own context instance.
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   */
  public function __construct() {
  }

  /** @BeforeScenario */
  public function before(BeforeScenarioScope $scope)
  {
    $this->fieldTypes = \Drupal::service('plugin.manager.field.field_type')->getDefinitions();

    $this->contentTypes = [];
    $this->baseFieldTypes = [];
    $this->contentType = NULL;
    $this->baseFields = NULL;
    $this->overridableBaseFields = NULL;
    $this->attachedFields = NULL;
    $this->nodeDefinitions = NULL;
    //$this->baseFieldTypes = \Drupal::service('entity.manager')->getFieldDefinitions('node', 'tva_facilities');
  }

  /**
   * @Given I have defined node content types
   */
  public function iHaveDefinedNodeContentTypes()
  {
    $contentTypes = \Drupal::service('entity_type.manager')->getStorage('node_type')->loadMultiple();
    if (empty($contentTypes)) {
      throw new \Exception(sprintf("No node content types have been defined"));
    }
    $nodeTypes = array_keys($contentTypes);
    $this->contentTypes = $contentTypes;
  }

  /**
   * @Then I should have a/an :contentType content type
   */
  public function iShouldHaveAContentType($contentType)
  {
    $found = FALSE;
    foreach ($this->contentTypes as $key => $nodeType) {
      $name = $nodeType->get('name');
      if ($name == $contentType) {
        $found = TRUE;
        unset($this->contentTypes[$key]);
      }
    }
    if (!$found) {
      throw new \Exception(sprintf("The '%s' content type has not been defined", $contentType));
    }
  }

  /**
   * @Then I should have no other content types
   */
  public function iShouldHaveNoOtherContentTypes()
  {
    $types = NULL;
    if (!empty($this->contentTypes)) {
      foreach ($this->contentTypes as $key => $type) {
        $types .= $type->get('name') . ", ";
        $types = trim(trim($types, " "), ",");
      }
      throw new \Exception(sprintf("These content type exist and should not: %s", $types));
    }
  }

  /**
   * @Given the :contentType content type exists
   */
  public function contentTypeExists($contentType)
  {
    $contentTypes = \Drupal::service('entity_type.manager')->getStorage('node_type')->loadMultiple();
    foreach ($contentTypes as $key => $nodeType) {
      $name = $nodeType->get('name');
      if ($name == $contentType) {
        $this->contentType = $nodeType;
        $this->attachedFields = $this->contentTypeFields($key);
        $this->baseFields = $this->contentTypeBaseFields($key);
        $this->overridableBaseFields = $this->contentTypeOverridableBaseFields($key);
        $this->nodeDefinitions = $contentTypes[$key];
        //$this->form_settings = $this->contentTypeFormSettings($key);
        break;
      }
    }
    if (is_null($this->contentType)) {
      throw new \Exception(sprintf('The content type "%s" was not found.', $contentType));
    }
  }

  /**
   * @Then I should have a/an :field field as type :fieldType, which is :required
   */
  public function iShouldHaveFieldAsTypeWhichIs($field, $fieldType, $required)
  {
    $errors = NULL;
    $machineName = $this->contentType->get('type');
    $fieldFound = FALSE;
    $fieldTypeValue = NULL;
    $requiredValue = NULL;
    $fieldMachineName = NULL;
    foreach ($this->attachedFields as $key => $fields) {
      if (strpos($key, $machineName) === FALSE || strpos($key, $machineName) > 0) {
        $errors .= sprintf('The field machine name "%s" should start with the content type machine name of "%s", but does not.', $key, $machineName) . "\n\r";
      }
      if ($fields->get('label') == $field)  {
        $fieldFound = TRUE;

        $fields_fieldType = $this->fieldTypes[$fields->get('field_type')];
        $fieldTypeValue = $fields_fieldType['label']->render();
        $requiredValue = $fields->get('required') ? 'required' : 'not required';
        unset($this->attachedFields[$key]);
      }
    }
    if ($fieldFound) {

      // @TODO:  add check for content type machine name in field name
      if (!$fieldTypeValue == $fieldType) {
        $errors .= sprintf('The field type should be "%s", but it is actually "%s".', $fieldType, $fieldTypeValue) . "\n\r";
      }
      if ($requiredValue != $required) {
        $errors .= sprintf('The field "%s" should be "%s", but it is actually "%s".', $field, $required, $requiredValue) . "\n\r";
      }
    }
    else {
      $errors = sprintf('The field "%s" was not found.', $field) . "\n\r";
    }
    if (!empty($errors)) {
      $errors = "\n\r" . $errors;
      throw new \Exception($errors);
    }
  }

  /**
   * @Then I should have no other fields on the :contentType content type
   */
  public function iShouldHaveNoOtherFieldsOnTheContentType($contentType)
  {
    $fieldTypes = NULL;
    if (!empty($this->attachedFields)) {
      $fields = array_keys($this->attachedFields);
      foreach($fields as $field) {
        $fieldType[] = $this->attachedFields[$field]->get('label');
      }
      $fieldTypes = implode(", ", $fieldType);
      $fieldTypes = trim(trim($fieldTypes, " "), ",");
      throw new \Exception(sprintf('These field types exist on the "%s" content type and should not: %s', $contentType, $fieldTypes));
    }
  }

  /**
   * @Then (the ):label should be (set to ):value
   */
  public function theShouldBeSetTo($label, $value)
  {
    dd($this->contentType);
    $labelInfo = NULL;
    $label_to_check = $label;
    if (!isset($this->nodeDefinitionSettings[$label_to_check])) {
      $name = $this->contentType->get('name');
      $label_to_check = str_replace($name, "%s", $label);
      if (!isset($this->nodeDefinitionSettings[$label_to_check])) {
       throw new \Exception(sprintf('The setting "%s" was not found in the node definition settings to check.  Check the text and try again.', $label));
      }
    }
    $labelInfo = $this->nodeDefinitionSettings[$label_to_check];

    switch ($labelInfo['field_type']) {
      case "contentType":
        $array_keys = NULL;
        if(!is_array($labelInfo['field_key'])) {
          $field_value = $this->contentType->get($labelInfo['field_key']);
        }
        else {
          $array_keys = array_keys($labelInfo['field_key']['third_party_settings']);
          $field_value = $this->contentType->get($array_keys[0]);
        }

        if ($labelInfo['field_key'] == 'preview_mode') {
          $field_value_label = $this->previewSettings[$field_value];
          if ($field_value_label != $value) {
            throw new \Exception(sprintf('The setting for "%s" is supposed to be "%s", but instead is set to "%s"', $label, $value, $field_value_label));
          }
        }

        else if ($labelInfo['field_key'] == 'display_submitted') {
          if ($field_value == 1) {
            $field_value_label = 'checked';
          }
          else {
            $field_value_label = 'unchecked';
          }
          if ($field_value_label != $value) {
            throw new \Exception(sprintf('The setting for "%s" is supposed to be "%s", but instead is set to "%s"', $label, $value, $field_value_label));
          }
        }
        else if (is_array($labelInfo['field_key']) && isset($labelInfo['field_key']['third_party_settings'])) {
          $third_party_setting_to_check = array_values($labelInfo['field_key']['third_party_settings'][$array_keys[0]]);
          $field_value = $this->contentType->getThirdPartySetting($array_keys[0], $third_party_setting_to_check[0]);
          if($field_value == 1) {
            $field_value_label = 'checked';
          }
          else {
            $field_value_label = 'unchecked';
          }
          if ($field_value_label != $value) {

            throw new \Exception(sprintf('The setting for "%s" is supposed to be "%s", but instead is set to "%s"', $label, $value, $field_value_label));
          }
        }
        else {
        }
        break;
      case "overrideableBaseFields":
        $field_object = NULL;
        if(isset($this->overridableBaseFields[$labelInfo['field_key']])) {
          $field_object = $this->overridableBaseFields[$labelInfo['field_key']];
        }
        if ($labelInfo['field_key'] == 'title') {
          if($field_object) {
            $field_value = $field_object->get('label');
            if ($field_value != $value) {
              throw new \Exception(sprintf('The setting for "%s" is supposed to be "%s", but instead is set to "%s"', $label, $value, $field_value));
            }
          }
        }
        else if (
          $labelInfo['field_key'] == 'status' ||
          $labelInfo['field_key'] == 'sticky' ||
          $labelInfo['field_key'] == 'promote') {
          if($field_object) {
            $field_value = $field_object->get('default_value');
            if ($value == 'checked') {
              $value_to_check = 1;
            }
            else {
              $value_to_check = 0;
            }

            if ($field_value[0]['value'] != $value_to_check) {
              if ($field_value[0]['value'] == 1) {
                $field_value_string = 'checked';
              }
              else {
                $field_value_string = 'unchecked';
              }
              throw new \Exception(sprintf('The setting for "%s" is supposed to be "%s", but instead is set to "%s"', $label, $value, $field_value_string));
            }
          }
        }
        else {
          throw new \Exception(sprintf('There was a problem running the tests to check the value of "%s".', $label));
        }
        break;
      default:
    }
  }

  /**
   * @Then Available Menus should be unchecked
   */
  public function availableMenusShouldBeUnchecked()
  {
    $menus = "";
    $parent_menu = NULL;
    $this->availableMenusShouldHaveCheckedAndTheDefaultParentItemShouldBeSetTo($menus, $parent_menu);
  }

  /**
   * @Then Available Menus should have :menus checked, and the Default parent item should be set to :parent_value
   */
  public function availableMenusShouldHaveCheckedAndTheDefaultParentItemShouldBeSetTo($menus, $parent_menu)
  {
    $module = 'menu_ui';
    $check = 'available_menus';
    $labelInfo = NULL;
    $field_value = $this->contentType->getThirdPartySetting($module, $check);
    $available_menus = array_flip(menu_ui_get_menus());
    $menus_to_check = explode(", ", $menus);
    $menu_values = array();
    foreach($menus_to_check as $menu) {
      if (isset($available_menus[$menu])) {
        $menu_values[] = $available_menus[$menu];
      }
    }
    if($field_value != $menu_values) {
      throw new \Exception(sprintf('The Available Menus should be set to "%s", but instead is set to "%s"', $menus, implode(", ", $field_value)));
    }
    $check = 'parent';
    $field_value = $this->contentType->getThirdPartySetting($module, $check);
    $value_to_check = $parent_menu;
    if(substr($field_value, -1) == ":") {
      $value_to_check = $parent_menu . ":";
    }
    if($field_value != $value_to_check) {
      // @TODO:  Program test for checking an actual parent menu insetad of a <placeholder>
      throw new \Exception(sprintf('The Default parent item should be set to "%s", but instead is set to "%s"', $parent_menu, str_replace(":", "", $field_value)));
    }
  }

  /**
   * Helper function to return the attached field definitions for a content type.
   *
   * @param string $contentType
   *   A string containing the field machine name.
   *
   * @return array $fields
   *   An array of field objects for attached fields.
   */
  private function contentTypeFields($contentType) {
    $fields = [];
    if(!empty($contentType)) {
      $fields = array_filter(
        \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $contentType), function ($field_definition) {
          return $field_definition instanceof FieldConfig;
        }
      );
    }
    return $fields;
  }

  /**
   * Helper function to return the base field definitions for a content type.
   *
   * @param string $contentType
   *   A string containing the field machine name.
   *
   * @return array $fields
   *   An array of field objects for base fields.
   */
  private function contentTypeBaseFields($contentType) {
    $fields = [];
    if(!empty($contentType)) {
      $fields = array_filter(
        \Drupal::service('entity.manager')->getFieldDefinitions('node', $contentType), function ($field_definition) {
          return $field_definition instanceof BaseFieldDefinition;
        }
      );
    }
    return $fields;
  }

  /**
   * Helper function to return the overridable base field definitions for a
   * content type.
   *
   * @param string $contentType
   *   A string containing the field machine name.
   *
   * @return array $fields
   *   An array of field objects for overridable base fields.
   */
  private function contentTypeOverridableBaseFields($contentType) {
    $fields = [];
    if(!empty($contentType)) {
      $fields = array_filter(
        \Drupal::service('entity.manager')->getFieldDefinitions('node', $contentType), function ($field_definition) {
          return $field_definition instanceof BaseFieldOverride;
        }
      );
    }
    return $fields;
  }

  // May not be needed.
  private function contentTypeFormSettings($contentType) {
    $entityTypeManager = \Drupal::service('entity_type.manager');
    $formSettings = [];
    if (!empty($contentType)) {
      $entityTypeManager->getStorage('entity_form_display');

    }
    return $formSettings;
  }
}
