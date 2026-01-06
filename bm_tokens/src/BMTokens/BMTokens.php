<?php
declare(strict_types=1);

namespace Drupal\bm_tokens\BMTokens;

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Provides Blue Marloc custom tokens.
 */
final class BMTokens {

  /**
   * Defines token info.
   */
  public function getTokenInfo(): array {
    return [
      'types' => [
        'bluemarloc' => [
          'name' => t('Blue Marloc'),
          'description' => t('Tokens BlueMarloc context.'),
        ],
        'node-example' => [
          'name' => t('Node examples'),
          'description' => t('Demonstration of tokens with a node context.'),
          'needs-data' => 'node',
        ],
      ],
      'tokens' => [
        'bluemarloc' => [
          'year-next-month' => [
            'name' => t('YYYY-MM of next month'),
          ],
          'term' => [
            'name' => t('Term Display'),
            'description' => t('Display a taxonomy term with a specified view mode, e.g. [bluemarloc:term:261:taxonomy_term_micro].'),
          ],
          'basic-node' => [
            'name' => t('Basic node'),
            'type' => 'node',
          ],
        ],
        'node-example' => [
          'timestamps' => [
            'name' => t('Timestamps'),
          ],
        ],
      ],
    ];
  }

  /**
   * Replaces tokens.
   */
  public function replaceTokens(string $type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
    $replacements = [];

    switch ($type) {
      case 'bluemarloc':
        foreach ($tokens as $key => $token) {
          $parts = explode(':', (string) $key);

          switch ($key) {
            case 'basic-node':
              if ($node = Node::load(1)) {
                $replacements[$token] = $node->label();
              }
              break;

            case 'year-next-month':
              $replacements[$token] = date('Y-m', strtotime('first day of next month'));
              break;

            default:
              if ($parts[0] === 'term' && count($parts) === 3) {
                $tid = (int) $parts[1];
                $view_mode = $parts[2];
                if ($term = Term::load($tid)) {
                  $view_builder = \Drupal::entityTypeManager()->getViewBuilder('taxonomy_term');
                  $build = $view_builder->view($term, $view_mode);
                  $output = (string) \Drupal::service('renderer')->render($build);
                  $replacements[$token] = $output;
                }
                else {
                  $replacements[$token] = t('Invalid term');
                }
              }
              break;
          }
        }

        $node_tokens = \Drupal::token()->findWithPrefix($tokens, 'basic-node');
        if (!empty($node_tokens) && ($node = Node::load(1))) {
          $replacements += \Drupal::token()->generate('node', $node_tokens, ['node' => $node], $options, $bubbleable_metadata);
        }
        break;

      case 'node-example':
        foreach ($tokens as $key => $token) {
          if ($key === 'timestamps' && isset($data['node']) && $data['node'] instanceof Node) {
            /** @var \Drupal\node\Entity\Node $n */
            $n = $data['node'];
            $replacements[$token] = $n->getCreatedTime() . ' and ' . $n->getChangedTime();
          }
        }
        break;
    }

    return $replacements;
  }

}
