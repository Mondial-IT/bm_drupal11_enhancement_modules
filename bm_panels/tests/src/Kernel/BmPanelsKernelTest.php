<?php

declare(strict_types=1);

namespace Drupal\Tests\bm_panels\Kernel;

use Drupal\bm_panels\Element\Panels;
use Drupal\Core\Render\Markup;
use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for BM Panels registration.
 */
class BmPanelsKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'bm_panels'];

  public function testThemeAndElementRegistration(): void {
    $registry = $this->container->get('theme.registry');
    $this->assertNotEmpty($registry->get('bm_panel'));

    $element_info = $this->container->get('element_info');
    $definition = $element_info->getInfo('bm_panels');
    $this->assertSame('bm_panel_container', $definition['#theme'] ?? NULL);
  }

  public function testPanelServiceStoresState(): void {
    /** @var \Drupal\bm_panels\Service\PanelService $service */
    $service = $this->container->get('bm_panels.service');
    $state = $service->getPanelState('kernel_test');
    $this->assertArrayHasKey('panels', $state);

    $service->savePanelState('kernel_test', [
      'panels' => [
        'example' => [
          'x' => 2,
          'y' => 1,
          'width' => 5,
          'height' => 3,
        ],
      ],
    ]);
    $updated = $service->getPanelState('kernel_test');
    $this->assertSame(5, $updated['panels']['example']['width']);
  }

  public function testPanelTitlesAndDescriptionsPropagateToTheme(): void {
    $element = [
      '#type' => 'bm_panels',
      '#collection' => 'kernel_test_titles',
      '#show_panel_meta' => TRUE,
      'demo' => [
        '#markup' => '<p>Demo</p>',
        '#title' => 'Demo panel',
        '#description' => 'Plain description',
        '#panel_description' => Markup::create('<strong>Markup</strong> tooltip'),
        '#bm_panels' => [
          'draggable' => FALSE,
          'removable' => TRUE,
          'x' => 2,
          'y' => 1,
          'width' => 5,
          'height' => 2,
        ],
      ],
      'plain' => [
        '#markup' => '<p>Plain</p>',
        '#title' => 'Plain panel',
        '#description' => 'Secondary description',
      ],
    ];
    $build = Panels::preRenderPanels($element);
    $panels = [];
    foreach ($build['#panels'] as $panel) {
      $panels[$panel['#panel_id']] = $panel;
    }
    $this->assertSame('Demo panel', $panels['demo']['#panel_title']);
    $this->assertSame('<strong>Markup</strong> tooltip', $panels['demo']['#panel_config']['panelDescription']);
    $this->assertSame((string) $element['demo']['#panel_description'], (string) $panels['demo']['#panel_description']);

    $this->assertSame('Plain panel', $panels['plain']['#panel_title']);
    $this->assertSame('Secondary description', $panels['plain']['#panel_config']['panelDescription']);
    $this->assertSame('Secondary description', (string) $panels['plain']['#panel_description']);
    $this->assertSame(2, $panels['demo']['#position']['x']);
    $this->assertSame(1, $panels['demo']['#position']['y']);
    $this->assertSame(5, $panels['demo']['#position']['width']);
    $this->assertSame(2, $panels['demo']['#position']['height']);
    $this->assertTrue($panels['demo']['#show_meta']);
  }

}
