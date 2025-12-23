<?php

declare(strict_types=1);

namespace Drupal\bm_notify;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\bm_notify\Ajax\NotifyCommand;

final class NotifyService {

  private array $queue = [];

  public const TYPE_STATUS = 'status';
  public const TYPE_WARNING = 'warning';
  public const TYPE_ERROR = 'error';
  public const TYPE_INFO = 'info';

  public function addNotification(
    string $message,
    string $type = 'info',
    int $timeout = 4000
  ): void {
    $this->queue[] = [
      'message' => $message,
      'type' => $type,
      'timeout' => $timeout,
    ];
  }

  /**
   * Whether notifications are queued.
   */
  public function hasNotifications(): bool {
    return !empty($this->queue);
  }

  /**
   * Messenger-style aliases.
   */
  public function addStatus(string $message, int $timeout = 4000): void {
    $this->addNotification($message, self::TYPE_STATUS, $timeout);
  }

  public function addWarning(string $message, int $timeout = 4000): void {
    $this->addNotification($message, self::TYPE_WARNING, $timeout);
  }

  public function addError(string $message, int $timeout = 4000): void {
    $this->addNotification($message, self::TYPE_ERROR, $timeout);
  }

  public function addInfo(string $message, int $timeout = 4000): void {
    $this->addNotification($message, self::TYPE_INFO, $timeout);
  }

  public function addToResponse(AjaxResponse $response): void {
    // Defensive: ensure the command class is available even if autoload cache is stale.

    foreach ($this->queue as $item) {
      $response->addCommand(new NotifyCommand($item));
    }
    $this->queue = [];
  }

}
