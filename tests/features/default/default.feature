@insidenet
Feature: TVA InsideNet is up
  In order to demonstrate that the site is running
  As a tester
  I need to be able to visit the home page and make sure the site is up

  Scenario:  Test that the site is up and running using Goette
    Given I visit "/"
    Then I should get a "200" HTTP response

  Scenario: Test the ability to find our site name in the header region using Goette
    Given I am on the homepage
    Then I should see "Drupal-Behat Testing" in the "branding" region

  @api
  Scenario:  Test that the site is up and running using Goette and the api
    Given I am logged in as a user with the "administrator" role
    When I visit "/"
    Then I should get a "200" HTTP response

  @api
  Scenario: Test the ability to find our site name in the header region using Goett and the api
    Given I am logged in as a user with the "administrator" role
    When I am on the homepage
    Then I should see "Drupal-Behat Testing" in the "branding" region

  @api @javascript
  Scenario:  Test that javascript is working
    Given I am logged in as a user with the "administrator" role
    When I visit "/user"
    Then I should see the link "View" in the "primary tabs" region
