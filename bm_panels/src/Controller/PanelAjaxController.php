<?php

declare(strict_types=1);

namespace Drupal\bm_panels\Controller;

use Drupal\bm_panels\Service\PanelService;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles AJAX interactions for saving and loading panel state.
 */
class PanelAjaxController extends ControllerBase {

  /**
   * PanelAjaxController constructor.
   */
  public function __construct(
    protected PanelService $panelService,
    protected CsrfTokenGenerator $csrfTokenGenerator,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('bm_panels.service'),
      $container->get('csrf_token')
    );
  }

  /**
   * Loads saved state for a panel collection.
   */
  public function loadState(string $collection): JsonResponse {
    $state = $this->panelService->getPanelState($collection);
    return new JsonResponse([
      'status' => 'ok',
      'state' => $state,
    ]);
  }

  /**
   * Saves the current state for a panel collection.
   */
  public function saveState(string $collection, Request $request): JsonResponse {
    $headerToken = $request->headers->get('X-CSRF-Token') ?? '';
    if (!$this->csrfTokenGenerator->validate($headerToken, 'bm_panels.state.' . $collection)) {
      return new JsonResponse([
        'status' => 'error',
        'message' => $this->t('Invalid CSRF token.'),
      ], 403);
    }

    $payload = json_decode($request->getContent() ?: '[]', TRUE) ?? [];
    if (!is_array($payload) || !isset($payload['panels']) || !is_array($payload['panels'])) {
      return new JsonResponse([
        'status' => 'error',
        'message' => $this->t('Malformed payload.'),
      ], 400);
    }

    $this->panelService->savePanelState($collection, $payload);

    return new JsonResponse([
      'status' => 'ok',
    ]);
  }

}
