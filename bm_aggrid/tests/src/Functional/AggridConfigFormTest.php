<?php

declare(strict_types=1);

namespace Drupal\Tests\bm_aggrid\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the AG Grid configuration form.
 */
class AggridConfigFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'bm_aggrid',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Ensure the configuration form loads for administrators.
   */
  public function testConfigFormLoads(): void {
    $account = $this->drupalCreateUser(['administer aggrid display']);
    $this->drupalLogin($account);

    $this->drupalGet('/admin/config/content/aggrid-display');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Grid definitions');
    $this->assertSession()->pageTextContains('Rows per page');
  }

}
