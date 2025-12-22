<?php
/*
 Copyright (c) Mondial-IT BV - Blue Marloc 2024
   Created on 2024-11-21 at 11:32:11
 */ /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace Drupal\bm_main\Form;


use Drupal;
use Drupal\bm_debug\BMDebugInfo\BMDebugInfo;
use Drupal\bm_fefq\Enum\FEFQAggridKeys;
use Drupal\bm_fefq\FEFQ_get\FEFQ_get;
use Drupal\bm_fefq\FEFQ_get\FEFQ_getAggrid;
use Drupal\bm_listings\BMListingFEFQInterface\BMListingFEFQInterface;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Exception;


/**
 *  # BMNodeTableSelectTemplateForm
 *
 *  ### The easiest way to create a feature rich Table Select form.
 *
 *  #### features:
 *  - extend your form with BMNodeTableSelectTemplateForm
 *  - fully ajax driven
 *  - provides a pager (buttons at the bottom)
 *  - provides a rows per page selector (at the top)
 *  - provides a header
 *  - provides a row selector
 *  - provides a footer
 *  - provides a simple means to add buttons (components) to your table
 *
 * ### how to:
 * - class YourForm extends BMNodeTableSelectTemplateForm
 * - $this->buildForm_heading(...)  pager and init
 * - $row = get_row_data(...)
 * - $this->buildForm_tableSelect(...)  creates the paged ajax table select
 * - update_nodes
 * - submit_form
 * - submit_AjaxCallback_ComponentState - general callback all components
 * - submit_AjaxCallback_5246_1 - specific callback for component 5246_1
 *
 *
 *  A base form class which you can use as extend
 *  to create a fully functional Ajax driven node edit table select
 *  with features such ajax updates, ajax processing
 *  number of rows on a page selection, pager next and previous.
 */
abstract class BMNodeTableSelectTemplateForm extends FormBase implements BMNodeTableSelectTemplateFormInterface {

  const PARENT_DEFAULT_NR_OF_ROWS = 1000;

  const KEY_PAGER_PREVIOUS_BTN = 'pager_previous_button';

  const HTML_ID_PAGER_PREVIOUS_BTN = '[data-drupal-selector="edit-pager-previous-button"]';

  const KEY_PAGER_AT_PAGE = 'pager_at_page';

  const HTML_ID_PAGER_AT_PAGE = '[data-drupal-selector="edit-pager-at-page"]';

  const KEY_PAGER_NEXT_BTN = 'pager_next_button';

  const HTML_ID_PAGER_NEXT_BTN = '[data-drupal-selector="edit-pager-next-button"]';

  const BM_D_KEY = __CLASS__;

  // this file uses bm_d() to display debug messages
  // using the BMDebug class
  // This key can be enabled/disabled from the BMDebug settings form

  const BM_D_KEY_TRACE = __CLASS__ . 'trace';

  const BM_D_KEY_AGGRID = __CLASS__ . 'aggrid';

  protected array $rows;

  // when bm_d(key... is used, it calls back once to request information about this key
  // and use it on the debug settings form to enable/disable it dynamically
  //

  private mixed $componentState = [];

  private array $values_and_keys;

  private NodeInterface $node;

  static public function bm_d_callback($key, $BMDbgInfo): BMDebugInfo|false {
    switch ($key) {
      case self::BM_D_KEY:
        $BMDbgInfo->module = 'bm_main';
        $BMDbgInfo->description = 'Debug BMNodeTableSelectTemplateForm ';
        $BMDbgInfo->default = 0;
        $BMDbgInfo->screen_dsm_log = 'dsm';
        return $BMDbgInfo;
      case self::BM_D_KEY_TRACE:
        $BMDbgInfo->module = 'bm_main';
        $BMDbgInfo->description = 'Debug BMNodeTableSelectTemplateForm display called functions';
        $BMDbgInfo->default = 0;
        $BMDbgInfo->screen_dsm_log = 'dsm';
        return $BMDbgInfo;
      case self::BM_D_KEY_AGGRID:
        $BMDbgInfo->module = 'bm_main';
        $BMDbgInfo->description = 'Debug BMNodeTableSelectTemplateForm AG Grid functions';
        $BMDbgInfo->default = 0;
        $BMDbgInfo->screen_dsm_log = 'dsm';
        return $BMDbgInfo;
      default:
        break;
    }
    return FALSE;
  }

  /**
   * ### getFormId
   * ## Override from parent
   *
   * @return string
   */
  abstract public function getFormId(): string;
  //{
  //return 'bm-node-tableselect-template-form';
  //}

  /**
   * # get_entity__ValuesAndChains__asOptions
   * #### a helper method to retrieve field values for report columns
   * ## sets this->values_and_keys to ['values'=> ...]
   *
   * Returns (Example):
   * ```
   * 'field__listing__field_listing_phone'=>["N71.0"=>"+013 22632270"]
   * ```
   *
   * * The method receives an array of FEFQ names (ListingField enums) from the getRows method using the Node.
   * * For each of the names (fields) the value is retrieved.
   * * When there are multiple values, the values are flattened into a single string value.
   * * Together with the values, the keys (nids, tids, paragraph ids are stored in a keys array).
   * * ajax-update: This `keys`-array is added onto the table select data in column `keys` to facilitate field updating
   * through ajax
   *
   * @param NodeInterface $node
   * @param array         $array_of_field_enums
   *
   * @return array|mixed name=>value array
   * @throws Exception
   * @see interestAdmninForm for example
   */
  public function get_entity__ValuesAndChains__asOptions(NodeInterface $node, array $array_of_field_enums): mixed {
    bm_d(self::BM_D_KEY_TRACE, __FUNCTION__, ' trace NodeTemplateForm:', 'screen');


    // [
    //  'values'=>[enum_label=>value, ... ]
    //  'chains'=>[enum_label=>access chain, ... ]
    // ]
    $FEFQValuesAndChains = FEFQ_get::get_entity_fields__ValuesAndChains($node, $array_of_field_enums);

    bm_d(self::BM_D_KEY, $FEFQValuesAndChains, ' VALUES AND CHAINS', 'dsm');

    // Row construction, placing retrieval chains as keys in separate column.
    // returns the flat values and the key to find and update the field with
    $options['keys'] = [];
    foreach ($FEFQValuesAndChains->values as $enumLabel => $value) {
      $options['keys'][$enumLabel] = $FEFQValuesAndChains->chains[$enumLabel];
      $options[$enumLabel] = $value;
    }
    bm_d(self::BM_D_KEY, $options, ' the resulting prepared options:', 'dsm');// note keys problem and $value term problem.
    return $options;
  }

  /**
   * ### buildForm: structures the buildForm for the child
   *
   * @noinspection PhpMultipleClassDeclarationsInspection
   * @throws Exception
   */
  public
  function buildForm(array $form, FormStateInterface $form_state): array {

    bm_d(self::BM_D_KEY_TRACE, __function__ . '- in template. Pager is on page: ' . (int) $form_state->get('pager_page'), 'trace NodeTemplateForm BMNodeTableSelectTemplateForm:', 'screen');
    $this->values_and_keys = ['values' => [], 'keys' => []];


    $this->restore_storage($form_state);


    $form['#attributes']['class'][] = 'bm-node-table-select-template-form';

    // not used with aggrid.
    // loads css and js and get bm_component.js to run as well
    // $form['#attached']['library'][] = 'bm_main/bm_node_table_select_template_form_interface';// css

    // Attach the DataTable library to the tableselect.
    $form['#attached']['library'][] = 'bm_main/aggridjs';
    $form['#attached']['library'][] = 'ziston/tippyjs';
    $form['#attached']['library'][] = 'ziston/bm_tippyjs';

    $this->buildIntroText($form, $form_state);

    // pager and init
    $this->buildForm_heading($form, $form_state);


    // you can add components to the ribbon in $form['ribbon']['components']
    $this->buildForm_components($form, $form_state);

    $rows = $this->get_rows($form, $form_state);

    // edit is always edit from the template
    $header = array_merge(
      [
        'row_id' => ['data' => 'Id', 'class' => ['id-column', 'ag--maxWidth--110'], 'headerTooltip' => 'Entity Id'],
        'row_nr' => ['data' => 'Nr', 'class' => ['ag--maxWidth--90'], 'headerTooltip' => 'Row number'],
        'changed' => ['data' => 'U', 'class' => ['boolean-column', 'ag--maxWidth--90'], 'headerTooltip' => 'Updated'],
        'edit' => ['data' => 'Edit', 'class' => ['link-column', 'ag--maxWidth--110']],
        'keys' => [
          'data' => 'Update Keys',
          'class' => ['system-column'],
          'headerTooltip' => 'field updater information',
        ],//'ag--hide--true'
      ],
      $this->get_tableHeader($rows));

    $tableSelect = [
      '#type' => 'tableselect',
      '#header' => $header,// note array(key1=>value1, key2=>value2 where key=row key
      '#options' => $rows,// not array( 0=>array(key1=>value1, key2=>value2) where key=header key.
      // not rows cells count must be equal every row and header cells must match row cell count
      '#empty' => t('No content available.'),
    ];
    // we skip this with aggrid
    // $this->buildForm_tableSelect($tableSelect, $form, $form_state);

    $form['aggrid'] = [
      '#type' => 'markup',
      '#markup' => '<div id="table-select-ajax-ag-grid" class="ag-theme-quartz-dark">agGrid loading</div>',
    ];

    $form['execute_bulkUpdate'] = [
      '#type' => 'submit',
      '#value' => 'Update selected',
      '#prefix' => '<div class="submit-buttons-wrapper">',
      '#submit' => ['::submitForm_update_nodes'],
      '#validate' => ['::validateForm_update_nodes'],
      '#ajax' => [
        'callback' => '::submit_AjaxCallback',
        'wrapper' => 'table-select-ajax-replace', // Replace with your tableselect element's wrapper ID.
      ],
    ];
    $this->buildForm_submitButtons($form, $form_state);
    $form['submit_buttons_close_wrapper'] = [
      '#type' => '#markup',
      '#markup' => '</div>',
    ];

    // add ajax where missing
    foreach ($form as &$element) {
      if (isset($element['#type']) && $element['#type'] == 'submit') {
        // add ajax when not present
        if (!isset($element['#submit'])) {
          $element['#submit'] = ['::submitForm_update_nodes'];
          $element['#validate'] = ['::validateForm_update_nodes'];
          $element['#ajax'] = [
            'callback' => '::submit_AjaxCallback',
            'wrapper' => 'table-select-ajax-replace', // Replace with your tableselect element's wrapper ID.
          ];
        }
      }
    }


    $form['status_messages'] = [
      '#markup' => '<div id="status-messages">' . $this->renderStatusMessages() . '</div>',
    ];

    // pass on to aggrid  $rows_per_page=100;
    $agGridOptions = $this->buildForm_aggrid($tableSelect);
    $form['#attached']['drupalSettings']['agGridOptions'] = $agGridOptions;


    bm_d(self::BM_D_KEY_AGGRID, $form, 'form in build function');
    return $form;
  }

  private
  function restore_storage(FormStateInterface $form_state): void {
    $this->componentState = $form_state->get('components');
  }

  /**
   * ### buildIntroText add intro text (markup) of the form
   * Example:
   * ```
   * $form['introtext'] = [
   * '#type' => 'markup',
   * '#markup' => '<h1>BM Node Tableselect Template Form</h1>',
   * ];
   * ```
   *
   * @param array              $form
   * @param FormStateInterface $form_state
   *
   * @return void
   */
  abstract public
  function buildIntroText(array &$form, FormStateInterface $form_state): void;

  /**
   * ### buildForm_heading
   *
   * ### this method needs to be called in the top of the buildForm method
   *
   *
   * @param array              $form
   * @param FormStateInterface $form_state
   *
   * @return void
   */
  protected function buildForm_heading(array &$form, FormStateInterface $form_state): void {

    bm_d(self::BM_D_KEY_TRACE, __function__, 'trace NodeTemplateForm', 'screen');

    // pager
    $no_of_rows = NULL;
    $userInput = $form_state->getUserInput();

    if (isset($userInput['no_of_rows'])) {
      $no_of_rows = (int) $userInput['no_of_rows'];
    }

    if ($no_of_rows !== NULL && ($no_of_rows !== (int) $form_state->get('rows_per_page'))) {
      $this->init_form($form_state);
    }

    //bm_d(self::BM_D_KEY_TRACE, __function__.' no of rows:'.$no_of_rows, 'trace NodeTemplateForm', 'screen');
    if ($form_state->get('pager_page') === NULL
      || (int) $form_state->get('pager_page') === -1
      || $no_of_rows === NULL) {
      if (!$no_of_rows) {
        $no_of_rows = $this->getNrOfRows();
      }
      $this->init_form($form_state);
    }

    $trigger = $form_state->getTriggeringElement();
    $callback = $trigger['#ajax']['callback'] ?? NULL;

    switch ($callback) {
      case '::AjaxCallback_agGrid_nextPage':
      case '::refreshTableSelect_nextPage_AjaxCallback':
        $page = (int) $form_state->get('pager_page');
        $pages = (int) $form_state->get('pager_pages');

        bm_d(self::BM_D_KEY_TRACE, __function__ . '- Page:' . $page . ' of:' . $pages, 'trace NodeTemplateForm', 'screen');
        if ($page < ($pages - 1)) {
          $nextPage = $page + 1;
          $form_state->set('pager_page', $nextPage);
          bm_d(self::BM_D_KEY_TRACE, __function__ . '-nxt Set to: page:' . $nextPage . ' of:' . $pages, 'trace NodeTemplateForm', 'screen');
          //dsm($form_state,'pager_page?'.$nextPage);
        }
        break;
      case '::refreshTableSelect_previousPage_AjaxCallback':
        if ((int) $form_state->get('pager_page')) {
          $form_state->set('pager_page', $form_state->get('pager_page') - 1);
          bm_d(self::BM_D_KEY_TRACE, __function__ . '- prev. Set to: page:' . $form_state->get('pager_page'), 'trace NodeTemplateForm', 'screen');
        }
        break;
      case '::AjaxCallback_agGridOptions':
        $form_state->set('pager_page', 0);
        break;
      case '::refreshTableSelectAjaxCallback':// when clicked on the page in < page >
        $this->init_form($form_state);
        break;
      // remember component states
      case '::submit_AjaxCallback_component_states':
        $input = json_decode($form_state->getUserInput()['component_states']);
        $componentFormId = $input->componentFormId;
        $state = $input->state;
        // keep track of component states
        $storage = $form_state->get('components');
        $storage[$componentFormId] = $state;
        $form_state->set('components', $storage);
        bm_d(self::BM_D_KEY, $storage, 'storage component state' . __FUNCTION__);

        // alternative
        $this->componentState = $storage;

        break;
      // called last
      case '::submit_AjaxCallback':

      default:

        break;
    }
    $form['ribbon']['pre'] = [
      '#type' => 'markup',
      '#markup' => '<div class="table-select-ribbon">',
    ];

    $form['ribbon']['components'] = [];

    /* not used with aggrid
    // clear cache after changing default, as it gets stuck ...
    $form['ribbon']['no_of_rows'] = [
      '#type' => 'select',
      '#options' => [

          5 => '5',
          10 => '10',
          25 => '25',
          50 => '50',
          100 => '100',
          200 => '200',
          500 => '500',
          1000 => '1000',

      ],
      '#default_value' => 100,
      '#title' => t('Rows'),
      '#ajax' => [
        'callback' => '::submit_AjaxCallback',
      ],
    ];
    */
    // this form element receives the component states from component buttons
    // in form component_id:state
    $form['ribbon']['component_states'] = [
      '#type' => 'textfield',
      '#value' => 'Hidden component state collector',
      '#attributes' => ['style' => 'display:none;'],
      '#ajax' => [
        'event' => 'change',
        'callback' => '::submit_AjaxCallback_component_states',
      ],
    ];

    $form['ribbon']['post'] = [
      '#type' => 'markup',
      '#markup' => '</div>',
    ];


  }
  /*{
    bm_d(self::BM_D_KEY_TRACE, __function__, 'trace NodeTemplateForm', 'screen');
    return Drupal::entityQuery('node')
      ->condition('type', 'invoice')
      // ->condition('field_interaction_listing', 499, '=')
      ->accessCheck(FALSE)
      ->execute();
  }
  */

  /**
   * ### init_form: Initialises and gets the node list
   *
   * @param     $form_state
   * @param int $nrOfRows
   *
   * @return void
   */
  private function init_form($form_state): void {

    bm_d(self::BM_D_KEY_TRACE, __function__, 'trace NodeTemplateForm', 'screen');
    $nrOfRows = $this->getNrOfRows();
    $entity_ids = $this->get_entity_ids($form_state, $nrOfRows);

    //    $queryData = BMListingEntityQuery::list('test_label_for_last_filter_used', []);
    //    $entity_ids = $queryData['nids'];
    $form_state->set('rows_per_page', $nrOfRows);

    $form_state->set('nids_array', array_chunk($entity_ids, $form_state->get('rows_per_page')));
    //dsm($form_state->get('nids_array'),'nids array');
    $form_state->set('pager_page', 0);
    $form_state->set('pager_pages', (int) (count($entity_ids) / $form_state->get('rows_per_page')) + 1);
    $form_state->set('total', count($entity_ids));
    $form_state->set('todo', $form_state->get('total'));
    $form_state->set('init', TRUE);
  }
  /*{
    bm_d(self::BM_D_KEY_TRACE, __function__, 'trace NodeTemplateForm', 'screen');
    return [
      'id' => 'Id',
      'description' => 'Description',
      'client' => 'Client',
      'country',
      'invoice_due_date' => 'Due Date',
      'open_ex_interest' => 'Open (ex.interest)',
      'interest' => 'Interest',
      'open_incl_interest' => 'Open (with interest)',
      'interest_today' => 'ytd interest',
      'explanation',
    ];
  }
*/

  /**
   * # getNrOfRows
   * * set the number of rows to retrieve
   *
   * * Optional method to set nr of rows.
   *
   * @return int
   */
  public function getNrOfRows(): int {
    return self::PARENT_DEFAULT_NR_OF_ROWS;// child can define own
  }
  /*{
    bm_d(self::BM_D_KEY_TRACE, __function__, 'trace NodeTemplateForm', 'screen');
    $row = [];
    // example getting field data from the entity using FEFQ interface
    $row['id'] = BMListingFEFQInterface::get_entity_field_value_chain($node, 'field_invoice_id');
    return $row;
  }
  */

  /**
   * ### get_entity_ids
   * * return a list of nids to get rows from for the table
   * Example:
   * ```
   * return Drupal::entityQuery('node')
   *    ->condition('type', 'invoice')
   *    ->condition('field_interaction_listing', 499, '=')
   *    ->accessCheck(TRUE)
   *    ->range(0, $nrOfRows)
   *    ->execute();
   * ```
   *
   * @param FormStateInterface $form_state
   * @param int                $nrOfRows
   *
   * @return array|int entity_ids
   */
  abstract public function get_entity_ids(FormStateInterface $form_state, int $nrOfRows): int|array;

  /**
   * **override in child**
   * ### buildForm_components
   * Define the components (buttons) in the ribbon.\
   * They are displayed above the table in an area called the ribbon.\
   *
   * **Usage**
   * ```$form['ribbon']['components']['your key']``` as basis
   * ```
   *  $nStateButton = new BMnStateButton(component_id: 'nstatebutton_off_on',
   *  component_term_label:'nstatebutton_off_on');
   *  $form['ribbon']['components']['nstatebutton_off_on'] = $nStateButton->get__with_label(asFormElement: TRUE);
   * ```
   *
   * In addition, you can use all methods of `BMnStateButton` to set the button states\
   * ```
   * $nStateButton->setUserLabel('Detail "Wettelijke rente" calculation');
   * $nStateButton->setTippy('Display the details of the calculation;hide the details of the calculation');
   * ```
   *
   *
   * * the *component_id* is used in the markup and to retrieve values
   * * as it is in markup, make sure has only valid characters.
   *
   * **Tip**
   * to retrieve the actual state where needed, use
   * ```
   *  $this->getComponentState($componentId)
   * ```
   *
   *
   * @param array              $form
   * @param FormStateInterface $form_state
   *
   * @return void
   * @throws Exception
   */
  public
  abstract function buildForm_components(array &$form, FormStateInterface $form_state): void;

  /**
   * ### Manage, get all rows (initially) or per changed row, row data
   * ### calls get_row_data per row with the loaded Node
   *
   * When form_state['init'] or no rows selected in form_state,
   *  then it loads the nodes to create all rows of a page [form_state rows_per_page]
   * else it processes `get_row_data()` from the selected rows.
   *
   * @param array              $form
   * @param FormStateInterface $form_state
   *
   * @return array
   */
  protected function get_rows(array &$form, FormStateInterface $form_state): array {
    bm_d(self::BM_D_KEY_TRACE, __function__, 'trace NodeTemplateForm', 'screen');

    $rowNrs = $form_state->getValue('table_select');

    $storage = $form_state->getStorage();
    $mutation_time_list = $storage['mutation_time_list'] ?? [];
    // first time
    if ($rowNrs === NULL || $form_state->get('init') === TRUE) {

      bm_d(self::BM_D_KEY, __function__ . '- Build nids per page array for page: ' . (int) $form_state->get('pager_page'), 'trace NodeTemplateForm', 'screen');
      // nids organized in pages chunks
      $nids_per_page_array = $form_state->get('nids_array');
      if (!$nids_per_page_array) {
        return [];
      }

      $nodes = Node::loadMultiple($nids_per_page_array[(int) $form_state->get('pager_page')]);
      //      bm_d(self::BM_D_KEY_TRACE, $mutation_time_list, 'trace NodeTemplateForm'.__FUNCTION__.' mutation_time_list:', 'screen');
      $rows = [];
      //$form_state->get('pager_page') * $form_state->get('rows_per_page');
      $rowNr = (int) $form_state->get('pager_page') * $form_state->get('rows_per_page');

      // in headers we find the - names we can use to retrieve the values of rows given a nid.

      foreach ($nodes as $node) {

        $this->node = $node;

        $nid = $node->id();
        $change = $node->getChangedTime();

        $last_mutation = $mutation_time_list[$nid] ?? 0;
        if (!$last_mutation) {
          $mutation_time_list[$nid] = $change;
          $last_mutation = $change;
        }
        $changed = FALSE;
        $attributes = NULL;
        if ($last_mutation !== $change) {
          // change detected
          Drupal::messenger()->addStatus('1) Change detect:' . $nid . ' list:' . $last_mutation . ' node:' . $change);
          $attributes = ['class' => ['bgcolor-lightgreen']];
          $changed = TRUE;
        }


        $rowData = $this->get_row_data($node, $form, $form_state);

        bm_d(self::BM_D_KEY, $rowData, ' ROW', 'dsm');
        if (isset($rowData['keys'])) {
          $rowData['keys'] = $this->cacheFormKeys($rowData['keys']);
        }
        // see flatten_as_master_detail
        if (isset($rowData['fefq_flattened_master_detail'])) {

          foreach ($rowData as $rowId => $fefqRow) {
            if ($rowId == 'fefq_flattened_master_detail') {
              continue;
            }//header row
            // dsm($fefqRow,'Table select Processing this fefq row');

            $row = array_merge(
              [
                'row_id' => $nid,
                'row_nr' => $rowNr,
                'changed' => $changed,
                'edit' => Markup::create('<a class="" href="/node/' . $node->id() . '/edit">Edit</a>'),
              ],
              $fefqRow
            );
            if ($attributes) {
              $row['#attributes'] = $attributes;
              $rows[] = $row;
            }
            else {
              $row['#attributes'] = ['class' => ['']];
              //     $rows[] = $row;
            }

            $rows[] = $row;
            $rowNr++;
          }

        }
        else {


          $row = array_merge(
            [
              'row_id' => $nid,
              'row_nr' => $rowNr,
              'changed' => $changed,
              'edit' => Markup::create('<a class="" href="/node/' . $node->id() . '/edit">Edit</a>'),
              // remember where the fields came from, to be able to update them.
              'keys' => json_encode($this->values_and_keys['keys']),
            ],
            $rowData
          );
          if ($attributes) {
            $row['#attributes'] = $attributes;
            $rows[] = $row;
          }
          else {
            $row['#attributes'] = ['class' => ['']];
            $rows[] = $row;
          }

          $rowNr++;
        }
      }

    }
    else {
      bm_d(self::BM_D_KEY, __function__ . '- changed rows?', 'trace NodeTemplateForm', 'screen');

      // changed rows
      $rows = $form_state->getCompleteForm()['ajax_table_select']['table_select']['#options'];
      foreach ($rowNrs as $rowNr) {
        if ($rowNr === 0) {
          break;
        }
        $entity_id = $rows[$rowNr][0]['Id'];
        $node = Node::load($entity_id);
        if ($node) {

          // detect change
          $nid = $node->id();
          $change = $node->getChangedTime();
          $last_mutation = $mutation_time_list[$nid] ?? 0;
          $attributes = NULL;
          $changed = FALSE;
          if (!$last_mutation) {
            $mutation_time_list[$nid] = $change;
            $last_mutation = $change;
          }
          else {
            if ($last_mutation !== $change) {
              // change detected
              Drupal::messenger()
                ->addStatus('2) Change detect:' . $nid . ' list:' . $last_mutation . ' node:' . $change);
              $changed = TRUE;
              $attributes = ['class' => ['bgcolor-lightgreen']];
            }
          }
          $row = array_merge(
            [
              'row_id' => $nid,
              'row_nr' => $rowNr,
              'changed' => $changed,
              'edit' => Markup::create('<a class="" href="/node/' . $node->id() . '/edit">Edit</a>'),
            ],
            $this->get_row_data($node, $form, $form_state)
          );
          // remember where the fields came from, to be able to update them.
          //dsm($row,__LINE__.'ROW');
          if (isset($row['keys'])) {
            $row['keys'] = $this->cacheFormKeys($this->values_and_keys['keys']);// json_encode($this->values_and_keys['keys'])
          }
          if ($attributes) {
            $row['#attributes'] = $attributes;
            $rows[$rowNr] = $row;
          }
          else {
            $row['#attributes'] = ['class' => ['']];
            $rows[$rowNr] = $row;
          }
        }
      }
    }

    $storage = $form_state->getStorage();
    $storage['mutation_time_list'] = $mutation_time_list;
    $form_state->setStorage($storage);
    bm_d(self::BM_D_KEY, $form_state->get('pager_page') * $form_state->get('rows_per_page') . ' to ' . $rowNr, __FUNCTION__, 'screen');
    // dsm($rows);
    return $rows;
  }

  /**
   * **override in child**
   * ### get_row_data: Create one row of data based from the passed node
   * Example:
   * ```
   * // example getting field data from the entity using FEFQ interface
   *  $row['field_invoice_id'] = BMListingFEFQInterface::get_entity_field_value_chain($node, 'field_invoice_id');
   *  return $row;
   * ```
   *
   * @param NodeInterface      $node
   * @param array &            $form ,
   * @param FormStateInterface $form_state
   *
   * @return array
   */
  abstract public function get_row_data(NodeInterface $node, array &$form, FormStateInterface $form_state): array;
  /*{
    bm_d(self::BM_D_KEY_TRACE, __function__, 'trace NodeTemplateForm', 'screen');
    // formComponentId  will form a unique id in the browser component
    // where you can create a submit_AjaxCallback_'formComponentId' method for receiving the trigger and state
    // but better test the state in the build form, using $this->getComponentState($formComponentId);
    // example
    $nStateButton2 = new BMnStateButton(component_id: 'nstatebutton_off_on', component_term_label: 'nstatebutton_off_on');
    $nStateButton2->setUserLabel('Detail "Wettelijke rente" calculation');
    $nStateButton2->setTippy('Display the details of the calculation;hide the details of the calculation');
    $form['ribbon']['components']['nstatebutton_off_on'] = $nStateButton2->get__with_label(asFormElement: TRUE);
    // retrieve the state with $this->getComponentState('nstatebutton_off_on');
  }
  */

  /**
   * @param $keys
   *
   * @return string
   */
  protected function cacheFormKeys($keys): string {
    $cid = 'FEFQ' . uniqid();
    $expiration = Drupal::time()->getRequestTime() + 3600;
    Drupal::cache('bluemarloc')->set($cid, $keys, $expiration, ['ajaxFormKeys']);
    return $cid;
  }

  /**
   *
   * ## get_tableHeader: create array of table headers
   * * use `['data'=>'heading','class'=>['a class','a class']]` for extra formatting
   *
   * #### existing classes
   * * id-column renders a spinner when loading
   * * money-column makes it € 999.999,00
   * * link-column makes it a button with the link
   * * html-column  makes it markup
   * * date-column  makes it ..
   * * datestring-column'makes it YYYY-MM-DD
   * * button-column  makes it a button with a modal display
   * * button-tooltip-column  makes it a button on hover tooltip
   * * boolean-column makes it a checkbox
   *
   * #### ag--attributename--value
   *
   * you can further add classes to control ag grid attributes
   * They are placed in the header of the column definition
   * examples:
   * * ag--maxWidth--100,
   * * ag--wrapText--true
   * * ag--autoHeight--true
   * * ag--hide--true note hide--false is still hide...
   *
   * ## server side cell-classes and tooltips
   * *Classes*
   * Add an extra column {field_name}_classess and place the class for the cell as value
   * It will be picked up by aggrid and applied into the cell. Available colors:
   * * .bgcolor-{colors}
   * * .fgcolor-{colors}
   *
   * The field is formatted to also store tooltips for those classes on hover.
   * format:
   * 'active-class1 active-class2;class1:tooltip for class 1;class2:tooltip for class2'
   * * separate classes by space
   * * separate key:tooltipText by ;
   * * separate tooltipText from the key (className) by :
   *  example:
   * ```
   *  bgcolor-green;bgcolor-lightgreen:phone numbers match;bgcolor-lightgreen:match 7 numbers;
   * ```
   *
   *
   *
   *
   * #### The parameter `row` can be used to extract headings.
   * Example:
   * ```
   * $heading = [
   *  'title' => ['data' => 'Title','class' => ['field'],],
   *  'field_listing_street' => ['data' => 'Street','class' => ['field','ag--editable--true'],],
   *  'field_listing_phone'=>['data' => 'Phone','class' => ['field','ag--maxWidth--200','ag--editable--true'],],
   *  'field_listing_phone_classes'=>['data' => 'Phone classes','class' => ['ag--display--none'],],
   *  'place_housenr'=>['data' => 'PLACE HouseNr','class' => ['field','ag--maxWidth--80'],],
   *  'field_listing_city' => ['data' => 'City', 'class' => ['ag--editable--true'], ],
   * ```
   * * note the use of the key as field name, which enables ag--editable--true to work.
   * * note the use field_listing_phone_classes to preset color classes on cells as value in this column
   *
   * @param array $row one row to use to extract headings
   *
   * @return string[] array of headings (optional with classes)
   */
  abstract public function get_tableHeader(array $row): array;
  /*
   {
    bm_d(self::BM_D_KEY_TRACE, __function__, 'trace NodeTemplateForm', 'screen');
    $form['example'] = [
      '#type' => 'submit',
      '#value' => 'example',
    ];
  }
  */

  /**
   * **override in child**
   * ### buildForm_submitButtons
   * * use this method to define additional submit buttons at the bottom of the table
   * * you don't need to add ajax handling or a submit function, it is added automatically
   *
   * #### Example (note automatically transformed to an ajax submit)
   * Will create a button, which ajax submits,\
   * and calls `update_nodes` with the selected rows
   * ```
   *  $form['example']=[
   *    '#type'=>'submit',
   *    '#value'=>'example',
   *  ];
   * ```
   */
  abstract public
  function buildForm_submitButtons(array &$form, FormStateInterface $form_state): void;

  /*
  {
    $form['introtext'] = [
      '#type' => 'markup',
      '#markup' => '<h1>BM Node Tableselect Template Form</h1>',
    ];

  }
  */

  /**
   * ### renderStatusMessages: displays status messages via ajax on ajax calls
   *
   * @return MarkupInterface|string
   */
  protected
  function renderStatusMessages(): MarkupInterface|string {
    bm_d(self::BM_D_KEY_TRACE, __function__, 'trace NodeTemplateForm', 'screen');
    $messages = Drupal::messenger()->messagesByType('status');
    return Drupal::theme()->render('status_messages', $messages);
  }

  /**
   * @throws Exception
   */
  public function buildForm_aggrid($table_select): array {
    global $settings;

    bm_d(self::BM_D_KEY_TRACE, __FUNCTION__, 'trace NodeTemplateForm', 'screen');
    bm_d('', $table_select, __FUNCTION__ . ' tableSelect', 'dsm');

//    $FEFQ = BMListingFEFQInterface::getInstance();
    // defined in bm_main.css
/* moved to getAggrid
    $colorClasses = [
      '.bgcolor-blue',
      '.bgcolor-dark-mint',
      '.bgcolor-lightgreen',
      '.bgcolor-green',
      '.bgcolor-grey',
      '.bgcolor-lightgrey',
      '.bgcolor-lightpink',
      '.bgcolor-lightviolet',
      '.bgcolor-navy',
      '.bgcolor-orange',
      '.bgcolor-purple',
      '.bgcolor-red',
      '.bgcolor-violet',
      '.bgcolor-yellow',
      '.colorful',
      '.colorful-border',
      '.colorful.fadeout',
      '.faded-colorful',
      '.fgcolor-white',
      '.fgcolor-blue',
      '.fgcolor-dark-mint',
      '.fgcolor-green',
      '.fgcolor-grey',
      '.fgcolor-lightgreen',
      '.fgcolor-lightgrey',
      '.fgcolor-lightpink',
      '.fgcolor-navy',
      '.fgcolor-orange',
      '.fgcolor-purple',
      '.fgcolor-red',
      '.fgcolor-violet',
      '.fgcolor-yellow',
    ];
    $agGridOptions['selector'] = '#table-select-ajax-ag-grid';
    $agGridOptions['gridOptions'] = [
      //'getRowId'=>'params => params.data.row_id',
      //'rowHeight'=> 120,
      'autoSizeStrategy' => [
        //'type'=>'SizeColumnsToFitGridStrategy'
        //'type'=>'fitProvidedWidth',
        //'type'=>'SizeColumnsToContentStrategy',
        'type' => 'fitCellContents',
      ],
      'rowSelection' => 'multiple',
      'onSelectionChanged' => 'onSelectionChanged',
      'rowMultiSelectWithClick' => TRUE,
      'enableCellExpressions' => TRUE,
      //'editType' => 'fullRow',
      'rowGroupPanelShow' => 'always',
      // 'enableAdvancedFilter'=> true,// enterprise
      // 'enableFillHandle'=> true,// enterprise
      // 'enableRangeSelection'=>true,//enterprise
      'tooltipInteraction' => TRUE,
      'tooltipShowDelay' => 1000,
      'tooltipHideDelay' => 2000,
      // prevent users from hiding columns by dragging off the grid
      'suppressDragLeaveHidesColumns' => FALSE,

      // INFINITE SCROLL

      // tell grid we want virtual row model type
      'rowModelType' => 'clientSide',// fixed nr of rows loaded
      //'rowModelType'=> 'infinite',// dynamic load

      // only when not infinite scroll
      'rowDragManaged' => TRUE,//controlled in bm_aggrid.js
      'pagination' => TRUE,//controlled in bm_aggrid.js
      'paginationPageSize' => 50,
      'paginationPageSizeSelector' => [2, 10, 20, 50, 200, 500, 1000],
      'paginationAutoPageSize' => TRUE,// size by amount available on screen

      'rowBuffer' => 1000,
      // how big each page in our page cache will be, default is 100
      'cacheBlockSize' => 100,
      // how many extra blank rows to display to the user at the end of the dataset,
      // which sets the vertical scroll and then allows the grid to request viewing more rows of data.
      // default is 1, ie show 1 row.
      'cacheOverflowSize' => 1,
      // how many server side requests to send at a time.
      // if user is scrolling lots, then the requests are throttled down
      'maxConcurrentDatasourceRequests' => 1,
      // how many rows to initially show in the grid. having 1 shows a blank row, so it looks like
      // the grid is loading from the users perspective (as we have a spinner in the first col)
      'infiniteInitialRowCount' => 1000,
      // how many pages to store in cache. default is undefined, which allows an infinite sized cache,
      // pages are never purged. this should be set for large data to stop your browser from getting
      // full of data
      //'maxBlocksInCache'=>100,
      'debug' => TRUE,
    ];
    // add a first checkbox
    $agGridOptions['gridOptions']['columnDefs'] = [
      [
        'cellClass' => 'default',
        'headerName' => 'Check',
        'field' => 'rowselect',
        'editable' => 'true',
        'width' => 50,
        //  'cellRenderer'=>'BooleanRenderer'
        'headerTooltip' => '50Select rows to update',
        //        'checkboxSelection' => TRUE,
        'checkboxSelection' => 'checkboxSelection',
        //      'headerCheckboxSelection'=> 'headerCheckboxSelection',
        'pinned' => 'left',
        'lockPinned' => TRUE,
        // make all rows draggable
        'rowDrag' => TRUE,
      ],
    ];
    //
    $agGridOptions['gridOptions']['defaultColDef'] = [
      //'autoHeight' => TRUE, // use ag--autoHeight--true individually
      'wrapText' => TRUE,
      'flex' => 1,
      //'width' => 180,
      //   'floatingFilter'=>true,
      'editable' => FALSE,
      'sortable' => TRUE,
      //'enableRowGroup'=> true,//enterprise
      //'enablePivot'=> true,//enterprise
      //'enableValue'=> true,//enterprise
      'enableCellChangeFlash' => TRUE,
      'filter' => TRUE,
      //'filter'=> 'agTextColumnFilter',
      //'headerTooltip' => 'text column',
      // this is with the colorful hover
      //'tooltipComponent' => 'AgTooltip',
      //'tooltipComponentParams' => [
      //  'color' => '#55AA77',
      //],

    ];
    $agGridOptions['gridOptions']['rowData'] = [
      //   [ 'edit'=>'<a>link</a>', 'id'=> 'NASA','times10'=>10],
      //   [ 'edit'=>'<a>link</a>', 'id'=> 'ekov','times10'=>9],
    ];
    //$agGridOptions['gridOptions']['rowClassRules'] = [
    // anonymous(x, ctx, oldValue, newValue, value, node, data, colDef, rowIndex, api, columnApi, getValue, column, columnGroup
    //              'bgcolor-green'=>'data.status==="Paid"'
    //];
*/
    $agGridOptions = FEFQ_getAggrid::agGridOptions();

/* moved to getAggrid
    $translateClassToRenderer = [
      'id-column' => 'IdCellRenderer',// renders a spinner when loading
      'money-column' => 'CurrencyCellRenderer', // makes it € 999.999,00
      'link-column' => 'LinkCellRenderer',  // makes it a button with the link
      'html-column' => 'HTMLCellRenderer',  // makes it markup
      'date-column' => 'YYYYMMDDCellRenderer',// makes it ..
      'datestring-column' => 'YYYYMMDDCellRenderer',  // makes it YYYY-MM-DD
      'button-column' => 'ButtonCellRenderer',// makes it a button with a modal display
      'button-tooltip-column' => 'ButtonTooltipCellRenderer',// makes it a button on hover tooltip
      'boolean-column' => '',
      'large-text-column' => '',
      'select-column' => '',
      'system-column' => '',// like keys, which are not visible
    ];
    $translateDataTypes = [
      'system-column' => 'text',
      'money-column' => 'number',
      'link-column' => 'text',
      'html-column' => 'text',
      'date-column' => 'date',
      'boolean-column' => 'boolean',
      'datestring-column' => 'dateString',
      'button-column' => 'text',
      'button-tooltip-column' => 'text',
    ];
    $selectValues = [
      'value1',
      'value2',
    ];
    $translateCellEditors = [
      'link-column' => [
        'cellEditor' => 'agTextCellEditor',
        'cellEditorParams' => [
          'maxLength' => 120,
        ],
        'headerTooltip' => '110link-column',
        'width' => 110,
      ],
      'html-column' => [
        'cellEditor' => 'agTextCellEditor',
        'cellEditorParams' => [
          'maxLength' => 20,
        ],
        'headerTooltip' => '110html-column',
        'width' => 110,
      ],
      'button-column' => [
        'cellRenderer' => 'ButtonCellRenderer',
        'cellRendererParams' => [
          'button_title' => 'Info',
          'modal_title' => 'Info',
        ],
        'width' => 100,
        'headerTooltip' => '110button-column',
      ],
      'button-tooltip-column' => [
        'cellRenderer' => 'ButtonTooltipCellRenderer',
        'cellRendererParams' => [
          'button_title' => 'Info',
          'tooltip_title' => 'Info',
        ],
        'width' => 100,
        'headerTooltip' => '110button-column',
        'tooltipComponent' => 'AgTooltip',
        'tooltipComponentParams' => [
          'color' => '#55AA77',
        ],
      ],
      'text-column' => [
        'cellEditor' => 'agTextCellEditor',
        'cellEditorParams' => [
          'maxLength' => 20,
        ],
        'width' => 200,
        'headerTooltip' => '200text-column',
      ],
      'large-text-column' => [
        'cellEditor' => 'agLargeTextCellEditor',
        'cellEditorParams' => [
          'maxLength' => 250,
          'rows' => 3,
          'cols' => 50,
        ],
        'headerTooltip' => 'large-text-column',
      ],
      'system-column' => [
        'cellEditor' => 'agLargeTextCellEditor',
        'cellEditorParams' => [
          'maxLength' => 250,
          'rows' => 3,
          'cols' => 50,
        ],
        'headerTooltip' => 'system-column',
      ],
      'select-column' => [
        'cellEditor' => 'agSelectCellEditor',
        'cellEditorParams' => [
          'values' => $selectValues,
        ],
        'headerTooltip' => 'select-column',
      ],

      'money-column' => [
        'cellEditor' => 'agNumberCellEditor',
        'cellEditorParams' => [
          'precision' => 2,
        ],
        'type' => 'rightAligned',
        'headerTooltip' => '110money-column',
        'width' => 110,
        'comparator' => 'currencyComparator',
      ],
      'date-column' => [  // this is for Date js objects
        'cellEditor' => 'agDateCellEditor',
        'headerTooltip' => 'date-column',
      ],
      'datestring-column' => [
        'cellEditor' => 'agDateStringCellEditor',
        'headerTooltip' => '115datestring-column',
        'width' => 115,
      ],
      'boolean-column' => [
        'cellRenderer' => 'agCheckboxCellRenderer',
        'cellRendererParams' => [
          'disabled' => TRUE,
        ],
        'headerTooltip' => '50boolean-column',
        'width' => 50,
      ],
    ];
*/
//  example yml
//  aggrid_shared_definitions:
//  ############################ AGGRID DEFAULTS
//  # SO WE CAN USE OVERRIDES AND REDIRECTS AND DUPLICATE LESS
//
//  # note the parser uses (when not found on the entry):
//  #  headerTooltip : tippy.en
//  #  headerName : user_label.en
//  #  field: name
//  - name: aggrid_html_field
//    aggrid:
//      gridOptions:
//        # options
//        columnDefs:
//          # headerName: headerName
//          # field: none
//          editable: TRUE
//          width: 50

    // create columnDefs from them, continue with fields, from what is already there (initialized by FEFQ_get)
    $n = count($agGridOptions['gridOptions']['columnDefs']);

    // $headerKeys = array_keys($table_select['#header']);
    // bm_d('',$headerKeys,'headerKeys','dsm');
    // foreach ($form['ajax_table_select']['table_select']['#header'] as $field => $headerData) {
    foreach ($table_select['#header'] as $field_name => $headerData) {
      bm_d(self::BM_D_KEY_AGGRID, $headerData, '<div class="bgcolor-green fgcolor-white">'.__FUNCTION__ . ' headerData for ' . $field_name.'</div>', 'dsm');

      // Detect if child form has defines a class `aggrid_{type}_field`,
      // which should be used to display this field.
      $aggrid_field_hint=null;
      if (isset($headerData['class'])) {
        foreach($headerData['class'] as $class){
          if(str_starts_with($class,'aggrid_')){
            $aggrid_field_hint=$class;
          }
        }
      }

      // try if field exists in YML
      $YML_config = FEFQ_getAggrid::get_entity_aggrid_data($field_name, $aggrid_field_hint);
      if ($YML_config !== []) {
        bm_d(self::BM_D_KEY_AGGRID, $YML_config, __FUNCTION__ . ' YML_config for field: ' . $field_name);
        $agGridOptions['gridOptions']['columnDefs'][$n] = $YML_config['columnDefs'];
        bm_d(self::BM_D_KEY_AGGRID, $agGridOptions, __FUNCTION__ . ' RESULT: agGridOptions for field:[' . $field_name.']');
      }
      // todo jbx this lost the YML definition which is not processed into the AGGrid options., override due to whats next:
      // headerData edit=>edit and invoice_due_date['data']['due date'],invoice_due_date['class']['date-column']
      $renderer = NULL;
      $dataType = NULL;
      $cellEditor = NULL;

      // passed in via `enum->value()=>['data'=>the label, 'class'=['ag--key--value',''],'tippy'=>'tippy text']`
      if (isset($headerData['data'])) {
        $agGridOptions['gridOptions']['columnDefs'][$n]['headerName'] = $headerData['data'];
      }
      if (isset($headerData['tippy'])) {
        $agGridOptions['gridOptions']['columnDefs'][$n]['headerTooltip'] = $headerData['tippy'];
      }
      if (isset($headerData['class'])) {
        $class = $headerData['class'];
        if (is_array($headerData['class'])) {
          $class = NULL;
          // in get_tableHeader classes are set, there can be multiple
          foreach ($headerData['class'] as $column_class) {

            if (str_contains($column_class, '-column')) {
              $class = $column_class;
            }
            // format in table header class aggrid_{type}_field
            // indicates the aggrid in YML to use for this field.
            if (str_contains($column_class, 'aggrid_')) {
              // ignore as already handled.
            }

              // format in table header class ag--key--value
            if (str_contains($column_class, 'ag--')) {
              $agProperty = explode('--', $column_class);
              // $agGridOptions['gridOptions']['columnDefs'][$n]['suppressSizeToFit']=true;
              if ($agProperty[2] === 'true') {
                $agProperty[2] = TRUE;
              }
              $agGridOptions['gridOptions']['columnDefs'][$n][$agProperty[1]] = $agProperty[2];
            }
          }
        }

        // translating classes of types to renderCell of ag-grid
        if ($class !== NULL) {
          // override renderer
          $renderer = FEFQ_getAggrid::translateClassToRenderer($class);
          if ($renderer === NULL) {
            Drupal::messenger()
              ->addWarning('Unknown css class [' . $class . '] add class in this ' . __FUNCTION__, TRUE);
            continue;
          }
          // override data types
          $g = FEFQ_getAggrid::translateDataTypes($class);
          if ($g) {
            $dataType = $g;
          }
          // override editors
          $c = FEFQ_getAggrid::translateCellEditors($class);
          if ($c) {
            $cellEditor = $c;
          }
        }
      }
      else {
        $headerName = $headerData;
      }
      //      if($headerName=="Reference"){
      //        $agGridOptions['gridOptions']['columnDefs'][$n]['suppressSizeToFit']=true;
      //      }
      $agGridOptions['gridOptions']['columnDefs'][$n]['field'] = $field_name;
      // TooltipSpecification can be used for extra data
      // example:
      //  'bgcolor-lightgreen: last 7 digit match;bgcolor-green:Phone numbers match'
      if (!empty($headerData['tooltipText'])) {
        $agGridOptions['gridOptions']['columnDefs'][$n]['tooltipSpecification'] = $headerData['tooltipText'];
      }
      // $agGridOptions['gridOptions']['columnDefs'][$n]['cellClass'] = '';
      //$agGridOptions['gridOptions']['columnDefs'][$n]['headerClass']=$headerClass;
      if ($renderer) {
        $agGridOptions['gridOptions']['columnDefs'][$n]['cellRenderer'] = $renderer;
      }
      if ($dataType) {
        $agGridOptions['gridOptions']['columnDefs'][$n]['cellDataType'] = $dataType;
      }
      if ($cellEditor) {
        $agGridOptions['gridOptions']['columnDefs'][$n] =
          array_merge($agGridOptions['gridOptions']['columnDefs'][$n], $cellEditor);
      }
      $n++;
    }

    // rows

    // we need columnDefs to determine how to handle some specific field data.
    $columnDefsIndex=[];
    foreach($agGridOptions['gridOptions']['columnDefs'] as $n=>$columnDef){
      $columnDefsIndex[$columnDef['field']]=$n;
    }

    foreach ($table_select['#options'] as $rowNr => $rowData) {
      //    foreach ($form['ajax_table_select']['table_select']['#options'] as $rowNr => $rowData) {
      $row = [];

      foreach ($rowData as $key => $td) {
        if ($key == '#attributes') {
          continue;
        }
        // process classes
        if (is_array($td)) {
          if (!empty($td['data'])) {
            $classes = $td['class'];
            if (is_array($td['class'])) {
              $classes = implode(',', $td['class']);
            }
            $rowValue = $td['data'];

            // bring back € 12.010,00 to float to get the sort correct
            // the formatting is now done in the renderer not in the data
            if (str_contains($rowValue, '€')) {
              $classes = $td['class'];
              if (is_array($td['class'])) {
                $classes = implode(',', $td['class']);
              }
              if (isset($classes) && str_contains($classes, 'money_column')) {
                $rowValue = floatval(str_replace(['€', ' ', ','], '', $rowValue));
              }
            }
          }
          else {
            $rowValue = $td;
          }
        }
        else {
          $rowValue = $td;
        }
        // jbx 240927 bug workaround missing ... field__listing__title
if(!isset($columnDefsIndex[$key])||!isset($agGridOptions['gridOptions']['columnDefs'][$columnDefsIndex[$key]]))continue;
        // retrieve the definitions of the fields to process special value treatment
        $columnDef= $agGridOptions['gridOptions']['columnDefs'][$columnDefsIndex[$key]];

        // money-column, aggrid_money_field identified by cellRenderer.
        if(!empty($columnDef['cellRenderer'])&& $columnDef['cellRenderer'] === 'CurrencyCellRenderer'){
          $rowValue = floatval(str_replace(['€', ' ', ','], '', $rowValue));
        }

        // select-column, aggrid_select_field

        if(!empty($columnDef['cellEditorParams']['values']) && $columnDef['cellEditorParams']['values']===FEFQAggridKeys::key_cellEditorParams_parse_terms->value){
          // todo jbx we have taxonomy_terms as standardisedTerm as values. Which need to be handled as csv list and a select editor. Here first implementation as cs list
          // in case array of terms
          if(is_array($rowValue)){
            $values=[];
            foreach($rowValue as $value){
              // terms?
              if(is_object($rowValue)){
                if(!empty($rowValue->label))$values[]=$rowValue->label;
              }
            }
            $rowValue=implode(',',$values);
          }else {
            if (is_object($rowValue)) {
              if(!empty($rowValue->label))$rowValue = $rowValue->label;
            }
          }
        }

        $row[$key] = $rowValue;
      }
      $agGridOptions['gridOptions']['rowData'][] = $row;
    }
    dsm($agGridOptions, __LINE__ . 'the agGridOptions');
    // send it
    //    $rows_per_page = $form_state->get('rows_per_page');

    //$rows_per_page = $table_select['rows_per_page'];
    $agGridOptions['gridOptions']['cacheBlockSize'] = $this->getNrOfRows();

    return $agGridOptions;


  }

  /**
   * ### refreshTableSelect_previousPage_AjaxCallback
   *
   * @param array              $form
   * @param FormStateInterface $form_state
   *
   * @return AjaxResponse
   */
  public
  function refreshTableSelect_previousPage_AjaxCallback(array &$form, FormStateInterface $form_state): AjaxResponse {
    bm_d(self::BM_D_KEY_TRACE, __function__, 'trace NodeTemplateForm', 'screen');
    $this->reset_option($form, 1, TRUE);
    return $this->pager_handler($form, $form_state);
  }

  /**
   * ### Reset_option reset the form select checkboxes after acting on them
   *
   * @param      $form
   * @param int  $n
   * @param bool $all
   *
   * @return void
   */
  private
  function reset_option(&$form, int $n, bool $all = FALSE): void {
    //bm_d(self::BM_D_KEY_TRACE, __function__, 'trace NodeTemplateForm', 'screen');

    if ($n < 0 || $n > 999) {
      return;
    }

    $start = 0;
    $end = 999;
    if (!$all) {
      $start = $n;
      $end = $n;
    }

    for ($i = $start; $i <= $end; $i++) {
      if (isset($form['ajax_table_select']['table_select'][$i])) {
        $form['ajax_table_select']['table_select'][$i]['#default_value'] = NULL;
        $form['ajax_table_select']['table_select'][$i]['#checked'] = FALSE;
        // register, so we only need to update the changed records
        $form['ajax_table_select']['table_select']['#options'][$i][0]['Dirty'] = time();
        //$form['ajax_table_select']['table_select']['#options'][$i]['#attributes'] = ['class' => ['updated']];
        //dsm($form['ajax_table_select']['table_select']['#options'][$i],$i);
      }
      else {
        break;
      }
    }
    //    dsm($i, 'last');
  }

  /**
   * ### pager_handler, handles paging and the paging buttons
   *
   * @param $form
   * @param $form_state
   *
   * @return AjaxResponse
   */
  private
  function pager_handler(&$form, $form_state): AjaxResponse {
    $page = $form_state->get('pager_page');
    $pages = $form_state->get('pager_pages');
    bm_d(self::BM_D_KEY_TRACE, __function__ . ' page:' . $page . ' of pages:' . $pages, 'trace NodeTemplateForm', 'screen');

    $form[self::KEY_PAGER_PREVIOUS_BTN]['#value'] = '< (' . (int) max(($page), 1) . ')';
    $form[self::KEY_PAGER_AT_PAGE]['#value'] = ($page + 1);
    $form[self::KEY_PAGER_NEXT_BTN]['#value'] = '> (' . (int) min(($page + 2), $pages) . ' of ' . $pages . ')';

    //dsm($form);
    $response = new AjaxResponse();
    //   bm_d(self::BM_D_KEY, $form, __function__ . ' the form');
    //Drupal::messenger()->addMessage('success');
    // replace the table
    $response->addCommand(new ReplaceCommand('#table-select-ajax-replace', $form['ajax_table_select']));
    // replace the pager buttons
    $response->addCommand(new ReplaceCommand(self::HTML_ID_PAGER_NEXT_BTN, $form[self::KEY_PAGER_NEXT_BTN]));
    $response->addCommand(new ReplaceCommand(self::HTML_ID_PAGER_AT_PAGE, $form[self::KEY_PAGER_AT_PAGE]));
    $response->addCommand(new ReplaceCommand(self::HTML_ID_PAGER_PREVIOUS_BTN, $form[self::KEY_PAGER_PREVIOUS_BTN]));
    // display messages,
    $this->response_messages($response);

    return $response;
  }

  /**
   * ### Displays status messages (Because ajax buttons, do not refresh messages, it is added here)
   *
   * @param $response
   *
   * @return void
   */
  private
  function response_messages(&$response): void {
    //bm_d(self::BM_D_KEY_TRACE, __function__, 'trace NodeTemplateForm', 'screen');
    $noMessages = TRUE;
    $messages = Drupal::messenger()->messagesByType('status');
    $messages = array_merge($messages, Drupal::messenger()->messagesByType('warning'));
    $messages = array_merge($messages, Drupal::messenger()->messagesByType('error'));

    $message_markup = '<div id="status-messages">';
    if ($messages) {
      Drupal::messenger()->deleteByType('status');
      $message_markup .= '<div role = "contentinfo" aria-label = "Status message" class="messages messages--status">
      <h2 class="visually-hidden">Status message</h2>
      <ul class="messages__list" >';
      foreach ($messages as $message) {
        $noMessages = FALSE;
        $message_markup .= '<li class="messages__item" >' . $message . '</li>';
      }
      $message_markup .= '</ul></div>';
    }
    $message_markup .= '</div>';

    // ALTERNATIVE TO POPUP
    // send them and places them under table
    // $response->addCommand(new ReplaceCommand('#status-messages', $message_markup));

    // pops up a modal with the messages
    // The selector of the dialog.
    $selector = '#messages-modal';// js sees this in settings of behaviour call
    // The title of the dialog.
    $title = 'Messages';
    // The content that will be placed in the dialog, either a render array or an HTML string.
    $content = $message_markup;
    // (optional) Array of options to be passed to the dialog implementation. Any jQuery UI option can be used. See http://api.jqueryui.com/dialog.
    $dialog_options = [
      //'minHeight' => '700px',
      'width' => 700,
      'resizable' => TRUE,
    ];
    //    (optional) Array of custom settings that will be passed to the Drupal behaviours on the content of the dialog. If left empty, the settings will be populated automatically from the current request. See https://www.drupal.org/docs/8/api/javascript-api/javascript-api-overview.
    $settings = ['sizeAdjustModal' => $selector];
    if (!$noMessages) {
      $response->addCommand(new OpenModalDialogCommand($title, $content, $dialog_options, $settings));
    }
    //    $response->addCommand(new \Drupal\Core\Ajax\InvokeCommand(null, 'throbber', [true,$message,true]));
  }

  /**
   * ### refreshTableSelect_nextPage_AjaxCallback
   *
   * @param array              $form
   * @param FormStateInterface $form_state
   *
   * @return AjaxResponse
   */
  public
  function refreshTableSelect_nextPage_AjaxCallback(array &$form, FormStateInterface &$form_state): AjaxResponse {
    bm_d(self::BM_D_KEY_TRACE, __function__ . '- pager_page: ' . (int) $form_state->get('pager_page'), 'trace NodeTemplateForm', 'screen');

    $this->reset_option($form, 1, TRUE);
    return $this->pager_handler($form, $form_state);
  }

  /**
   * ### submit_AjaxCallbackComponent
   *
   * @param array              $form
   * @param FormStateInterface $form_state
   *
   * @return AjaxResponse
   */
  public
  function submit_AjaxCallbackComponent(array &$form, FormStateInterface &$form_state): AjaxResponse {
    bm_d(self::BM_D_KEY_TRACE, __function__, 'trace NodeTemplateForm', 'screen');
    //$input = json_decode($form_state->getUserInput()['component_states']);
    //$id = $input->formComponentId;
    //$state = $input->state;
    return $this->pager_handler($form, $form_state);
  }

  /**
   * ### submit_AjaxCallback
   *
   * @param array              $form
   * @param FormStateInterface $form_state
   *
   * @return AjaxResponse
   */
  public
  function submit_AjaxCallback(array &$form, FormStateInterface &$form_state): AjaxResponse {
    bm_d(self::BM_D_KEY_TRACE, __function__, 'trace NodeTemplateForm', 'screen');
    $trigger = $form_state->getTriggeringElement()['#name'];
    //$value = $form_state->getTriggeringElement()['#value'];
    if (!str_contains($trigger, 'component')) {
      $form_state->setRebuild();
      $this->rows = $form['ajax_table_select']['table_select']['#options'];
      // reset any checked option
      foreach ($form['ajax_table_select']['table_select']['#options'] as $rowNr => $row) {
        if ($form['ajax_table_select']['table_select'][$rowNr]['#checked']) {
          $this->reset_option($form, $rowNr);

        }
      }
      return $this->pager_handler($form, $form_state);
    }

    $response = new AjaxResponse();
    $this->response_messages($response);
    dsm($response);
    return $response;
  }

  /**
   * ### ajax component callback
   * * identifies which component has submitted
   * ## for easy handling of components:
   * * and calls (in the derived class) specific submit handlers in form:
   * ```
   * $this->{'submit_AjaxCallback_'.$formComponentId}($form, $form_state);
   * ```
   * * or a generalised version:
   * ```
   * $this->submit_AjaxCallback_ComponentState($form, $form_state);
   * ```
   *
   * @param array              $form
   * @param FormStateInterface $form_state
   *
   * @return AjaxResponse
   */
  public
  function submit_AjaxCallback_component_states(array &$form, FormStateInterface &$form_state): AjaxResponse {
    bm_d(self::BM_D_KEY_TRACE, __function__, 'trace NodeTemplateForm', 'screen');

    $input = json_decode($form_state->getUserInput()['component_states']);
    $id = $input->formComponentId ?? 0;
    $id = str_replace('.', '_', $id);
    $state = $input->state;
    //    $trigger = $form_state->getTriggeringElement()['#name'];
    //    $value = $form_state->getTriggeringElement()['#value'];
    // make it so, child sees only the component not the component_states collector
    $form_state->getTriggeringElement()['#name'] = $id;
    $form_state->getTriggeringElement()['#value'] = $state;

    $form_state->setRebuild();
    // call handler for the specific component ID
    if (method_exists($this, 'submit_AjaxCallback_' . $id)) {
      $this->{'submit_AjaxCallback_' . $id}($form, $form_state);
    }
    else {
      // call a general handler for components
      if (method_exists($this, 'submit_AjaxCallback_ComponentState')) {
        $this->submit_AjaxCallback_ComponentState($form, $form_state);
      }
      else {
        Drupal::logger(__CLASS__)
          ->warning("Ajax call to component, but no submit method was found. Add submit_AjaxCallback_ComponentState or submit_AjaxCallback_$id methods");
      }
    }


    return $this->pager_handler($form, $form_state);
  }

  /**
   * ### submit_AjaxCallback_ComponentState
   * ### Override parent to handle ajax component state changes
   *
   * @param array              $form
   * @param FormStateInterface $form_state
   *
   * @return void
   */
  public
  function submit_AjaxCallback_ComponentState(array &$form, FormStateInterface $form_state): void {
    bm_d(self::BM_D_KEY_TRACE, __function__, 'trace NodeTemplateForm', 'screen');
    // $trigger = $form_state->getTriggeringElement()['#name'];
    // $value = $form_state->getTriggeringElement()['#value'];
    //dsm('Trigger='.$trigger.' having value='.$value,'component submit in child');
  }

  /** ### Process selected nodes
   * #### retrieves the selected rows and calls update_nodes to handle the update
   * #### resets the 'selected' checkbox
   * #### handles paging.
   *
   * * first in submit sequence
   *
   * @param array              $form
   * @param FormStateInterface $form_state
   *
   * @return array selectedRows, rows, page, nids
   */
  public
  function submitForm_update_nodes(array &$form, FormStateInterface $form_state): array {
    bm_d(self::BM_D_KEY_TRACE, __function__, 'trace NodeTemplateForm', 'screen');
    $tableSelectData['selectedRows'] = [];
    $rows = $form_state->getValue('table_select');
    // array where selected is string, rest int 0
    foreach ($rows as $rowId => $n) {
      if (is_string($n)) {
        $tableSelectData['selectedRows'][] = $rowId;
        $tableSelectData['row'][] = $form['ajax_table_select']['table_select']['#options'][$n];
      }
    }
    $this->rows = $form['ajax_table_select']['table_select']['#options'];
    $tableSelectData['page'] = $form_state->get('pager_page');

    $nids_array = $form_state->get('nids_array');
    $tableSelectData['nids'] = [];

    foreach ($tableSelectData['selectedRows'] as $rowNr) {
      $tableSelectData['nids'][] = $nids_array[$tableSelectData['page']][$rowNr];
      $this->reset_option($form, $rowNr);
    }
    $form_state->setRebuild();
    $triggerName = $form_state->getTriggeringElement()['#value'];

    $this->update_nodes($tableSelectData, $form, $form_state, $triggerName);
    return $tableSelectData;
  }

  /**
   * ### update_nodes i.e. the selected nodes updater
   * * Called after submit
   * * it receives the row data and node information\
   * * use it to iterate over selected rows and update Nodes as you see fit
   *
   * #### Note
   * The row data can be a bit complex due to the 'class' definitions
   * examples:
   * * `$tableSelectData['row'][$n]['interest_today']['data'];`
   * * `$nids = $tableSelectData['nids'];`
   * * `$row = $form['my_table']['#options'][$row_key];`
   * * `$interestToday = $tableSelectData['row'][$n]['interest_today']['data'];`
   *
   * @param array              $tableSelectData
   * @param array              $form
   * @param FormStateInterface $form_state
   * @param string             $triggerName
   *
   * @return void
   */
  abstract public
  function update_nodes(array $tableSelectData, array $form, FormStateInterface $form_state, string $triggerName): void;

  /**
   * not used
   *
   * @param array              $form
   * @param FormStateInterface $form_state
   *
   * @return void
   */
  public
  function validateForm_update_nodes(array &$form, FormStateInterface $form_state): void {
    bm_d(self::BM_D_KEY_TRACE, __function__, 'trace NodeTemplateForm', 'screen');
  }
  /*
  {
    // EXAMPLE
    bm_d(self::BM_D_KEY_TRACE, __function__, 'trace NodeTemplateForm', 'screen');
    //dsm('triggered by: '.$triggerName);
    $nids = $tableSelectData['nids'];
    // $row = $form['my_table']['#options'][$row_key];

    foreach ($nids as $n => $invoice_id) {
      if ($invoice_id) {
        $invoice = Node::load($invoice_id);
        if ($invoice) {
          $interestToday = $tableSelectData['row'][$n]['interest_today']['data'];
          if ($interestToday) {
            $interestToday = (float) str_replace('€', '', $interestToday);
          }
          $open = $invoice->get('field_invoice_sum_total_ex_tax')->getValue()[0]['value'];
          $invoice->set('field_invoice_overdue_interest', $interestToday);
          $invoice->set('field_sum_ex_vat_incl_interest', $open + $interestToday);
          try {
            $invoice->save();
          } catch (Exception $e) {
            BMMain::messengerException($e);
          }
          Drupal::messenger()
            ->addStatus('Processed invoice ' . $invoice->getTitle() . '. Interest set to: ' . $interestToday . ' and total open ex tax to:' . $open + $interestToday);
        }
      }
    }

    $message = implode(',', $nids) . ' Invoices have been updated';
    Drupal::logger(__CLASS__)->info($message);

  }
  */

  /**
   * not needed (using ajax)
   *
   * @param array              $form
   * @param FormStateInterface $form_state
   *
   * @return AjaxResponse
   */
  public
  function submitForm(array &$form, FormStateInterface $form_state): void {
    bm_d(self::BM_D_KEY_TRACE, __function__, 'trace NodeTemplateForm', 'screen');


    /*
        while ($item = $queue->claimItem()) {
        }
    */

  }

  /**
   * not needed (using ajax)
   *
   * @param array              $form
   * @param FormStateInterface $form_state
   *
   * @return void
   */
  public
  function validateForm(array &$form, FormStateInterface $form_state): void {
    //    bm_d(self::BM_D_KEY_TRACE, __function__, 'trace NodeTemplateForm', 'screen');
  }

  /**
   * **add in child**
   * ### example submit_AjaxCallback_nstatebutton_off_on
   * #### override parent for specific component id's
   *
   * @param array              $form
   * @param FormStateInterface $form_state
   *
   * @return void
   */
  abstract public
  function submit_AjaxCallback_nstatebutton_off_on(array &$form, FormStateInterface $form_state): void;

  public
  function validateForm_AjaxCallback_agGridOptions(array &$form, FormStateInterface $form_state) {
  }

  /*
  {
    bm_d(self::BM_D_KEY_TRACE, __function__, 'trace NodeTemplateForm', 'screen');
    //$trigger = $form_state->getTriggeringElement()['#name'];
    // $value = $form_state->getTriggeringElement()['#value'];
    //dsm($value);
  }
  */

  public
  function submitForm_AjaxCallback_agGridOptions(array &$form, FormStateInterface $form_state) {
  }

  /**
   * @throws Exception
   */
  public
  function AjaxCallback_agGridOptions($form, $form_state): AjaxResponse {
    global $settings;
    $rows_per_page = 100;
    $agGridOptions = $this->buildForm_aggrid($form['ajax_table_select']['table_select'], $rows_per_page);
    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(new InvokeCommand(NULL, 'aggridHandler', [$agGridOptions]));
    // display messages,
    $this->response_messages($ajax_response);
    return $ajax_response;
    /*
        bm_d(self::BM_D_KEY_TRACE, __function__, 'trace NodeTemplateForm', 'screen');
        bm_d(self::BM_D_KEY_AGGRID, $form, 'form');

        $FEFQ = BMListingFEFQInterface::getInstance();
        // defined in bm_main.css

        $colorClasses = [
          '.bgcolor-blue',
          '.bgcolor-dark-mint',
          '.bgcolor-lightgreen',
          '.bgcolor-green',
          '.bgcolor-grey',
          '.bgcolor-lightgrey',
          '.bgcolor-lightpink',
          '.bgcolor-lightviolet',
          '.bgcolor-navy',
          '.bgcolor-orange',
          '.bgcolor-purple',
          '.bgcolor-red',
          '.bgcolor-violet',
          '.bgcolor-yellow',
          '.colorful',
          '.colorful-border',
          '.colorful.fadeout',
          '.faded-colorful',
          '.fgcolor-white',
          '.fgcolor-blue',
          '.fgcolor-dark-mint',
          '.fgcolor-green',
          '.fgcolor-grey',
          '.fgcolor-lightgreen',
          '.fgcolor-lightgrey',
          '.fgcolor-lightpink',
          '.fgcolor-navy',
          '.fgcolor-orange',
          '.fgcolor-purple',
          '.fgcolor-red',
          '.fgcolor-violet',
          '.fgcolor-yellow',
        ];

        $agGridOptions['selector'] = '#table-select-ajax-ag-grid';

        $agGridOptions['gridOptions'] = [
          //'getRowId'=>'params => params.data.row_id',
          //'rowHeight'=> 120,
          'autoSizeStrategy' => [
            //'type'=>'SizeColumnsToFitGridStrategy'
            //'type'=>'fitProvidedWidth',
            //'type'=>'SizeColumnsToContentStrategy',
            'type' => 'fitCellContents',
          ],
          'rowSelection' => 'multiple',
          'onSelectionChanged' => 'onSelectionChanged',
          'rowMultiSelectWithClick' => TRUE,
          'enableCellExpressions' => TRUE,
          //'editType' => 'fullRow',
          'rowGroupPanelShow' => 'always',
          // 'enableAdvancedFilter'=> true,// enterprise
          // 'enableFillHandle'=> true,// enterprise
          // 'enableRangeSelection'=>true,//enterprise
          'tooltipInteraction' => TRUE,
          'tooltipShowDelay' => 1000,
          'tooltipHideDelay' => 2000,
          // prevent users from hiding columns by dragging off the grid
          'suppressDragLeaveHidesColumns' => FALSE,

          // INFINITE SCROLL

          // tell grid we want virtual row model type
          'rowModelType' => 'clientSide',// fixed nr of rows loaded
          //'rowModelType'=> 'infinite',// dynamic load

          // only when not infinite scroll
          'rowDragManaged' => TRUE,//controlled in bm_main.js
          'pagination' => TRUE,//controlled in bm_main.js
          //      'paginationPageSize'=> 5,
          'paginationPageSizeSelector' => [2, 10, 20, 200, 500, 1000],
          'paginationAutoPageSize' => TRUE,

          'rowBuffer' => 0,
          // how big each page in our page cache will be, default is 100
          'cacheBlockSize' => 100,
          // how many extra blank rows to display to the user at the end of the dataset,
          // which sets the vertical scroll and then allows the grid to request viewing more rows of data.
          // default is 1, ie show 1 row.
          'cacheOverflowSize' => 1,
          // how many server side requests to send at a time.
          // if user is scrolling lots, then the requests are throttled down
          'maxConcurrentDatasourceRequests' => 1,
          // how many rows to initially show in the grid. having 1 shows a blank row, so it looks like
          // the grid is loading from the users perspective (as we have a spinner in the first col)
          'infiniteInitialRowCount' => 1000,
          // how many pages to store in cache. default is undefined, which allows an infinite sized cache,
          // pages are never purged. this should be set for large data to stop your browser from getting
          // full of data
          //'maxBlocksInCache'=>100,
          'debug' => TRUE,
        ];
        // add a first checkbox
        $agGridOptions['gridOptions']['columnDefs'] = [
          [
            'cellClass' => 'default',// todo
            'headerName' => 'Check',
            'field' => 'rowselect',
            'editable' => 'true',
            'width' => 50,
            //  'cellRenderer'=>'BooleanRenderer'
            'headerTooltip' => '50Select rows to update',
            //        'checkboxSelection' => TRUE,
            'checkboxSelection' => 'checkboxSelection',
            //      'headerCheckboxSelection'=> 'headerCheckboxSelection',
            'pinned' => 'left',
            'lockPinned' => TRUE,
            // make all rows draggable
            'rowDrag' => TRUE,
          ],
        ];
        $agGridOptions['gridOptions']['defaultColDef'] = [
          //'autoHeight' => TRUE, // use ag--autoHeight--true individually
          'wrapText' => TRUE,
          'flex' => 1,
          //'width' => 180,
          //   'floatingFilter'=>true,
          'editable' => FALSE,
          'sortable' => TRUE,
          //'enableRowGroup'=> true,//enterprise
          //'enablePivot'=> true,//enterprise
          //'enableValue'=> true,//enterprise
          'enableCellChangeFlash' => TRUE,
          'filter' => TRUE,
          //'filter'=> 'agTextColumnFilter',
          //'headerTooltip' => 'text column',
          // this is with the colorful hover
          //'tooltipComponent' => 'AgTooltip',
          //'tooltipComponentParams' => [
          //  'color' => '#55AA77',
          //],

        ];
        $agGridOptions['gridOptions']['rowData'] = [
          //            [ 'edit'=>'<a>link</a>', 'id'=> 'NASA','times10'=>10],
          //            [ 'edit'=>'<a>link</a>', 'id'=> 'ekov','times10'=>9],
        ];
        //$agGridOptions['gridOptions']['rowClassRules'] = [
        // anonymous(x, ctx, oldValue, newValue, value, node, data, colDef, rowIndex, api, columnApi, getValue, column, columnGroup
        //              'bgcolor-green'=>'data.status==="Paid"'
        //];

        $translateClassToRenderer = [
          'id-column' => 'IdCellRenderer',// renders a spinner when loading
          'money-column' => 'CurrencyCellRenderer', // makes it € 999.999,00
          'link-column' => 'LinkCellRenderer',  // makes it a button with the link
          'html-column' => 'HTMLCellRenderer',  // makes it markup
          'date-column' => 'YYYYMMDDCellRenderer',// makes it ..
          'datestring-column' => 'YYYYMMDDCellRenderer',  // makes it YYYY-MM-DD
          'button-column' => 'ButtonCellRenderer',// makes it a button with a modal display
          'button-tooltip-column' => 'ButtonTooltipCellRenderer',// makes it a button on hover tooltip
          'boolean-column' => '',
          'large-text-column' => '',
          'select-column' => '',
          'system-column'=>''// like keys, which are not visible
        ];
        $translateDataTypes = [
          'system-column'=>'text',
          'money-column' => 'number',
          'link-column' => 'text',
          'html-column' => 'text',
          'date-column' => 'date',
          'boolean-column' => 'boolean',
          'datestring-column' => 'dateString',
          'button-column' => 'text',
          'button-tooltip-column' => 'text',
        ];

        $selectValues = [
          'value1',
          'value2',
        ];
        $translateCellEditors = [
          'link-column' => [
            'cellEditor' => 'agTextCellEditor',
            'cellEditorParams' => [
              'maxLength' => 120,
            ],
            'headerTooltip' => '110link-column',
            'width' => 110,
          ],
          'html-column' => [
            'cellEditor' => 'agTextCellEditor',
            'cellEditorParams' => [
              'maxLength' => 20,
            ],
            'headerTooltip' => '110html-column',
            'width' => 110,
          ],
          'button-column' => [
            'cellRenderer' => 'ButtonCellRenderer',
            'cellRendererParams' => [
              'button_title' => 'Info',
              'modal_title' => 'Info',
            ],
            'width' => 100,
            'headerTooltip' => '110button-column',
          ],
          'button-tooltip-column' => [
            'cellRenderer' => 'ButtonTooltipCellRenderer',
            'cellRendererParams' => [
              'button_title' => 'Info',
              'tooltip_title' => 'Info',
            ],
            'width' => 100,
            'headerTooltip' => '110button-column',
            'tooltipComponent' => 'AgTooltip',
            'tooltipComponentParams' => [
              'color' => '#55AA77',
            ],
          ],
          'text-column' => [
            'cellEditor' => 'agTextCellEditor',
            'cellEditorParams' => [
              'maxLength' => 20,
            ],
            'width' => 200,
            'headerTooltip' => '200text-column',
          ],
          'large-text-column' => [
            'cellEditor' => 'agLargeTextCellEditor',
            'cellEditorParams' => [
              'maxLength' => 250,
              'rows' => 3,
              'cols' => 50,
            ],
            'headerTooltip' => 'large-text-column',
          ],
          'system-column' => [
            'cellEditor' => 'agLargeTextCellEditor',
            'cellEditorParams' => [
              'maxLength' => 250,
              'rows' => 3,
              'cols' => 50,
            ],
            'headerTooltip' => 'system-column',
          ],
          'select-column' => [
            'cellEditor' => 'agSelectCellEditor',
            'cellEditorParams' => [
              'values' => $selectValues,
            ],
            'headerTooltip' => 'select-column',
          ],

          'money-column' => [
            'cellEditor' => 'agNumberCellEditor',
            'cellEditorParams' => [
              'precision' => 2,
            ],
            'type' => 'rightAligned',
            'headerTooltip' => '110money-column',
            'width' => 110,
            'comparator' => 'currencyComparator',
          ],
          'date-column' => [  // this is for Date js objects
            'cellEditor' => 'agDateCellEditor',
            'headerTooltip' => 'date-column',
          ],
          'datestring-column' => [
            'cellEditor' => 'agDateStringCellEditor',
            'headerTooltip' => '115datestring-column',
            'width' => 115,
          ],
          'boolean-column' => [
            'cellRenderer' => 'agCheckboxCellRenderer',
            'cellRendererParams' => [
              'disabled' => TRUE,
            ],
            'headerTooltip' => '50boolean-column',
            'width' => 50,
          ],
        ];

        // headers
        // first check if definitions for this column type are found in fefq interface yml
        // example yml
        // aggrid_shared_definitions:
        //############################ AGGRID DEFAULTS
        //# SO WE CAN USE OVERRIDES AND REDIRECTS AND DUPLICATE LESS
        //
        //  # note the parser uses (when not found on the entry):
        //  #  headerTooltip : tippy.en
        //  #  headerName : user_label.en
        //  #  field: name
        //  - name: aggrid_html_field
        //    aggrid:
        //      gridOptions:
        //        # options
        //        columnDefs:
        //          # headerName: headerName
        //          # field: none
        //          editable: TRUE
        //          width: 50

        // create columnDefs from them
        $n = count($agGridOptions['gridOptions']['columnDefs']);
        $headerKeys= array_keys($form['ajax_table_select']['table_select']['#header']);
        foreach ($form['ajax_table_select']['table_select']['#header'] as $field => $headerData) {

          // with the ag grid data in the FEFQ YAML, we can not get the ag grid settings directly from the YAML
          // without interpretations of classes defined in the form tableselect
          // a 'field' is identified by class 'field'
          if (isset($headerData['class']) && in_array('field', $headerData['class'])) {
            bm_d(self::BM_D_KEY_AGGRID,$field, __FUNCTION__.' Field which is looked-up via FEFQ','screen');

            $aggrid_yaml = $FEFQ->get_entity_aggrid_data($field);
            if ($aggrid_yaml !== []) {
              bm_d(self::BM_D_KEY_AGGRID,$aggrid_yaml, __FUNCTION__.' found this yaml for field: '.$field);
              $agGridOptions['gridOptions']['columnDefs'][$n] = $aggrid_yaml['columnDefs'];

              $n++;
              continue;
            }
          }

          // headerData edit=>edit and invoice_due_date['data']['due date'],invoice_due_date['class']['date-column']
          $headerClass = 'none';
          $renderer = NULL;
          $dataType = NULL;
          $cellEditor = NULL;
          $properties = NULL;// passed in via class with format ag--key--value
          if (isset($headerData['data'])) {
            $headerName = $headerData['data'];
            if(isset($headerData['tippy'])){

            }
            if (isset($headerData['class'])) {
              $class = $headerData['class'];

              if (is_array($headerData['class'])) {
                $class = NULL;
                // in get_tableHeader classes are set, there can be multiple
                foreach ($headerData['class'] as $column_class) {

                  if (str_contains($column_class, '-column')) {
                    $class = $column_class;
                  }
                  // format in table header class ag--key--value
                  if (str_contains($column_class, 'ag--')) {
                    $agProperty = explode('--', $column_class);
                    // $agGridOptions['gridOptions']['columnDefs'][$n]['suppressSizeToFit']=true;
                    if ($agProperty[2] === 'true') {
                      $agProperty[2] = TRUE;
                    }
                    $agGridOptions['gridOptions']['columnDefs'][$n][$agProperty[1]] = $agProperty[2];

                  }
                  // format in table header class bm--key--special
                  //              if (str_contains($column_class, 'bm--')) {
                  //                $bmProperty = explode('--', $column_class);
                  //                if ($bmProperty[2] === 'true') {
                  //                  $bmProperty[2] = TRUE;
                  //                }
                  //                switch($bmProperty[1]){
                  //                  case 'taxonomy':
                  ////                    BMTaxonomies::getTerms($bm_property[2]);
                  //                    break;
                  //                }
                  //                $agGridOptions['gridOptions']['columnDefs'][$n][$agProperty[1]] = $agProperty[2];
                  //
                  //              }
                }
              }

              // translating classes of types to renderCell of ag-grid
              if ($class !== NULL) {
                if (array_key_exists($class, $translateClassToRenderer)) {
                  $renderer = (string) $translateClassToRenderer[$class] ?? NULL;
                }
                else {
                  Drupal::messenger()
                    ->addWarning('Unknown css class [' . $class . '] add class in this '.__FUNCTION__, $headerName);
                }

                // setting special data types
                if (isset($translateDataTypes[$class])) {
                  $dataType = (string) $translateDataTypes[$class] ?? NULL;
                }

                // setting special editors
                if (array_key_exists($class, $translateCellEditors)) {
                  $cellEditor = $translateCellEditors[$class] ?? NULL;
                }
              }
            }
          }
          else {
            $headerName = $headerData;
          }
          //      if($headerName=="Reference"){
          //        $agGridOptions['gridOptions']['columnDefs'][$n]['suppressSizeToFit']=true;
          //      }
          $agGridOptions['gridOptions']['columnDefs'][$n]['headerName'] = $headerName;
          $agGridOptions['gridOptions']['columnDefs'][$n]['field'] = $field;
          $agGridOptions['gridOptions']['columnDefs'][$n]['headerTooltip'] = $headerData['headerTooltip']??$field;

          // extra data, we use to make the tooltips specific for classes applied to the cell.
          if(!empty($headerData['tooltipText'])) {
            $agGridOptions['gridOptions']['columnDefs'][$n]['tooltipSpecification'] = $headerData['tooltipText'];
          }

          // $agGridOptions['gridOptions']['columnDefs'][$n]['cellClass'] = '';


          //$agGridOptions['gridOptions']['columnDefs'][$n]['headerClass']=$headerClass;
          if ($renderer) {
            $agGridOptions['gridOptions']['columnDefs'][$n]['cellRenderer'] = $renderer;
          }
          if ($dataType) {
            $agGridOptions['gridOptions']['columnDefs'][$n]['cellDataType'] = $dataType;
          }
          if ($cellEditor) {
            $agGridOptions['gridOptions']['columnDefs'][$n] =
              array_merge($agGridOptions['gridOptions']['columnDefs'][$n],$cellEditor);
          }


          $n++;
        }

        // rows

        foreach ($form['ajax_table_select']['table_select']['#options'] as $rowNr => $rowData) {
          $row = [];

          foreach ($rowData as $key => $td) {

            if ($key == '#attributes') {
              continue;
            }

            // td edit=>.. and invoice_due_date['data']['2023-02-12'],invoice_due_date['class']['date-column']
            if (is_array($td)) {
              if (isset($td['data'])) {
                $classes = $td['class'];
                if (is_array($td['class'])) {
                  $classes = implode(',', $td['class']);
                }
                $rowValue = $td['data'];
                // bring back € 12.010,00 to float to get the sort correct
                // the formatting is now done in the renderer not in the data
                if (str_contains($rowValue, '€')) {
                  $classes = $td['class'];
                  if (is_array($td['class'])) {
                    $classes = implode(',', $td['class']);
                  }
                  if (isset($classes) && str_contains($classes, 'money_column')) {
                    $rowValue = floatval(str_replace(['€', ' ', ','], '', $rowValue));
                  }
                }
              }
              else {
                $rowValue = $td;
              }
            }
            else {
              $rowValue = $td;
            }
            $row[$key] = $rowValue;
          }
          $agGridOptions['gridOptions']['rowData'][] = $row;
        }
        //dsm($agGridOptions,__LINE__.' the agGridOptions');
        // send it

        $rows_per_page = $form_state->get('rows_per_page');
        $at_page = $form_state->get('pager_page');
        $of_pages = $form_state->get('pager_pages');

        $agGridOptions['gridOptions']['cacheBlockSize'] = $rows_per_page;

        $ajax_response = new AjaxResponse();
        $ajax_response->addCommand(new InvokeCommand(NULL, 'aggridHandler', [$agGridOptions]));
        // display messages,
        $this->response_messages($ajax_response);

        return $ajax_response;
    */
  }

  public
  function AjaxCallback_agGrid_nextPage($form, $form_state) {
    $page = $form_state->get('pager_page');
    //$response_data=$form['ajax_table_select']['table_select']['#options'];
    // has classes embedded which we don't need any more

    // alternative without form
    $response_data = $this->get_rows($form, $form_state);
    foreach ($response_data as &$rowData) {
      if (array_key_exists('#attributes', $rowData)) {
        unset($rowData['#attributes']);
      }
    }

    $settings = ['AjaxCallback_agGrid_nextPage' => count($response_data)];

    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(new HtmlCommand('#table-select-ajax-ag-grid-utility', json_encode($response_data), $settings));
    // display messages,
    $this->response_messages($ajax_response);

    // Return the AJAX response
    return $ajax_response;

  }

  /**
   * ### general helper method: getComponentState(componentId)
   * - returns the state of the component, or false when not changed since init.
   *
   * @param $componentFormId string the component form ID given at form element define
   *
   * @return false|mixed
   */
  protected function getComponentState(string $componentFormId): mixed {
    $states = $this->componentState;
    bm_d(self::BM_D_KEY_TRACE, $states, 'trace NodeTemplateForm:' . __function__, 'screen');

    if (isset($states[$componentFormId])) {
      return $states[$componentFormId];
    }
    return FALSE;
  }

  /**
   * ### buildForm_tableSelect
   * Called from buildForm to process row and headings into a table select
   * It takes care of creating the tableselect form with pager and ajax functionalities
   * * note it has a special function, which places the 'class' in a header in every row-cell to format the table.
   *
   * @throws Exception
   */
  protected
  function buildForm_tableSelect(array $tableSelect, array &$form, FormStateInterface $form_state): void {
    bm_d(self::BM_D_KEY_TRACE, __function__, 'trace NodeTemplateForm', 'screen');
    // dsm($tableSelect['#options'],'table select options');
    // apply the heading classes into the rows as well
    // handling cell classes as well
    foreach ($tableSelect['#options'] as &$row) {
      foreach ($row as $name => &$td) {
        if (isset($tableSelect['#header'][$name]['class'])) {
          if (is_array($td) && isset($td['class'])) {  // already a data, class form
            $td['class'] = array_merge($td['class'], $tableSelect['#header'][$name]['class']);
          }
          else {
            $td = ['data' => $td, 'class' => $tableSelect['#header'][$name]['class']];
          }
        }
      }

    }

    //  $form['ag_grid_container'] = [
    //    '#markup' => '<div id="agGridContainer" style="height: 400px;"></div>',
    //  ];

    $form['ajax_table_select']['pre_markup'] = [
      '#markup' => '<div id="table-select-ajax-replace">',
    ];
    // include columns inserted by this class
    // button column is to infor aggrid
    //   $tableSelect['#header'] = array_merge(['edit' => ['data'=>'Edit','class'=>'button_column']], $tableSelect['#header']);

    ///// UN COMMENT TO DISPLAY A DRUPAL NATIVE SELECT
    //  $form['ajax_table_select']['table_select'] = $tableSelect;

    $form['ajax_table_select']['post_markup'] = [
      '#markup' => '</div>',
    ];

    $pagerShow = ((int) $form_state->get('pager_pages') <= 1) ? 'displaynone' : '';

    $form['pager-wrapper-prefix'] = [
      '#type' => '#markup',
      '#markup' => '<div class="pager-wrapper">',
    ];

    $form['pager_previous_button'] = [
      '#type' => 'button',
      '#value' => '<',
      '#attributes' => ['class' => [$pagerShow]],
      '#ajax' => [
        'callback' => '::refreshTableSelect_previousPage_AjaxCallback',
        'wrapper' => 'purchase-table-select-ajax-replace', // Replace with your tableselect element's wrapper ID.
      ],
    ];
    $form['pager_at_page'] = [
      '#type' => 'button',
      '#value' => '1',
      '#attributes' => ['class' => [$pagerShow]],
    ];
    $form['pager_next_button'] = [
      '#type' => 'button',
      '#value' => '>',
      '#attributes' => ['class' => [$pagerShow]],

      '#ajax' => [
        'callback' => '::refreshTableSelect_nextPage_AjaxCallback',
        'wrapper' => 'purchase-table-select-ajax-replace', // Replace with your tableselect element's wrapper ID.
      ],
    ];
    $form['pager-wrapper-suffix'] = [
      '#type' => '#markup',
      '#markup' => '</div>',
    ];

  }

}
