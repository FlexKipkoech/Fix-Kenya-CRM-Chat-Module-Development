# Copilot Instructions for Fix-Kenya-CRM-Chat-Module-Development

## Overview
This project is a module for a CRM system, providing a real-time chat feature similar to Slack. It is structured as a CodeIgniter module, with clear separation between controllers, models, and views. The main module is located in `modules/slack_chat/`.

## Architecture
- **MVC Pattern**: Follows CodeIgniter's MVC structure:
  - `controllers/Slack_chat.php`: Handles admin routes and access control.
  - `models/Chat_model.php`: Manages chat data (channels, messages, members) using the database.
  - `views/admin/dashboard.php`: Admin dashboard UI for the chat module.
- **Module Entry**: `slack_chat.php` registers the module, hooks into the admin menu, and manages activation/deactivation.
- **Database Setup**: `install.php` creates required tables (`chat_channels`, `chat_messages`, `chat_members`) using CodeIgniter's DB API and `db_prefix()`.

## Key Conventions & Patterns
- **Database Access**: Always use `db_prefix()` for table names to ensure compatibility with the CRM's naming conventions.
- **User Context**: Use `get_staff_user_id()` to get the current user and `is_admin()` for access control.
- **Menu Integration**: Add admin menu items via `app_menu->add_sidebar_menu_item` inside an `admin_init` hook.
- **Localization**: Use `_l()` for all user-facing strings to support translations.
- **Activation/Deactivation**: Register hooks with `register_activation_hook` and `register_deactivation_hook` in the module entry file.

## Developer Workflows
- **Install/Upgrade**: Place the module in the `modules/` directory and access the CRM admin panel. The `install.php` script will run automatically if not already installed.
- **Testing**: No automated tests are present. Manual testing is done via the CRM admin UI.
- **Debugging**: Use CodeIgniter's logging and error reporting. Check for `exit('No direct script access allowed')` to prevent unauthorized access.

## Integration Points
- **CRM Core**: Relies on CodeIgniter and CRM-specific helpers (e.g., `db_prefix`, `get_instance`, `admin_url`).
- **Hooks System**: Integrates with the CRM via hooks for menu and lifecycle events.

## Examples
- **Adding a new chat feature**: Create a new method in `Chat_model.php` and expose it via a controller in `controllers/Slack_chat.php`.
- **Database migrations**: Add queries to `install.php` for new tables or columns, using `db_prefix()`.

## References
- `modules/slack_chat/slack_chat.php`: Module entry and hooks
- `modules/slack_chat/controllers/Slack_chat.php`: Controller logic
- `modules/slack_chat/models/Chat_model.php`: Data access
- `modules/slack_chat/install.php`: Database schema
- `modules/slack_chat/views/admin/dashboard.php`: Admin UI

---

If you are unsure about any workflow or convention, review the files above for concrete examples. For CRM-specific helpers and hooks, refer to the CRM's developer documentation.