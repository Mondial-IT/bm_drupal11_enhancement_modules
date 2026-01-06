<?php

declare(strict_types=1);

namespace Drupal\bm_tokens\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Redirects to the BM Tokens help topic overview.
 */
final class HelpRedirectController extends ControllerBase {

  /**
   * Redirects to the top-level help topic.
   */
  public function overview(): RedirectResponse {
    $url = Url::fromRoute('help.help_topic', [
      'module' => 'bm_tokens',
      'id' => 'bm_tokens.overview',
    ]);
    return new RedirectResponse($url->toString());
  }

}
