<?php

namespace Drupal\Tests\crm_core_contact\Unit;

use Drupal\crm_core_contact\Entity\Individual;
use Drupal\crm_core_contact\Entity\Organization;
use Drupal\Tests\UnitTestCase;

/**
 * Test the module file.
 *
 * @group crm_core
 */
class ContactModuleTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    require_once __DIR__ . '/../../../crm_core_contact.module';
  }

  /**
   * Test suggestions.
   *
   * @covers ::crm_core_contact_theme_suggestions_crm_core_individual
   */
  public function testIndividualSuggestions(): void {
    $individual = $this->createMock(Individual::class);
    $individual
      ->method('bundle')->willReturn('customer');
    $individual
      ->method('id')->willReturn(1);
    $result = crm_core_contact_theme_suggestions_crm_core_individual(
      [
        'elements' => [
          '#crm_core_individual' => $individual,
          '#view_mode' => 'my.test',
        ],
      ]
    );
    $this->assertArrayEquals($result, [
      'crm_core_individual__my_test',
      'crm_core_individual__customer',
      'crm_core_individual__customer__my_test',
      'crm_core_individual__1',
      'crm_core_individual__1__my_test',
    ]);
  }

  /**
   * Test suggestions.
   *
   * @covers ::crm_core_contact_theme_suggestions_crm_core_organization
   */
  public function testOrganizationSuggestions(): void {
    $organization = $this->createMock(Organization::class);
    $organization
      ->method('bundle')->willReturn('customer');
    $organization
      ->method('id')->willReturn(1);
    $result = crm_core_contact_theme_suggestions_crm_core_organization(
      [
        'elements' => [
          '#crm_core_organization' => $organization,
          '#view_mode' => 'my.test',
        ],
      ]
    );
    $this->assertArrayEquals($result, [
      'crm_core_organization__my_test',
      'crm_core_organization__customer',
      'crm_core_organization__customer__my_test',
      'crm_core_organization__1',
      'crm_core_organization__1__my_test',
    ]);
  }

  /**
   * Test template hook.
   *
   * @covers ::crm_core_contact_theme
   */
  public function testTemplate(): void {
    $templates = crm_core_contact_theme();
    $this->assertequals('crm-core-organization', $templates['crm_core_organization']['template']);
    $this->assertequals('crm-core-individual', $templates['crm_core_individual']['template']);
  }

  /**
   * Test mail hook.
   *
   * @covers ::crm_core_contact_mail
   */
  public function testMail(): void {
    $params = [
      'subject' => 'Subject',
      'message' => 'Content',
    ];
    $message = [];
    crm_core_contact_mail('example', $message, $params);
    $this->assertEquals($message['subject'], $params['subject']);
    $this->assertEquals($message['body'][0], $params['message']);
  }

}
