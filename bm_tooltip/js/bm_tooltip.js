((Drupal, once) => {
  const DEFAULT_MAX_WIDTH = 320;

  const toNumber = (value, fallback) => {
    if (value === undefined || value === null || value === '') {
      return fallback;
    }
    const parsed = parseInt(value, 10);
    return Number.isNaN(parsed) ? fallback : parsed;
  };

  const resolveHtmlSource = (element) => {
    // Explicit selector.
    if (element.dataset.tooltipSelector) {
      const target = document.querySelector(element.dataset.tooltipSelector);
      if (target) {
        return target;
      }
    }
    // Explicit target id.
    if (element.dataset.tooltipTarget) {
      const target = document.getElementById(element.dataset.tooltipTarget);
      if (target) {
        return target;
      }
    }
    // Nearest sibling with data-tooltip.
    if (element.nextElementSibling && element.nextElementSibling.hasAttribute('data-tooltip')) {
      return element.nextElementSibling;
    }
    // Self contains the content.
    if (element.hasAttribute('data-tooltip')) {
      return element;
    }
    return null;
  };

  const getContent = (element) => {
    const hasTippyAttr = element.hasAttribute('data-bm-tooltip-content');
    const textAttr = element.getAttribute('data-bm-tooltip-content') || element.getAttribute('data-tip');

    if (textAttr && textAttr.trim() !== '') {
      return { content: textAttr, allowHTML: false };
    }

    // If data-bm-tooltip-content attribute exists but is empty, allow HTML from the element body.
    if (hasTippyAttr && element.innerHTML.trim() !== '') {
      return { content: element.innerHTML, allowHTML: true };
    }

    const htmlSource = resolveHtmlSource(element);
    if (htmlSource) {
      return { content: htmlSource.innerHTML, allowHTML: true };
    }
    return { content: '', allowHTML: false };
  };

  const getMinWidth = (element) => {
    if (element.dataset.bmTooltipMinWidth) {
      return element.dataset.bmTooltipMinWidth;
    }
    const styles = window.getComputedStyle(element);
    return styles.getPropertyValue('--bm-tooltip-min') || '';
  };

  const buildOptions = (element, allowHTML = false) => {
    const placement = element.dataset.bmTooltipPlacement || 'top';
    const theme = element.dataset.bmTooltipTheme || 'dark';
    const maxWidth = toNumber(element.dataset.bmTooltipMaxWidth, DEFAULT_MAX_WIDTH);
    const minWidth = getMinWidth(element);
    const interactive = element.dataset.bmTooltipInteractive === 'true';

    const options = {
      placement,
      theme,
      appendTo: document.body,
      animation: 'shift-away-subtle',
      allowHTML,
      maxWidth,
      interactive,
      offset: [0, 8],
      onCreate(instance) {
        if (minWidth) {
          instance.popper.style.setProperty('--bm-tooltip-min-width', minWidth);
        }
      },
      onShow(instance) {
        if (minWidth) {
          instance.popper.style.setProperty('--bm-tooltip-min-width', minWidth);
        }
      },
    };

    if (element.dataset.bmTooltipTrigger) {
      options.trigger = element.dataset.bmTooltipTrigger;
    }
    if (element.dataset.bmTooltipDelay) {
      const delay = element.dataset.bmTooltipDelay.split(',').map((value) => toNumber(value.trim(), 0));
      options.delay = delay.length === 1 ? delay[0] : delay.slice(0, 2);
    }
    if (element.dataset.bmTooltipInteractiveBorder) {
      options.interactiveBorder = toNumber(element.dataset.bmTooltipInteractiveBorder, 2);
    }
    if (element.dataset.bmTooltipPlacementFallbacks) {
      options.placement = element.dataset.bmTooltipPlacement;
      options.fallbackPlacements = element.dataset.bmTooltipPlacementFallbacks.split(',').map((pos) => pos.trim());
    }
    if (element.dataset.bmTooltipDistance) {
      const distance = toNumber(element.dataset.bmTooltipDistance, 8);
      options.offset = [0, distance];
    }
    return options;
  };

  Drupal.behaviors.bmTooltipTippy = {
    attach(context) {
      if (typeof window.tippy !== 'function') {
        return;
      }
      once('bm-tooltip', '.bm-tooltip[data-tip], .bm-tooltip[data-bm-tooltip-content], .bm-tooltip[data-tooltip], .bm-tooltip[data-tooltip-target], .bm-tooltip[data-tooltip-selector]', context)
        .forEach((element) => {
          const { content, allowHTML } = getContent(element);
          if (!content) {
            return;
          }
          const options = buildOptions(element, allowHTML);
          options.content = content;
          window.tippy(element, options);
        });
    },
  };
})(Drupal, once);
