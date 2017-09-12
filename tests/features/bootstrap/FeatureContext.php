<?php

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Testwork\Tester\Result\TestResult;
use Behat\Mink\Driver\Selenium2Driver;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends RawDrupalContext implements SnippetAcceptingContext {

  /**
   * Initializes context.
   *
   * Every scenario gets its own context instance.
   * You can also pass arbitrary arguments to the
   * context constructor through behat.yml.
   */
  public function __construct() {
  }

  /**
   * @AfterStep
   */
  public function takeScreenshotAfterFailedStep($event)
  {
    if ($event->getTestResult()->getResultCode() === TestResult::FAILED) {
      $data = NULL;
      $driver = $this->getSession()->getDriver();
      $filePath = '/var/www/sites/drupal-behat.localhost/tests/screenshots';
      $stepText = $event->getStep()->getText();
      $fileName = preg_replace('#[^a-zA-Z0-9\._-]#', '', $stepText);
      if ($driver instanceof Selenium2Driver) {
        $this->getSession()->resizeWindow(1600, 1800, 'current');
        $fileName = $fileName .'.png';
        $data = $this->getSession()->getScreenshot();
      }
      elseif ($driver->getClient()->getInternalResponse()){
        $data = $driver->getContent();
        $fileName = $fileName .'.html';
      }
      if ($data) {
        file_put_contents($filePath . DIRECTORY_SEPARATOR . $fileName, $data);
        print "Screenshot for '{$stepText}' placed in ". $filePath . DIRECTORY_SEPARATOR . $fileName."\n";
      }
    }
  }
  /**
   * @AfterStep
   */
  /**
  public function takeScreenshotAfterFailedStep(AfterStepScope $scope)
  {
    if (99 === $scope->getTestResult()->getResultCode()) {
      $this->takeScreenshot();
    }
  }

  private function takeScreenshot()
  {
    $driver = $this->getSession()->getDriver();
    if (!$driver instanceof Selenium2Driver) {
      return;
    }
    //$baseUrl = $this->getMinkParameter('base_url');
    $fileName = date('d-m-y') . '-' . uniqid() . '.png';
    //$filePath = $this->getContainer()->get('kernel')->getRootdir() . '/../web/tmp/';
    $filePath = '/var/www/sites/tva.local/tests/screenshots';
    $this->saveScreenshot($fileName, $filePath);
    print 'Screenshot at: /var/www/sites/tva.local/tests/screenshots/' . $fileName;
  }
  **/
}
