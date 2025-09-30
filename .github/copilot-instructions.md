# Copilot Instructions for Fix-Kenya-CRM-Chat-Module-Development
```markdown
# Copilot Instructions — Slack Chat module

This repository contains a CodeIgniter module that implements a Slack-like chat for a CRM. The module lives in `modules/slack_chat/` and follows the host CRM's module/hook conventions.

Key points for an AI coding agent (be precise and local):

- Architecture & responsibilities
  - `controllers/Slack_chat.php` — admin controller (extends `AdminController`). Gatekeeping: `is_admin()` and `access_denied()` are used.
  - `models/Chat_model.php` — DB access. Uses `db_prefix()` for table names and defensive `table_exists_or_log()` checks.
  - `views/admin/*.php` — UI (dashboard and chat view). Use `_l()` for translatable strings.
  - `slack_chat.php` — module entry: registers `register_activation_hook`, `register_deactivation_hook`, and adds the admin menu via `hooks()->add_action('admin_init', ...)`.
  - `install.php` — creates 3 tables: `chat_channels`, `chat_messages`, `chat_members`. It returns true on success and logs errors via `log_message()`.

- Project-specific conventions (strictly follow these)
  - Always use `db_prefix()` for table names (see `install.php` and `Chat_model.php`).
  - Use CRM helpers: `get_instance()`, `admin_url()`, `get_staff_user_id()` for user context.
  - Localize UI text via `_l('...')`.
  - Use `hooks()->add_action('admin_init', 'your_init_func')` to hook into admin UI/menu initialization.

- Developer workflows (how to run / test changes)
  - No build system. To test changes: copy the module into the host CRM's `modules/` folder, then activate the module from the CRM admin modules UI (activation calls `install.php`).
  - For manual testing: log in as an admin, open Admin -> Chat (menu added by `slack_chat.php`), exercise chat UI and AJAX endpoints (`send_message`, `get_messages`).
  - Debugging: inspect CRM logs (CodeIgniter `log_message()` output), enable CI debug mode in the CRM, and check web server/PHP error logs if needed.

- Integration & error handling patterns
  - Activation runs `install.php` via include; the file must return true on success. Errors are logged with `log_message('error', ...)`.
  - Controllers check `$this->input->is_ajax_request()` for AJAX endpoints and call `show_404()` on invalid access.
  - Use `access_denied('Slack Chat')` when permission checks fail in controllers.

- Small concrete examples you can mimic
  - Add admin menu item: see `slack_chat.php` -> `app_menu->add_sidebar_menu_item('slack_chat', [...])`.
  - Create a new channel: `Chat_model::create_channel($name, $description, $is_private)` — uses `get_staff_user_id()` and `db_prefix()`.
  - Insert message: `Chat_model::send_message($channel_id, $user_id, $message)` — returns insert id.

- What an AI agent must not assume
  - There are no unit tests or CI targets in this repo. Do not add external services or credentials. Changes must be limited to module code unless instructed otherwise.

Files to inspect when editing behavior:
- `modules/slack_chat/slack_chat.php` (hooks/activation/menu)
- `modules/slack_chat/install.php` (DB schema creation)
- `modules/slack_chat/controllers/Slack_chat.php` (routes/AJAX handlers)
- `modules/slack_chat/models/Chat_model.php` (DB access patterns)

If anything here is unclear or you need runtime credentials/environment details (how to enable the CRM or where to activate modules), ask the maintainer. Please confirm any assumption before making changes that affect the host CRM or external systems.
```