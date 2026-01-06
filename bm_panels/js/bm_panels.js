((Drupal, drupalSettings, once) => {
  const translate = (message, args = {}) => {
    if (typeof Drupal.t === 'function') {
      return Drupal.t(message, args);
    }
    let output = message;
    Object.entries(args).forEach(([key, value]) => {
      output = output.replace(key, value);
    });
    return output;
  };

  const toPlainText = (value) => {
    if (!value) {
      return '';
    }
    const helper = document.createElement('div');
    helper.innerHTML = value;
    return helper.textContent ? helper.textContent.trim() : '';
  };

  class PanelGrid {
    constructor(container, config) {
      this.container = container;
      this.instanceId = config.instanceId;
      this.config = config;
      this.grid = config.grid || 80;
      this.rowUnit = this.grid;
      this.columns = config.columns || 12;
      this.state = {
        panels: { ...config.panels },
        removed: { ...PanelGrid.normalizeRemoved(config.removed) },
      };
      this.state.removed = this.state.removed || {};
      this.panelConfigs = { ...config.panelsConfig };
      this.palette = {};
      const paletteEntries = Array.isArray(config.palette)
        ? config.palette
        : (config.palette && typeof config.palette === 'object'
          ? Object.values(config.palette)
          : []);
      paletteEntries.forEach((item) => {
        if (item?.id) {
          this.palette[item.id] = item;
        }
      });
      this.panelMarkup = {};
      this.saveTimer = null;
      this.activeInteraction = null;
      this.wrapper = container.closest('.bm-panels') || container.parentElement || document;
      this.toolbar = this.wrapper.querySelector(`[data-bm-panels-toolbar="${this.instanceId}"]`);
      this.removedWrapper = this.wrapper.querySelector(`[data-bm-panels-removed="${this.instanceId}"]`);
      this.removedSelect = this.removedWrapper?.querySelector('[data-bm-panels-removed-select]') || null;
      this.controls = this.wrapper.querySelector(`[data-bm-panels-controls="${this.instanceId}"]`);
      this.preventOverlap = true;
      this.init();
    }

    init() {
      this.container.style.setProperty('--bm-panel-grid', `${this.grid}px`);
      this.container.style.setProperty('--bm-panel-row-height', `${this.rowUnit}px`);
      this.container.style.setProperty('--bm-panel-columns', `${this.columns}`);
      this.preparePanels();
      this.rowUnit = this.getRowUnit();
      this.bindControls();
      this.syncRemovedOptionsFromState();
      this.updateToolbarVisibility();
      this.loadState();
    }

    preparePanels() {
      this.container.querySelectorAll('.bm-panel').forEach((panel) => {
        const id = panel.dataset.panelId;
        this.panelMarkup[id] = panel.querySelector('.bm-panel__body')?.innerHTML ?? '';
        const draggable = panel.dataset.panelDraggable !== 'false';
        if (draggable) {
          const dragHandle = panel.querySelector('.bm-panel__handle');
          if (dragHandle) {
            dragHandle.addEventListener('pointerdown', (event) => this.startDrag(event, panel));
          }
        }
        const resizer = panel.querySelector('.bm-panel__resizer');
        if (resizer) {
          resizer.addEventListener('pointerdown', (event) => this.startResize(event, panel));
        }
        const removeButton = panel.querySelector('.bm-panel__remove');
        if (removeButton) {
          removeButton.addEventListener('click', () => this.removePanel(id));
        }
        this.bindPanelActions(panel);
      });
    }

    bindControls() {
      if (this.controls) {
        this.controls.querySelectorAll('.bm-panel-add').forEach((button) => {
          button.addEventListener('click', () => {
            const panelId = button.dataset.panelId;
            this.addPanelFromPalette(panelId);
          });
        });
      }

      if (this.removedSelect) {
        this.removedSelect.addEventListener('change', (event) => {
          const panelId = event.target.value;
          if (!panelId) {
            return;
          }
          this.restorePanel(panelId);
          event.target.value = '';
        });
        this.updateRemovedVisibility();
      }
    }

    loadState() {
      fetch(this.config.loadUrl, { credentials: 'same-origin' })
        .then((response) => (response.ok ? response.json() : Promise.reject()))
        .then((payload) => {
          if (payload?.state?.panels) {
            this.state.panels = { ...this.state.panels, ...payload.state.panels };
          }
          if (payload?.state?.removed) {
            this.state.removed = PanelGrid.normalizeRemoved(payload.state.removed);
          }
        })
        .catch(() => {})
        .finally(() => {
          this.applyState();
          this.syncRemovedOptionsFromState();
          this.updateToolbarVisibility();
        });
    }

    applyState() {
      this.container.querySelectorAll('.bm-panel').forEach((panel) => {
        const id = panel.dataset.panelId;
        const position = this.state.panels[id] || { x: 0, y: 0, width: 4, height: 3 };
        const config = this.panelConfigs[id] || {};
        this.applyPosition(panel, position, config);
      });
    }

    applyPosition(panel, position, config = {}) {
      const columnStart = (position.x ?? 0) + 1;
      const rowStart = (position.y ?? 0) + 1;
      const width = Math.max(1, position.width ?? config.width ?? 4);
      const height = Math.max(1, position.height ?? config.height ?? 3);
      panel.style.gridColumn = `${columnStart} / span ${width}`;
      panel.style.gridRow = `${rowStart} / span ${height}`;
      this.updateMeta(panel, { ...position, width, height }, config);
    }

    startDrag(event, panel) {
      event.preventDefault();
      panel.setPointerCapture(event.pointerId);
      const id = panel.dataset.panelId;
      const startState = { ...(this.state.panels[id] || { x: 0, y: 0, width: 4, height: 3 }) };
      const rect = panel.getBoundingClientRect();
      const columnUnit = this.getColumnUnit();
      const rowUnit = this.getRowUnit();
      this.activeInteraction = {
        type: 'drag',
        panel,
        id,
        pointerId: event.pointerId,
        origin: { x: event.clientX, y: event.clientY },
        startState,
        units: {
          column: columnUnit,
          row: rowUnit,
        },
        offsetPx: {
          x: event.clientX - rect.left,
          y: event.clientY - rect.top,
        },
      };
      this.handlePointerMove = (e) => this.onPointerMove(e);
      this.handlePointerUp = (e) => this.onPointerUp(e);
      panel.addEventListener('pointermove', this.handlePointerMove);
      panel.addEventListener('pointerup', this.handlePointerUp);
      panel.addEventListener('pointercancel', this.handlePointerUp);
    }

    startResize(event, panel) {
      event.preventDefault();
      panel.setPointerCapture(event.pointerId);
      const id = panel.dataset.panelId;
      const startState = { ...(this.state.panels[id] || { x: 0, y: 0, width: 4, height: 3 }) };
      const columnUnit = this.getColumnUnit();
      const rowUnit = this.getRowUnit();
      this.activeInteraction = {
        type: 'resize',
        panel,
        id,
        pointerId: event.pointerId,
        origin: { x: event.clientX, y: event.clientY },
        startState,
        units: {
          column: columnUnit,
          row: rowUnit,
        },
      };
      this.handlePointerMove = (e) => this.onPointerMove(e);
      this.handlePointerUp = (e) => this.onPointerUp(e);
      panel.addEventListener('pointermove', this.handlePointerMove);
      panel.addEventListener('pointerup', this.handlePointerUp);
      panel.addEventListener('pointercancel', this.handlePointerUp);
    }

    onPointerMove(event) {
      if (!this.activeInteraction || event.pointerId !== this.activeInteraction.pointerId) {
        return;
      }
      const columnUnit = this.activeInteraction.units.column || this.getColumnUnit();
      const rowUnit = this.activeInteraction.units.row || this.getRowUnit();
      const containerRect = this.container.getBoundingClientRect();
      const deltaX = (event.clientX - this.activeInteraction.origin.x) / columnUnit;
      const deltaY = (event.clientY - this.activeInteraction.origin.y) / rowUnit;
      const updated = { ...this.activeInteraction.startState };

      if (this.activeInteraction.type === 'drag') {
        const pointerColumn = (event.clientX - containerRect.left - (this.activeInteraction.offsetPx?.x ?? 0)) / columnUnit;
        const pointerRow = (event.clientY - containerRect.top - (this.activeInteraction.offsetPx?.y ?? 0)) / rowUnit;
        updated.x = Math.max(0, Math.floor(pointerColumn));
        updated.y = Math.max(0, Math.floor(pointerRow));
        const maxX = Math.max(0, this.columns - updated.width);
        updated.x = Math.min(updated.x, maxX);
      }
      else {
        updated.width = Math.min(this.columns, Math.max(1, Math.round(this.activeInteraction.startState.width + deltaX)));
        const maxWidth = Math.max(1, this.columns - this.activeInteraction.startState.x);
        updated.width = Math.min(updated.width, Math.max(1, maxWidth));
        updated.height = Math.max(1, Math.round(this.activeInteraction.startState.height + deltaY));
      }
      if (updated.x + updated.width > this.columns) {
        updated.x = Math.max(0, this.columns - updated.width);
      }

      if (this.preventOverlap && this.isOverlapping(updated, this.activeInteraction.id)) {
        return;
      }

      this.state.panels[this.activeInteraction.id] = updated;
      this.panelConfigs[this.activeInteraction.id] = {
        ...this.panelConfigs[this.activeInteraction.id],
        width: updated.width,
        height: updated.height,
      };
      this.applyPosition(this.activeInteraction.panel, updated, this.panelConfigs[this.activeInteraction.id]);
    }

    onPointerUp(event) {
      if (!this.activeInteraction || event.pointerId !== this.activeInteraction.pointerId) {
        return;
      }
      const { panel } = this.activeInteraction;
      panel.releasePointerCapture(event.pointerId);
      panel.removeEventListener('pointermove', this.handlePointerMove);
      panel.removeEventListener('pointerup', this.handlePointerUp);
      panel.removeEventListener('pointercancel', this.handlePointerUp);
      this.handlePointerMove = null;
      this.handlePointerUp = null;
      this.activeInteraction = null;
      this.scheduleSave();
    }

    isOverlapping(candidate, skipId) {
      return Object.entries(this.state.panels).some(([id, rect]) => {
        if (id === skipId) {
          return false;
        }
        return this.rectsOverlap(rect, candidate);
      });
    }

    rectsOverlap(a, b) {
      return (
        a.x < b.x + b.width &&
        a.x + a.width > b.x &&
        a.y < b.y + b.height &&
        a.y + a.height > b.y
      );
    }

    scheduleSave() {
      if (this.saveTimer) {
        clearTimeout(this.saveTimer);
      }
      this.saveTimer = setTimeout(() => this.persistState(), 400);
    }

    persistState() {
      this.saveTimer = null;
      fetch(this.config.saveUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': this.config.csrfToken,
        },
        credentials: 'same-origin',
        body: JSON.stringify({ panels: this.state.panels, removed: this.state.removed }),
      }).catch(() => {});
    }

    removePanel(panelId) {
      const panel = this.container.querySelector(`[data-panel-id="${panelId}"]`);
      if (!panel) {
        return;
      }
      if (panel.dataset.panelRemovable === 'false') {
        return;
      }
      this.panelMarkup[panelId] = panel.querySelector('.bm-panel__body')?.innerHTML ?? this.panelMarkup[panelId];
      panel.remove();
      delete this.state.panels[panelId];
      this.state.removed[panelId] = true;
      this.panelConfigs[panelId] = {
        ...this.panelConfigs[panelId],
        removed: true,
      };
      const labelSource = panel.querySelector('.bm-panel__title-text')?.textContent?.trim()
        || panel.querySelector('.bm-panel__handle')?.textContent?.trim()
        || panelId;
      this.addRemovedOption(panelId, labelSource);
      this.updateToolbarVisibility();
      this.scheduleSave();
    }

    restorePanel(panelId) {
      if (this.container.querySelector(`[data-panel-id="${panelId}"]`)) {
        return;
      }
      const template = this.panelMarkup[panelId] || this.palette[panelId]?.markup;
      if (!template) {
        return;
      }
      const config = this.palette[panelId] || this.panelConfigs[panelId] || {};
      const position = this.getNextAvailablePosition(config);
      this.state.panels[panelId] = position;
      this.panelConfigs[panelId] = {
        ...this.panelConfigs[panelId],
        ...config,
        removed: false,
      };
      const panelConfig = this.panelConfigs[panelId];
      const panel = this.buildPanelElement(panelId, panelConfig.label || panelId, template, position, panelConfig);
      this.container.appendChild(panel);
      this.applyPosition(panel, position, panelConfig);
      if (this.state.removed) {
        delete this.state.removed[panelId];
      }
      this.removeRemovedOption(panelId);
      this.updateToolbarVisibility();
      this.scheduleSave();
    }

    getNextAvailablePosition(config = {}) {
      for (let y = 0; y < 50; y += 1) {
        for (let x = 0; x < this.columns; x += 1) {
          const rect = {
            x,
            y,
            width: Math.min(this.columns, config.width ?? 4),
            height: config.height ?? 3,
          };
          if (!this.isOverlapping(rect)) {
            return rect;
          }
        }
      }
      return { x: 0, y: 0, width: config.width ?? 4, height: config.height ?? 3 };
    }

    buildPanelElement(id, label, markup, position, config = {}) {
      const title = config.title || label || id;
      const description = config.panelDescription || config.description || '';
      const tipText = toPlainText(description);
      const handleClasses = ['bm-panel__handle'];
      const isRemoved = config.removed === true;
      const wrapper = document.createElement('div');
      wrapper.classList.add('bm-panel');
      if (config.draggable === false) {
        wrapper.classList.add('bm-panel--static');
      }
      if (isRemoved) {
        wrapper.classList.add('bm-panel--removed');
        wrapper.setAttribute('hidden', 'hidden');
        wrapper.setAttribute('aria-hidden', 'true');
      }
      wrapper.dataset.panelId = id;
      wrapper.dataset.panelDraggable = config.draggable === false ? 'false' : 'true';
      wrapper.dataset.panelRemovable = config.removable === false ? 'false' : 'true';
      wrapper.dataset.panelRemoved = isRemoved ? 'true' : 'false';

      const handleRow = document.createElement('div');
      handleRow.classList.add('bm-panel__handle-row');

      const handleElement = document.createElement('div');
      handleElement.classList.add(...handleClasses);
      handleElement.setAttribute('role', 'button');
      handleElement.setAttribute('tabindex', '0');
      handleElement.setAttribute('aria-label', translate('Drag panel'));

      const handleGrid = document.createElement('div');
      handleGrid.classList.add('bm-panel__handle-grid');

      const titleSpan = document.createElement('span');
      titleSpan.classList.add('bm-panel__title-text');
      titleSpan.textContent = title || id;
      if (tipText) {
        titleSpan.classList.add('tooltip', 'tooltip--bottom', 'tooltip--dark', 'tooltip--edge-aware');
        titleSpan.dataset.tip = tipText;
        titleSpan.setAttribute('tabindex', '0');
      }
      handleGrid.appendChild(titleSpan);

      const actions = document.createElement('div');
      actions.classList.add('bm-panel__actions');

      if (tipText) {
        const infoButton = document.createElement('button');
        infoButton.type = 'button';
        infoButton.classList.add('bm-panel__info', 'tooltip', 'tooltip--bottom', 'tooltip--dark', 'tooltip--edge-aware');
        infoButton.setAttribute('aria-label', translate('Panel info'));
        infoButton.dataset.bmPanelsStopDrag = 'true';
        infoButton.dataset.tip = tipText;
        infoButton.setAttribute('tabindex', '0');
        const icon = document.createElement('span');
        icon.classList.add('bm-panel__icon', 'fa-solid', 'fa-circle-info');
        icon.setAttribute('aria-hidden', 'true');
        infoButton.appendChild(icon);
        actions.appendChild(infoButton);
      }

      let removeButton;
      if (config.removable !== false) {
        removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.classList.add('bm-panel__remove', 'tooltip', 'tooltip--bottom', 'tooltip--dark', 'tooltip--edge-aware');
        removeButton.setAttribute('aria-label', translate('Remove panel'));
        removeButton.dataset.bmPanelsStopDrag = 'true';
        removeButton.dataset.tip = translate('Remove panel');
        const icon = document.createElement('span');
        icon.classList.add('bm-panel__icon', 'fa-solid', 'fa-circle-xmark');
        icon.setAttribute('aria-hidden', 'true');
        removeButton.appendChild(icon);
        actions.appendChild(removeButton);
      }

      handleGrid.appendChild(actions);
      handleElement.appendChild(handleGrid);
      handleRow.appendChild(handleElement);
      wrapper.appendChild(handleRow);

      const divider = document.createElement('hr');
      divider.setAttribute('aria-hidden', 'true');
      wrapper.appendChild(divider);

      const body = document.createElement('div');
      body.classList.add('bm-panel__body');
      body.innerHTML = markup;
      wrapper.appendChild(body);

      const resizer = document.createElement('span');
      resizer.classList.add('bm-panel__resizer');
      resizer.setAttribute('aria-hidden', 'true');
      wrapper.appendChild(resizer);
      this.applyPosition(wrapper, position);
      const dragHandle = wrapper.querySelector('.bm-panel__handle');
      if (dragHandle && config.draggable !== false) {
        dragHandle.addEventListener('pointerdown', (event) => this.startDrag(event, wrapper));
      }
      if (resizer) {
        resizer.addEventListener('pointerdown', (event) => this.startResize(event, wrapper));
      }
      if (removeButton) {
        removeButton.addEventListener('click', () => this.removePanel(id));
      }
      this.bindPanelActions(wrapper);
      return wrapper;
    }

    addPanelFromPalette(panelId) {
      const definition = this.palette[panelId];
      if (!definition || this.state.panels[panelId]) {
        return;
      }
      this.panelMarkup[panelId] = definition.markup;
      const position = this.getNextAvailablePosition(definition);
      this.state.panels[panelId] = position;
      this.panelConfigs[panelId] = {
        ...this.panelConfigs[panelId],
        title: definition.title || definition.label || panelId,
        panelDescription: definition.panelDescription || definition.description || '',
        label: definition.label || panelId,
        draggable: definition.draggable !== false,
        removable: definition.removable !== false,
        width: definition.width ?? 4,
        height: definition.height ?? 3,
        removed: false,
      };
      const panel = this.buildPanelElement(
        panelId,
        this.panelConfigs[panelId].label || panelId,
        definition.markup,
        position,
        this.panelConfigs[panelId],
      );
      this.container.appendChild(panel);
      this.applyPosition(panel, position, this.panelConfigs[panelId]);
      if (this.state.removed) {
        delete this.state.removed[panelId];
      }
      this.removeRemovedOption(panelId);
      this.updateToolbarVisibility();
      this.scheduleSave();
    }

    addRemovedOption(panelId, label) {
      if (!this.removedSelect || !this.hasRemovalSupport()) {
        return;
      }
      if (this.removedSelect.querySelector(`option[value="${panelId}"]`)) {
        this.updateRemovedVisibility();
        return;
      }
      const option = document.createElement('option');
      option.value = panelId;
      option.dataset.panelId = panelId;
      option.textContent = label;
      this.removedSelect.appendChild(option);
      this.updateRemovedVisibility();
    }

    removeRemovedOption(panelId) {
      if (!this.removedSelect) {
        return;
      }
      const option = this.removedSelect.querySelector(`option[value="${panelId}"]`);
      if (option) {
        option.remove();
      }
      if (this.removedSelect.value === panelId) {
        this.removedSelect.value = '';
      }
      this.updateRemovedVisibility();
    }

    updateRemovedVisibility() {
      if (!this.removedWrapper || !this.removedSelect) {
        return;
      }
      const available = this.removedSelect.querySelectorAll('option[data-panel-id]').length;
      const shouldShow = this.hasRemovalSupport() && (available > 0 || this.hasRemovedPanels());
      if (shouldShow) {
        this.removedWrapper.removeAttribute('hidden');
      }
      else {
        this.removedWrapper.setAttribute('hidden', 'hidden');
      }
      this.updateToolbarVisibility();
    }

    getColumnUnit() {
      const width = this.container.clientWidth || 1;
      const styles = window.getComputedStyle(this.container);
      const gap = parseFloat(styles.columnGap) || 0;
      const track = (width - gap * (this.columns - 1)) / this.columns;
      return track + gap;
    }

    getRowUnit() {
      const styles = window.getComputedStyle(this.container);
      const gap = parseFloat(styles.rowGap) || 0;
      const autoRows = styles.gridAutoRows;
      let track = parseFloat(autoRows) || this.rowUnit;
      if (Number.isNaN(track)) {
        track = this.rowUnit;
      }
      return track + gap;
    }

    updateMeta(panel, position, config = {}) {
      const draggableEl = panel.querySelector('.bm-panel__meta-draggable');
      if (draggableEl) {
        draggableEl.textContent = config.draggable === false ? translate('Configured as non-draggable') : translate('Configured as draggable');
      }
      const removableEl = panel.querySelector('.bm-panel__meta-removable');
      if (removableEl) {
        removableEl.textContent = config.removable === false ? translate('pinned') : translate('removable');
      }
      const sizeEl = panel.querySelector('.bm-panel__meta-size');
      if (sizeEl) {
        sizeEl.textContent = translate('size @width×@height', {
          '@width': position.width ?? config.width ?? 4,
          '@height': position.height ?? config.height ?? 3,
        });
      }
    }

    bindPanelActions(panel) {
      panel.querySelectorAll('[data-bm-panels-stop-drag="true"]').forEach((control) => {
        if (control.dataset.bmPanelsStopBound === 'true') {
          return;
        }
        control.addEventListener('pointerdown', (event) => {
          event.stopPropagation();
        });
        ['mouseenter', 'mouseover', 'pointerenter', 'focus'].forEach((eventName) => {
          control.addEventListener(eventName, (event) => {
            event.stopPropagation();
          }, true);
        });
        control.dataset.bmPanelsStopBound = 'true';
      });
    }

    hasRemovalSupport() {
      return Object.values(this.panelConfigs || {}).some((config) => config.removable !== false)
        || Object.values(this.palette || {}).some((item) => item.removable !== false)
        || this.hasRemovedPanels();
    }

    hasActiveRemovablePanels() {
      return Object.entries(this.panelConfigs || {}).some(([id, config]) => (
        config?.removable !== false && this.state.removed?.[id] !== true
      ));
    }

    hasRemovedPanels() {
      return Object.values(this.state.removed || {}).some((value) => value === true);
    }

    syncRemovedOptionsFromState() {
      if (!this.removedSelect || !this.hasRemovedPanels()) {
        this.updateRemovedVisibility();
        return;
      }
      Object.entries(this.state.removed || {}).forEach(([panelId, isRemoved]) => {
        if (!isRemoved) {
          return;
        }
        if (!this.removedSelect.querySelector(`option[value="${panelId}"]`)) {
          const label = this.panelConfigs[panelId]?.title
            || this.panelConfigs[panelId]?.label
            || panelId;
          this.addRemovedOption(panelId, label);
        }
      });
      this.updateRemovedVisibility();
    }

    updateToolbarVisibility() {
      if (!this.toolbar) {
        return;
      }
      const shouldShow = this.hasActiveRemovablePanels() || this.hasRemovedPanels();
      if (shouldShow) {
        this.toolbar.removeAttribute('hidden');
      }
      else {
        this.toolbar.setAttribute('hidden', 'hidden');
      }
    }

    static normalizeRemoved(removedConfig) {
      if (!removedConfig) {
        return {};
      }
      const normalized = {};
      if (Array.isArray(removedConfig)) {
        removedConfig.forEach((panelId) => {
          if (panelId) {
            normalized[panelId] = true;
          }
        });
      }
      else if (typeof removedConfig === 'object') {
        Object.entries(removedConfig).forEach(([panelId, flag]) => {
          if (flag) {
            normalized[panelId] = true;
          }
        });
      }
      return normalized;
    }
  }

  Drupal.behaviors.bmPanels = {
    attach(context) {
      const settings = drupalSettings.bmPanels || {};
      Object.entries(settings).forEach(([instanceId, config]) => {
        once('bmPanels', `[data-bm-panels-instance="${instanceId}"]`, context).forEach((container) => {
          // eslint-disable-next-line no-new
          new PanelGrid(container, { ...config, instanceId });
        });
      });
    },
  };
})(Drupal, drupalSettings, once);
