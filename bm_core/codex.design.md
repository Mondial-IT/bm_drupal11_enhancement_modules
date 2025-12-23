# AJAX Messages: Embedded OR Dialog Popup (Drupal 11)

This document describes a **single base form class** that supports **two interchangeable message display modes**:

- **Embedded** (inline container in the form)
- **Dialog popup** (Drupal AJAX modal dialog)

The mode is controlled by a **single switch on the base form**, with zero duplication and identical developer ergonomics.

---

## 1. Design Goals

- One base class
- One API for adding messages
- Switchable rendering strategy
- No theme overrides
- No message duplication
- Works with AJAX rebuilds
- Drupal 11 compliant

---

## 2. Message Modes

```text
EMBEDDED  → messages render inside the form
DIALOG    → messages render in a modal popup
