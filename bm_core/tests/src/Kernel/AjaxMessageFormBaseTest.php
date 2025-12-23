<?php

declare(strict_types=1);

namespace Drupal\Tests\bm_core\Kernel;

use Drupal\bm_core\Form\AjaxMessageFormBase;
use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests for AjaxMessageFormBase.
 */
class AjaxMessageFormBaseTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
  ];

  /**
   * Ensures embedded mode renders container and disables core messages.
   */
  public function testEmbeddedModeBuild(): void {
    $form = new StubEmbeddedForm();
    $form_state = new FormState();
    $built = $form->buildForm([], $form_state);

    $this->assertArrayHasKey('#disable_messages', $built);
    $this->assertTrue($built['#disable_messages']);
    $this->assertArrayHasKey('messages', $built, 'Embedded mode renders a messages container.');
    $this->assertSame('ajax-messages', $built['messages']['#attributes']['id']);
  }

  /**
   * Ensures dialog mode attaches dialog library and no messages container.
   */
  public function testDialogModeBuild(): void {
    $form = new StubDialogForm();
    $form_state = new FormState();
    $built = $form->buildForm([], $form_state);

    $this->assertArrayHasKey('#disable_messages', $built);
    $this->assertTrue($built['#disable_messages']);
    $this->assertArrayNotHasKey('messages', $built, 'Dialog mode does not render an embedded messages container.');
    $attached = $built['#attached']['library'] ?? [];
    $this->assertContains('core/drupal.dialog.ajax', $attached);
  }

  /**
   * Messages render commands are produced.
   */
  public function testRenderMessagesCommands(): void {
    $form = new StubEmbeddedForm();
    $this->container->get('messenger')->addStatus('Test message');
    $response = new \Drupal\Core\Ajax\AjaxResponse();
    $form->exposeAppendMessages($response);
    $this->assertNotEmpty($response->getCommands(), 'Rendered commands are appended.');
  }

}

/**
 * Stub form for embedded mode.
 */
class StubEmbeddedForm extends AjaxMessageFormBase {

  public function getFormId(): string {
    return 'stub_embedded_form';
  }

  public function exposeAppendMessages(\Drupal\Core\Ajax\AjaxResponse $response): void {
    $this->appendMessages($response);
  }

}

/**
 * Stub form for dialog mode.
 */
class StubDialogForm extends AjaxMessageFormBase {

  protected string $messageMode = self::MESSAGE_MODE_DIALOG;

  public function getFormId(): string {
    return 'stub_dialog_form';
  }

}
