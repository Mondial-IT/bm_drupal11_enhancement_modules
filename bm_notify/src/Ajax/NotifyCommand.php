<?php

declare(strict_types=1);

namespace Drupal\bm_notify\Ajax;

use Drupal\Core\Ajax\CommandInterface;

final readonly class NotifyCommand implements CommandInterface {

  public function __construct(
    private array $payload
  ) {}

  public function render(): array {
    return [
      'command' => 'bmNotify',
      'payload' => $this->payload,
    ];
  }

}
