<?php

declare(strict_types=1);

namespace Drupal\bm_aggrid\Controller;

use Drupal\bm_aggrid\Service\AggridConfigService;
use Drupal\bm_aggrid\Service\AggridDataService;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AggridAjaxController extends ControllerBase {

  public function __construct(
    protected AggridConfigService $configService,
    protected AggridDataService $dataService,
    protected CsrfTokenGenerator $csrfToken,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('bm_aggrid.config'),
      $container->get('bm_aggrid.data'),
      $container->get('csrf_token'),
    );
  }

  public function loadData(string $config_id, Request $request): JsonResponse {
    $configuration = $this->configService->getDisplayConfig($config_id);
    if (!$configuration) {
      return new JsonResponse(['status' => 'error', 'message' => $this->t('Invalid configuration.')], 404);
    }
    $offset = max(0, (int) $request->query->get('offset', 0));
    $limit = max(1, (int) $request->query->get('limit', $configuration['page_size'] ?? 50));
    $data = $this->dataService->getEntityData(
      $configuration['entity_type'],
      $configuration['bundle'],
      $configuration['fields'] ?? [],
      $limit,
      $offset,
      $request->query->all(),
    );

    return new JsonResponse([
      'status' => 'ok',
      'data' => $data,
    ]);
  }

  public function saveCell(Request $request): JsonResponse {
    $token = $request->headers->get('X-CSRF-Token');
    if (!$this->csrfToken->validate($token ?? '', 'bm_aggrid.save_cell')) {
      return new JsonResponse(['status' => 'error', 'message' => $this->t('Invalid CSRF token.')], 403);
    }

    $payload = json_decode($request->getContent() ?? '[]', TRUE);
    if (!is_array($payload)) {
      return new JsonResponse(['status' => 'error', 'message' => $this->t('Malformed payload.')], 400);
    }

    $result = $this->dataService->saveCellValue($payload);
    $status = $result['status'] ?? 'error';
    $code = $status === 'ok' ? 200 : 400;
    return new JsonResponse($result, $code);
  }

}
