<?php
namespace Drupal\bm_aggrid\Controller;

use Drupal\bm_aggrid\Service\AggridConfigService;
use Drupal\bm_aggrid\Service\AggridDataService;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class AggridDisplayController extends ControllerBase implements ContainerInjectionInterface {

  public function __construct(
    protected AggridConfigService $configService,
    protected AggridDataService $dataService,
    protected RendererInterface $renderer,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('bm_aggrid.config'),
      $container->get('bm_aggrid.data'),
      $container->get('renderer'),
    );
  }

  public function displayGrid(string $config_id, Request $request): array {
    $configuration = $this->configService->getDisplayConfig($config_id);
    if (!$configuration) {
      $this->messenger()->addError($this->t('Unknown AG Grid configuration.'));
      return [
        '#markup' => $this->t('The requested grid configuration was not found.'),
      ];
    }

    $fields = $configuration['fields'] ?? [];
    $column_defs = $this->dataService->getColumnDefinitions(
      $configuration['entity_type'],
      $configuration['bundle'],
      $fields
    );
    $data = $this->dataService->getEntityData(
      $configuration['entity_type'],
      $configuration['bundle'],
      $fields,
      $configuration['page_size'] ?? 50,
      0,
    );

    $defaults = [
      'theme' => 'ag-theme-quartz',
      'row_height' => 44,
      'enable_pagination' => FALSE,
      'pagination_page_size' => $configuration['page_size'] ?? 50,
    ];
    $options = $configuration['options'] ?? [];
    $configuration['options'] = $options + $defaults;

    $build = [
      '#theme' => 'bm_aggrid_grid',
      '#attached' => [
        'library' => [
          'bm_aggrid/aggrid.base',
        ],
        'drupalSettings' => [
          'aggridDisplay' => [
            $config_id => [
              'config' => $configuration,
              'data' => $data,
              'columnDefs' => $column_defs,
            ],
          ],
        ],
      ],
      '#grid_id' => $config_id,
      '#configuration' => $configuration,
    ];

    return $build;
  }

}
