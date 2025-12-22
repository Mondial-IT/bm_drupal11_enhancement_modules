<?php

/*
 Copyright (c) Mondial-IT BV - Blue Marloc 2024
   Created on 2024-11-21 at 11:32:11
 */

namespace Drupal\bm_main\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

/**
 * Simplest implementation of an ajax driven, paged tableselect form.
 * @see interestAdminForm for a live example
 */
interface BMNodeTableSelectTemplateFormInterface {
  /**
   * Should return an array of nids
   *
   * @param FormStateInterface $form_state
   * @param int                $nrOfRows
   *
   * @return int|array
   */
  public function get_entity_ids(FormStateInterface $form_state,int $nrOfRows): int|array;

  /**
   * ### buildIntroText - add introduction text of the form in markup elements
   * @param array              $form
   * @param FormStateInterface $form_state
   *
   * @return void
   */
  public function buildIntroText(array &$form, FormStateInterface $form_state):void;
  /**
   * should return an array of key=>title of the column headers
   *
   * @param array $row
   *
   * @return array
   */
  function get_tableHeader(array $row): array;
  /**
   * receives the node loaded from the nids
   * should return a valid array of rows to be used in the table_select
   *
   * @param \Drupal\node\NodeInterface $node
   * @param array                      $form
   * @param FormStateInterface         $form_state
   *
   * @return array
   */
  function get_row_data(NodeInterface $node, array &$form, FormStateInterface $form_state): array;
  /**
   * ## Implement these methods
   *
   * * get_entity_ids to do a query for the nids to use in the table
   * * buildForm_components to add components to the form ribbon (above the table)
   * * get_tableHeader to make a list of headers
   * * get_row_data to return a row of data for the given nid
   * * buildForm_submitButtons to add buttons at the bottom of the form to initiate actions on selected nids
   * * update_nodes is passed the list of selected nids and the triggering submit, use it to process your data
   *
   * there are also methods to detect component submits like:
   * * submit_AjaxCallbock_ComponentState
   * * submit_AjaxCallback_nstatebutton_on_off
   * But using $this->getComponentState to prepare the row, or to do action in update_nodes may be simpler
   *
  */
  /**
   * #### buildForm_components
   *  * Define the components displayed above the table in the ribbon here.
   *  * Override in your class.
   *
   *  Use
   *  * $this->getComponentState($componentId) to retrieve the actual state
   *
   *
   */
  function buildForm_components(array &$form, FormStateInterface $form_state): void;
  function buildForm_submitButtons(array &$form, FormStateInterface $form_state): void;

  /**
   * This is called on selecting a row and hitting Update button
   * the nids and row numbers selected are passed to work with
   * as is the form_state.
   *
   * @param array              $tableSelectData
   * @param array              $form
   * @param FormStateInterface $form_state
   * @param string             $triggerName
   *
   * @return void
   */
  function update_nodes(array $tableSelectData, array $form, FormStateInterface $form_state,string $triggerName): void;
  /**
   * # submit_AjaxCallback_ComponentState
   * #### Called with triggering element the component id, triggering value the component state
   *
   * @param array              $form
   * @param FormStateInterface $form_state
   *
   * @return void
   */
  public function submit_AjaxCallback_ComponentState(array &$form, FormStateInterface $form_state): void;
  /**
   * # example specific component ajax callback methods
   * there exists a component with id nstatebutton_off_on, which calls this handler on click
   * ### submit_AjaxCallback_nstatebutton_off_on
   *
   * @param array              $form
   * @param FormStateInterface $form_state
   *
   * @return void
   */
  public function submit_AjaxCallback_nstatebutton_off_on(array &$form, FormStateInterface $form_state): void;
}
