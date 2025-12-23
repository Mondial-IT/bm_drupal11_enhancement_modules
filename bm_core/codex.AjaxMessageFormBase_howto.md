# How to Use `bm_drupal_enhancements/bm_core/AjaxMessageFormBase` (Drupal 11)

This guide explains how to use the shared `AjaxMessageFormBase` class to control Drupal AJAX messages in a **single, predictable location**, without duplication during form rebuilds.

The base class:
- Disables Drupal’s automatic message injection
- Provides one explicit message container
- Ensures messages only appear when you decide
- Works reliably with AJAX rebuilds, pagination, and partial form replacement

# Codex / Agents Instructions

Use the following section verbatim in your internal documentation or agent instructions.

---

## Standard: AJAX Message Handling (Drupal Forms)

All Drupal 11 forms that use AJAX **must** extend `AjaxMessageFormBase`.

Rules:

1. Automatic Drupal message rendering is disabled per form
2. Messages are rendered only via `renderMessagesAjax()`
3. Pagination, filters, and rebuilds must clear messages unless explicitly persisted
4. No form may render `status_messages` directly
5. No AJAX callback may target `[data-drupal-messages]`
6. Message rendering mode must be explicitly chosen:
   - embedded (default)
   - dialog

### AJAX Messages Standard (Drupal 11)



- AJAX callbacks must use `renderMessagesAjax()`



Rationale:

* Prevents duplicated messages
* Avoids Form API side effects
* Ensures predictable UI behavior
* Aligns with Drupal core AJAX patterns

Compliance:

* New forms must extend the base class
* Existing forms must be migrated when touched
* Deviations require explicit architectural approval

---

End of document.
