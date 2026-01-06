# ai_logging_fix

Prevents AI Logging field UI routes from polluting the admin navigation. The ai_logging contrib module exposes Field UI tabs for its log type entities; this fix keeps those pages reachable by direct URL while hiding them from the admin menu.

- **What it does**: Removes `_admin_route` markers from AI Logging entity routes and Field UI routes so Admin Toolbar/Navigation won’t list them.
- **Configuration**: None. Enable the module; caches must be rebuilt after enabling.
- **When to use**: Whenever ai_logging is installed and unwanted menu items like `/admin/config/ai/logging/types/*/edit` or `…/fields` appear in the admin menu.
