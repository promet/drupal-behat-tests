<?php

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Defines application features from the specific context.
 */
class TaxonomyContext extends RawDrupalContext implements SnippetAcceptingContext {

  protected $vocabularies;

  /**
   * Initializes context.
   *
   * Every scenario gets its own context instance.
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   */
  public function __construct() {
    $this->vocabularies = array();
  }

  /** @BeforeScenario */
  public function before(BeforeScenarioScope $scope)
  {
    $this->vocabularies = Vocabulary::loadMultiple();
  }

  /**
   * @Given the :module module is enabled
   */
  public function theModuleIsEnabled($module)
  {
    if (!(\Drupal::service('module_handler')->moduleExists($module))) {
      throw new Exception(sprintf("The %s module must be enabled to execute these tests.", $module));
    }
  }

  /**
   * @Then I should have a Vocabulary named :vocabulary
   */
  public function iShouldHaveAVocabularyNamed($vocabulary)
  {
    $found = FALSE;
    $mach_name_found = NULL;
    foreach ($this->vocabularies as $machine_name => $vocab_object) {
      if ($vocab_object->get('name') == $vocabulary) {
        $found = TRUE;
        $mach_name_found = $machine_name;
        break;
      }
    }
    if (!$found) {
      throw new Exception(sprintf('The "%s" Vocabulary was not found', $vocabulary));
    }
    unset($this->vocabularies[$mach_name_found]);
  }

  /**
   * @Then I should have no other Vocabularies
   */
  public function iShouldHaveNoOtherVocabularies()
  {
    $vocabs_left = NULL;
    if (!empty($this->vocabularies)) {
      foreach($this->vocabularies as $vocab_object) {
        $vocabs_left .= $vocab_object->get('name') . ", ";
      }
      $vocabs_left = trim($vocabs_left, ", ");
        throw new Exception(sprintf('The following Vocabularies exist and should not:  "%s"', $vocabs_left));
    }
  }

}
