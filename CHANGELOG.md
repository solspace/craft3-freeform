# Solspace Freeform Changelog

## 5.9.2 - 2025-01-24

### Fixed
- Fixed a bug where the Stripe field was not factoring in default values in Radio fields when set as the dynamic amount field.
- Fixed a bug where the File Drag & Drop field was not displaying all errors.
- Fixed a bug where the form builder and dashboard would not display any forms for a user who did not have access to the primary site.
- Fixed unnecessary captcha triggers in Stripe calls.
- Fixed a bug where the breadcrumbs Site switcher was not displaying correctly in Craft 5.

## 5.9.1.2 - 2025-01-23

### Fixed
- Fixed a bug where the Calculation field logic was not editable in the form builder.

## 5.9.1.1 - 2025-01-23

### Fixed
- Fixed a compatibility issue with the Monolog 2.x dependency in Craft 4.x.

## 5.9.1 - 2025-01-23

### Changed
- Updated the integration logging to include extensive Stripe webhook logging.
- Updated the plugin icon.

## 5.9.0 - 2025-01-21

### Added
- Added the ability to mark specific field columns as required inside Table fields.
- Added _Info_ and _Debugging_ logging for integrations.
    - Added an **Integrations Log** to the settings menu so admins can easily view and clear the log from the control panel.
- Added the ability to hide specific options from **Element and Predefined sources** for multi-option field types (via the Limited Users feature).
- Added the ability to limit the **Forms element field type** by specific forms.
- Added a setting to turn off **Automatic Purge Unfinalized Assets** queue jobs.
- Added counter for the number of **Saved** submissions on each form card on the dashboard.
- Added a **Tailwind 3 Dark** mode sample template.
- Added Site awareness and Integration logging level to the Diagnostics page.
- Added support for more features in the Express edition of Freeform:
    - All captchas, including Javascript Test and all reCAPTCHA, hCaptcha and Turnstile options.
    - All export file types, including Excel, CSV, XML, JSON and Text.
    - Access to Quick Export.
- Added support for more features in the Lite edition of Freeform:
    - All export file types, including Excel, CSV, XML, JSON and Text.
    - Access to Quick Export and Export Profiles.
- Verified support for Craft 5.6.x.

### Changed
- Refactored all exporting features and improved memory efficiency.
- Refactored all queue job runners into feature bundles and prevented duplicate jobs from happening.
- Updated various translations throughout the control panel.
- Updated all sample template CSS and JS, including the Javascript Test to be minified.

### Fixed
- Fixed a bug where integrations were not using Craft's guzzle configuration (`httpProxy`).
- Fixed a bug where custom button rendering incorrectly displayed **Back** buttons.
- Fixed a bug where the **Save & Continue Later** feature was visible on _Express_ and _Lite_ editions.
- Fixed a bug where the **Convert to Custom Values** button was incorrectly showing for Limited Users when hidden.
- Fixed a bug where the **Spam Protection Behavior** setting was showing incorrectly on the Diagnostics page.
- Fixed a bug where **Email Field** notifications were attempting to be sent when the Email field was hidden by Conditional Rules.
- Fixed a compatibility issue with the integration `options` column type for sites using older versions of MariaDB.

## 5.8.7 - 2025-01-15

### Fixed
- Fixed a bug where the PDF Templates feature could error in the form builder and notification templates area for some users.

## 5.8.6 - 2025-01-08

### Fixed
- Fixed a bug where mouse clicks were not affecting Conditional Rules on Number fields (e.g. up/down arrows).
- Fixed a bug where the _Usage in Elements_ tab in the form builder was crashing when no Freeform Form fields existed.
- Fixed a conflict issue with a CSS class in the control panel.
- Fixed a bug where some basic translations for GraphQL permission settings were missing.

## 5.8.5 - 2024-12-27

### Fixed
- Fixed a bug where sites using the multi-site feature would see multiple copies of submissions when using the Export Profiles feature.

## 5.8.4 - 2024-12-23

### Fixed
- Fixed a bug where sites using the multi-site feature would see multiple copies of submissions when using Quick Export.
- Fixed a bug where allowing spam submissions could error when using multi-sites.
- Fixed a bug where the _Last Number_ for the **Numbers** predefined option would always be `20`.
- Fixed a bug where the `m241126_113656_UpdateTableLayoutProperties.php` migration could error about undefined array keys.
- Fixed a bug where the Freeform observer started before the document was ready.

## 5.8.3 - 2024-12-16

### Fixed
- Fixed a bug where the initial field value wasn't used in the Calculation field.
- Fixed an issue where the resource URLs for sample formatting templates were not fully compatible with Craft Cloud sites.
- Fixed a bug where **Status** was not available in Quick Export.
- Fixed issues with the Stripe webhook.
- Fixed a bug where the form builder **Usage in Elements** tab was not always loading correctly.

## 5.8.2 - 2024-12-11

### Fixed
- Fixed a bug where a user with individual form permissions would not have access to a form they just duplicated.
- Fixed a bug where the automatic purging of old submissions queue job could fail if an empty string was passed for an asset.

## 5.8.1 - 2024-12-10

### Added
- Added Italian translation.

### Changed
- Changed the _English_ translation from `en-US` to `en`.

### Fixed
- Fixed a bug where spammy submissions were still being saved to the database when the Spam Folder was disabled.
- Fixed a bug where fresh installs between 5.7.0 and 5.8.0 were missing a new `options` column in the `freeform_email_marketing_fields` and `freeform_crm_fields` database tables. Added a migration for affected installs.
- Fixed a bug where the form name link on the CP submission details page was not linked correctly.
- Fixed a bug where translations for the Error Log notice were missing.

## 5.8.0 - 2024-12-03

### Added
- Added the ability to use Textareas and Radios inside a Table field.

### Changed
- Improved the usability of the Table field in the form builder.
- Improved the usability of the Submit button layout settings to hide unnecessary options in the form builder.
- Updated the sample formatting template assets to load as Asset Bundles to support Craft Cloud setups.
- Optimized all front end Freeform scripts.
    - Removed the `axios` dependency.
    - Removed the `lodash` dependency.
    - Removed the `core-js` polyfills.
    - Scripts for Calculation fields now load seperately as `calculation.js` only if the field exists in the form being displayed.
    - When using `freeform.loadScripts()` (for manually loading scripts) and Calculation fields, be sure to include the `field.calculation` option.

### Fixed
- Fixed a bug where multiple primary keys could be defined when MySQL has `sql_generate_invisible_primary_key` turned on.

## 5.7.4 - 2024-11-29

### Changed
- Updated the Mailchimp integration to include additional error logging around Contact Tags.

### Fixed
- Fixed a bug in the Freeform 4.x migration where form submission table names were not always being named correctly.
- Fixed a bug in the Freeform 4.x migration where original form handles were not being preserved.

## 5.7.3 - 2024-11-22

### Added
- Added translations for the **Usage in Elements** feature.

### Changed
- Updated the `phpoffice/phpspreadsheet` dependency to include `^2.0` and `^3.0`.
- Updated the Salesforce Lead **Update Duplicate Leads** setting to skip empty values in the new data when overwriting an existing Lead record.

### Fixed
- Fixed a bug where Matrix relation titles would not always display correctly in the **Usage in Elements** tab. Set `Entry ID#` as a title fallback for Matrix relations without a title.
- Fixed a bug where the Pipedrive integration was not correctly mapping to Lead fields.
- Fixed a bug where required field validation wasn't working correctly when using `is one of` and `is not one of` conditional rules logic.
- Fixed a bug where the Freeform 4.x migration could fail if a form name was too long.
- Fixed a bug where the Stripe payment status was not updating for previously declined cards.
- Fixed a bug where the Stripe instructions guide had some errors.

## 5.7.2 - 2024-11-15

### Added
- Added a **Usage in Elements** tab to each form's settings in the form builder, displaying which elements use that form in the Freeform Form element type (Craft 5 only).
- Added detection and mapping of predefined options to applicable fields in the Salesforce integration.
- Added detection and mapping of predefined options to applicable fields in the HubSpot integration.
- Added an accessibility check for reduced motion on form scroll.
- Verified support for Craft 5.5.x.

### Changed
- Updated composer.json to no longer force `^1.33` of the `phpstan/phpdoc-parser` package to workaround an issue in the `symfony/property-access` package now that the issue is resolved.

### Fixed
- Fixed a bug where the Constant Contact integration could not map to standard fields if no custom fields existed.
- Fixed a bug where user permission checks for creating and deleting forms were not working.
- Fixed a bug where some integrations might lose their `refresh_token`.
- Fixed an autoloading warning issue.
- Fixed a bug where the sample formatting templates' validation errors were not always working correctly when multiple forms with the same template existed in the same template.
- Fixed a bug in the Freeform 4.x migration where it wasn't converting extended ASCII characters in form or field handles.
- Fixed a bug where Quick Export could fail if there were invalid or stale field IDs.
- Fixed a bug where exporting submissions to Excel format could sometimes fail.
- Fixed a bug where the Constant Contact integration instructions were not up to date with the latest setup flow.

## 5.7.1 - 2024-11-12

### Added
- Added `EVENT_DEFINE_SIDEBAR_HTML` in the Freeform Submissions CP view. Thanks @mofman!

### Changed
- Updated CRM integrations in the form builder to include separators for objects to improve clarity.
- Updated composer.json to force the `phpstan/phpdoc-parser` package to `^1.33` to work around an issue in the `symfony/property-access` package.

### Fixed
- Fixed a bug where labels in the Jira integration would sometimes error about array values.
- Fixed a bug where some forms could error when duplicating forms.

## 5.7.0.1 - 2024-11-10

### Fixed
- Fixed a bug where the translation table migration wouldn't work with prefixed databases.

## 5.7.0 - 2024-11-09

### Added
- Added support for site-aware translatable forms inside the form builder (Pro).
- Added PDF Templates that can be dynamically generated and attached to submission notifications (Pro).
- Added the ability to designate where to render a visible captcha in your template.
- Added a **Jira Cards** integration (Pro).
- Added the ability to set custom error messages for **Required** validation on fields.
- Added support for overwriting existing Salesforce Lead records if the email address matches.
- Added a fallback formatting template for forms with a missing or invalid formatting template configured.
- Added a check for `asyncCsrfInputs` on the Diagnostics page.

### Fixed
- Fixed a bug where associated assets were not removed when submissions were deleted in the control panel or via the Submission Purge feature.
- Fixed a bug where the mailer service was resetting template mode too early.
- Fixed a bug where there could be a possible infinite loop in the Submission element.

## 5.6.8 - 2024-10-25

### Fixed
- Fixed a bug where Webhook URLs were not processing ENV variables.

## 5.6.7 - 2024-10-23

### Added
- Added support for sending file uploads to Salesforce objects.
- Added complete translations for the Salesforce integration.

### Fixed
- Fixed a bug where the CP Submissions index would error when displaying File Upload and File Upload Drag & Drop fields.
- Fixed a bug where Hidden and Invisible fields were not included in Freeform Data exports.
- Fixed a bug where the `dateCreated`, `dateUpdated` and `uid` columns were missing when persisting rule conditions on a PostgreSQL install.

## 5.6.6 - 2024-10-18

### Fixed
- Fixed a bug where the Javascript Test was not checking for the presence of the input before updating its value.
- Fixed a bug where email notification jobs did not consider the site ID used when submitting the form.
- Fixed a bug where Invisible fields could not have their values overridden at the template level.
- Fixed a bug where Stripe `payments.amount` only showed whole numbers.
- Fixed a bug where Stripe `payments.id` and `payments.resourceId` were not available to use.
- Fixed a bug where the GTM integration would fail if another script also uses the `form` global variable.
- Fixed a bug where the Freeform 4.x migration was missing a check for `targetFieldHash` before using it.

## 5.6.5 - 2024-10-11

### Added
- Added support for _Cloudflare Turnstile_ spam protection captcha.

### Changed
- Updated the sample Grid and Flexbox formatting templates to allow for compatibility between Group fields and Conditional Rules.

### Fixed
- Fixed a bug where the _Decimal Count_ setting for the Calculation field type was not seeing `0` as a valid value.
- Fixed a bug where the Freeform 4.x migration was converting status colors to `gray` instead of `grey` while in Craft 4.x.
- Fixed a bug where the **Send Success Email from Stripe to Submitter** setting was not respected and Stripe would continue sending email receipts.
- Fixed a bug where using the List view on the forms dashboard could trigger an error for some customers.

## 5.6.4 - 2024-10-07

### Added
- Added support for _Is One Of_ and _Is Not One Of_ rule types for conditional rules, allowing you to perform advanced conditional checks on multi-option field types.
- Added a _Decimal Count_ setting to the Calculation field type for the ability to automatically round result values.
- Added support for PKCE (Proof Key for Code Exchange) in the Pardot integration.

### Changed
- Improved the form builder to remember the last opened items for the Notifications, Rules and Integrations tabs.
- Updated the French translation.

### Fixed
- Fixed a bug where Export Notifications were not working.
- Fixed a bug where disabling email notifications inside the form builder would not disable them.
- Fixed a bug where duplicating a form would exclude email notifications, conditional rules and integrations.
- Fixed a bug where a default error message was not set for the Regex field type.
- Fixed a bug where the Freeform 4.x migration was not migrating or setting a default error message for Regex fields.

## 5.6.3 - 2024-09-27

### Added
- Added a new Stripe `EVENT_AFTER_UPDATE_PAYMENT_METADATA` event that allows additional metadata to be attached to a payment intent when a submission is created.

### Fixed
- Fixed a bug where custom fields were not showing for mapping in the Pipedrive CRM integration.
- Fixed a bug where mapping a field handle of `name` to a Stripe integration will prevent the Stripe field from loading in the front end.

## 5.6.2 - 2024-09-20

### Fixed
- Fixed a bug where the Export Freeform data utility was not loading correctly in Craft 4.
- Fixed a bug where email notification templates could not be assigned to forms in the form builder for some users.
- Fixed a bug where the form "Cards" icon was not showing in the button on the form dashboard page.
- Fixed a bug where form submission and spam counters were not displaying in the dashboard for users and groups without read or manage permissions.
- Fixed a bug where links to modify forms in the List view of the dashboard did not account for user and group permissions.

## 5.6.1 - 2024-09-18

### Fixed
- Fixed a bug where file-based email notification templates were not being read correctly.

## 5.6.0 - 2024-09-17

### Added
- Added a utility for granularly exporting and importing Freeform data (beta), including forms, submissions, templates, integrations, and Freeform settings.
- Added the ability to organize form cards into groups on the Forms dashboard page.
- Added the ability to switch the Forms dashboard page from Cards to a List table view.
- Added the ability to check in Twig to see if an integration is enabled for a form. Use `{% if form.isIntegrationEnabled("integrationHandle") %}`.
- Added template-level overrides for POST Forwarding, allowing for Twig in the POST Forwarding URL and error phrase.
- Added the ability to hide advanced settings for Submit buttons with the Limited Users feature.
- Added compatibility of Hubspot tracking code to the Hubspot CRM integration.
- Added French translation. Thanks @scandella!
- Added German translation.

### Changed
- Updated all sample formatting templates to load all scripts and styles via URLs to aid with strict content security policies. Added a migration for the Grid and Flexbox formatting template names in forms and the demo templates.
- Updated Status column styling to match Craft 5 for CP submissions/spam indexes.

### Fixed
- Fixed various issues with missing translation strings.
- Fixed style issues on Payment cards in the CP submissions index.

## 5.5.10 - 2024-09-06

### Fixed
- Fixed a compatibility issue with Campaign 3.x on Craft 5.x.
- Fixed a bug where an error could occur when creating new forms on some MySQL installs.
- Fixed a bug where the Diagnostics page could error on integration reporting if using PostgreSQL.

## 5.5.9 - 2024-09-04

### Added
- Added support for migrating Submit button labels in the Freeform 4.x migration.
- Added support for migrating conditional rules on Submit buttons in the Freeform 4.x migration.
- Verified support for Craft 5.4.x.

### Changed
- Various updates to Dutch translations.

### Fixed
- Fixed a bug where uploading assets to submissions in the control panel would incorrectly mark them as unfinalized assets to be deleted
- Fixed a bug where the Forms element field type was not alphabetically ordering the list of forms.
- Fixed a bug where some types of conditional rules on fields were not migrating properly in the Freeform 4.x migration.
- Fixed a bug where the Constant Contact integration was not implementing the refresh token interface.
- Fixed a bug where the Pardot integration was missing the `pardot_api` scope.

### Security
- Updated the `phpoffice/phpspreadsheet` dependency to correct an XSS vulnerability.

## 5.5.8 - 2024-08-30

### Fixed
- Fixed a bug where some headers were missing in the Pardot integration authorization request.

## 5.5.7 - 2024-08-14

### Changed
- Updated Webhooks to improve the inclusion of file metadata.
- Updated the Stripe dependency versions to include `v14` and `v15`.
- Changed Freeform so it does not set `noCache` headers on every request. Includes support for Craft's `asyncCsrfInputs` feature.

### Fixed
- Fixed a bug where the `site` parameter would lose track of the active site on nav links in the control panel when using the multi-site feature. For this to work correctly, you must enable the **Site-Aware Forms** setting.
- Fixed a bug where Freeform scripts were being attached to non-Freeform forms.

## 5.5.6 - 2024-08-10

### Added
- Added Keap CRM integration.
- Add a `queuePriority` setting to set the Craft queue priority of Freeform jobs.
- Verified support for Craft 5.3.x.

### Fixed
- Fixed issues with permissions when using the Craft Team edition.
- Fixed a bug where custom field types were not showing in the Express edition.
- Fixed some minor styling issues on the diagnostics page.

## 5.5.5 - 2024-07-30

### Changed
- Refactored exception catching. Added a failsafe which performs a token refresh flow once if an access token returns an unauthorized response.

### Fixed
- Fixed a bug where the **Bypass All Spam Checks for Logged in Users** setting did not correctly bypass all spam checks for logged-in users when a form is cached.
- Fixed a bug where moving fields around in the builder layout would sometimes not work correctly.
- Fixed a bug where the **Surveys & Polls** link in form cards was broken.

## 5.5.4 - 2024-07-11

### Added
- Added `freeform.loadScripts()` template function for manually loading Freeform scripts (e.g. Freeform JS, reCAPTCHA, JS Test, etc).

### Changed
- Changed form deleting from a `DELETE` to `POST` request for better compatibility with server permissions.

### Fixed
- Fixed a bug where Webhooks were being triggered on submissions sent to the Spam Folder.
- Fixed a bug where the CP Submissions chart was not showing for users with no settings access permissions.
- Fixed a bug where integration field mapping wasn't always cleaning up correctly.
- Fixed some Dutch translation language bugs.

## 5.5.3 - 2024-07-04

### Added
- Added support for mapping URLs, email addresses, and phone numbers to the Craft **URL field type** for element integrations.
- Added a `loadFormSpecificScripts` template function for manually loading additional form-specific scripts (e.g. reCAPTCHA, JS Test, etc).

### Changed
- Updated integrations to fail gracefully on the front end when a user submits the form. If there's a configuration issue or error for the integration, the form will continue to process for the user and log an error in the Freeform error log.
- Updated integrations to no longer encrypt setting values configured with an ENV variable. Instead, the ENV variable will be stored in the database to continue working in multiple environments.

### Fixed
- Fixed a bug where email notification jobs would fail when the **Store Submission Data** setting was disabled for the form.

## 5.5.2 - 2024-07-03

### Changed
- Improved field error validation on multi-page forms using Element integrations. If required element fields are not present on the current Freeform form page, their error validation will happen at the end of the form instead.
- Improved integration error messages in the settings page.

### Fixed
- Fixed a bug where email notifications were not rendering all fields from all pages.
- Fixed a bug where email notification jobs did not contain submission object data (if using that to render submission data).
- Fixed a potential ordering issue when dragging fields between rows.

## 5.5.1 - 2024-06-27

### Changed
- Updated the form deletion process to include a delete confirmation modal to prevent accidental deletion of forms.

### Fixed
- Fixed a bug that caused the Email Notification and Integration jobs to break when the **Store Submitted Data** setting was disabled.
- Fixed a bug that intermittently prevented integrations using OAuth from working.
- Fixed a bug where the Spam Blocking integrations page was not visible while admin changes were not allowed.
- Fixed a bug where going to a previous page in multi-page forms would not retain any data entered on the current page.
- Fixed a bug where the Move, Duplicate, and Archive form card buttons were displaying for the Express edition, which is not relevant.

## 5.5.0 - 2024-06-26

### Added
- Added the ability to bulk insert options into option-based field types (Checkboxes, Dropdown, etc).
- Added the ability to archive forms. Archived forms will continue to work and be accessible, but will no longer appear prominently on the Forms dashboard page.
- Added file attachments to be included in the POST Forwarding feature.

### Changed
- Changed spam blocking to be integrations now. Previously, whenever `allowAdminChanges` was set to `false`, it was no longer possible for users to modify blocked keywords, emails and IP addresses. This has been rectified by moving them from the settings to integration types. Users can now create pre-built sets of spam blocks, share them across forms, and manage each individually per form.
    - Removed Blocked Keywords, Emails, and IP Addresses from the Spam Protection settings area.
    - Added new integration types `Keywords`, `Emails` and `IP Addresses`.
    - Added a migration that migrates previous settings into new integrations and applies them to existing forms.
- Updated the **File Drag & Drop** field type to use `title` attribute to display error information on files and removed the `microtip` library, as it was incompatible with a strict Content Security Policy that doesn't allow inline styles or inline scripts to be used.

### Fixed
- Fixed a bug where rearranging fields into different rows in the form builder many times could cause it to stop working correctly.
- Fixed a bug that intermittently prevented integrations using OAuth from working. Added a buffer time for the refresh token check.
- Fixed a bug where failed Webhook integrations would break forms upon submission. Errors are now just logged in the Freeform error log.
- Fixed a bug where Date & Time field validation issues with native HTML types. A new setting has been added.

## 5.4.2 - 2024-06-21

### Changed
- Updated remaining unnecessary inline `style` attributes to be handled in `freeform.css` file instead.

### Fixed
- Fixed some visual issues in the settings area of the Limited Users feature.

## 5.4.1 - 2024-06-19

### Fixed
- Fixed a bug where the form builder would not load correctly on the Express and Lite editions.

## 5.4.0 - 2024-06-18

### Added
- Added Limited Users feature (Pro), which allows you to customize the form builder experience for specific users or groups.
    - You can choose which field types are available and which settings and page tabs are visible.
    - This ensures that these users are not overwhelmed by advanced settings and prevents them from accidentally breaking your forms or site.
    - Multiple limited user groups can be created.
- Added ability to set email notifications and integrations to use Craft's queue.
- Added settings to control the Default 'From Name' and 'From Name' when creating new email notification templates.
- Added a chart to the Submissions index page.
- Added full translation support for the form builder and overhauled the English and Dutch translation files to be complete.

### Changed
- Updated email notifications and integrations to be now handled by Craft's jobs. These can optionally be queued as well.
- Moved all remaining inline CSS and Javascript into external script files. This does not include inline CSS and JS for sample formatting templates.
- Updated the Date & Time field type to use `date`, `time`, and `datetime-local` when not using the Flatpickr date/time picker feature.

### Fixed
- Fixed a bug where the form Save & Continue feature was not working correctly.
- Fixed a bug where Stripe Payment forms would not scroll to the top of the form if there were errors.
- Fixed a bug that prevented clicking the Back button on a page that contained a Stripe Payment field from working.
- Fixed a bug where conditional rules were not being scoped within each form, which could affect other similar forms.

## 5.3.5 - 2024-06-13

### Added
- Verified support for Craft 5.2.
- Verified support for the reCAPTCHA Enterprise API and updated the Settings page.

### Changed
- Updated Survey & Poll report summary page to limit Text answers to the most recent 20 each to help with performance and readability.

### Fixed
- Fixed a bug where the Entries Elements integration was not working correctly on multi-site setups when attempting to map to a section with the primary site disabled.
- Fixed a bug where the spam reason was not displaying correctly for Captchas and JS Test.
- Fixed a bug where 'checked' was available for option-based fields in the GraphQL Explorer.

## 5.3.4 - 2024-06-07

### Added
- Added a list of active modules to the Freeform Diagnostics page.

### Changed
- Minor updates to the plugin icon for better visibility in Craft 5.

### Fixed
- Fixed a potential error when uninstalling Freeform 5.

## 5.3.3.1 - 2024-06-05

### Changed
- Reverted improved Twig template debugging for formatting templates change (5.1.17) for now, as it is causing issues with Windows servers.

## 5.3.3 - 2024-06-05

### Changed
- Updated the Password field type's Minimum Length setting to suppress Craft's minimum password length message when integrating with a Craft User to handle user registration.
- Updated the Freeform Diagnostics page to include additional checks and improve some existing ones.

### Fixed
- Fixed a bug where Windows servers would show an error when attempting to load formatting templates.
- Fixed a bug where a duplicate form filter could sometimes display in the CP Submissions index page.

## 5.3.2 - 2024-05-31

### Changed
- Updating the Surveys & Polls report/results page to include additional CSS that hides the Craft navbar and header for printing (or saving as PDF).

### Fixed
- Fixed a bug where the Table field type's built-in JS and max rows settings inside the form builder did not affect the front end.
- Fixed a bug where _en_ and _em_ dash characters were not saving in submission data on Radios, Dropdown, and Opinion Scale field types.

## 5.3.1 - 2024-05-28

### Fixed
- Fixed a bug that could cause the Craft search index to break if multiple forms had fields with the same handle.

## 5.3.0 - 2024-05-24

### Changed
- Improved the Script Insert feature:
    The _Static URLs_ option is no longer available, and the default is now Craft's _Asset Bundles_ (an improved version of _As Files_). This ensures the best performance and reliability for any site and compatibility with Craft Cloud.
    - The file pointers endpoints (_Static URLs) remain available for backward compatibility.
    - The manual script loading approach (`loadFreeformPlugin`) will also automatically load Asset Bundle URLs.
    - Renamed the _Freeform Script Insertion Location_ and _Freeform Script Insert Type_ settings to _Script Insert Location_ and _Script Insert Type_, respectively.
- Updated the demo templates:
    - The submissions index view now orders submissions in descending order.
    - The submission view now includes an enhanced display of assets for _File Drag & Drop_ fields (in addition to regular File fields).
- Various updates to sample formatting templates:
    - No longer include vague CSS classes to avoid conflict with other site styles. Added `freeform-form` class to the `<form>` tag for all sample templates.
    - No longer force overrides for `addButtonLabel` and `removeButtonLabel` on Table fields.
    - Improved display and positioning of submit buttons on _Basic_ sample templates.
    - Improved display of success and error banners on _Basic_ sample templates.

### Fixed
- Fixed a bug where the `tableAttributes` template overrides for Table fields were not working.
- Fixed a bug where displaying assets from File fields in CP Submissions was broken.
- Fixed a bug where the _Theme_ setting was mislabeled for _File Drag & Drop_ fields inside the form builder.

## 5.2.2 - 2024-05-22

### Added
- Added `freeform-stripe-load` event for Payments forms and moved Mutation Observer outside of `window.onload`.

### Changed
- Updated the Conditional Rules JSON attachment from `<script>` to `<div>` with a data attribute for better compatibility with JS frameworks.

### Fixed
- Fixed a bug where fetching Payment records (`freeform.payments`) in the Submissions query was not available.
- Fixed a bug where an error about `freeform_feed` would be triggered when attempting to install Freeform.

## 5.2.1 - 2024-05-21

### Fixed
- Fixed a bug where the Express Forms migration was not working as of Freeform 5.1.19+.

## 5.2.0 - 2024-05-15

### Added
- Added optional **Site Filtering** setting for forms and submissions (Pro). This setting allows you to filter form lists by Sites and prevents other admins from accessing forms that belong to Sites they don't have access to.
    - This only controls access to forms and submissions. It does not enable translations per site.
    - If you'd like to apply this change retroactively, you should run the `./craft freeform/submissions/resave` CLI command after enabling this setting.
- Added settings for **Password** field to set a minimum number of characters to be used (_Min Length_) and whether the password should contain at least one number, one lowercase letter, one uppercase letter, and one special character (_Character Variability_).
- Added an integration for **Google Sheets** (Pro).

### Changed
- Updated sample formatting templates to use `novalidate`. It was previously incorrectly applied to inputs instead of `<form>`.

### Fixed
- Fixed a bug where Stripe payment information was unavailable in email notifications.

## 5.1.19.1 - 2024-05-09

### Fixed
- Fixed a bug where attempting to update to Freeform 5.1.19 could cause the process to crash.
- Fixed input placeholder styles for Bootstrap sample formatting templates.

## 5.1.19 - 2024-05-08

### Changed
- Updated the **Settings → Statuses** to no longer have conflicting logic to set the default status for new forms. This is now done in the **Settings → Form Builder** area in Freeform 5+.
- Improved integration errors to log more meaningful error messages.

### Fixed
- Fixed a bug where the Update Notices feature for the Freeform dashboard was not working.
- Fixed a bug where clicking the Source type an additional time would clear any option settings you currently have for Options-based field types.
- Fixed a bug where setting `allowedGraphqlOrigins` to `false` in _general.php_ would cause form submissions to crash.

## 5.1.18.1 - 2024-05-06

> [!WARNING]
> If you are experiencing visual issues with form layouts and styles on the front end, please update Freeform and ensure you're adding the `|raw` filter to `form.attributes.success` and `form.attributes.errors` in any custom formatting templates you may have when not using AJAX.

### Changed
- Added the `|raw` filter to `form.attributes.success` and `form.attributes.errors` in sample formatting templates as they began parsing with an extra set of quotes around them when not using AJAX.

## 5.1.18 - 2024-05-06

> [!WARNING]
> If you are experiencing visual issues with form layouts and styles on the front end, please update Freeform and ensure you're adding the `|raw` filter to `form.attributes.row` in any custom formatting templates you may have.

### Changed
- Added the `|raw` filter to `form.attributes.row` in sample formatting templates as they began parsing with an extra set of quotes around them.

### Fixed
- Fixed a bug where forms would fail when loading if a **Confirm** field was used and the **Duplicate Check** setting was set to _Anyone - Once per Email Address_.
- Fixed a bug where the form builder's field association badges for Checkbox, HTML, and Rich Text fields did not display correctly.

## 5.1.17 - 2024-05-03

### Added
- Added **Assets** as an Element source for populating Freeform field options.
- Confirmed compatibility with Craft 5.1.x.

### Changed
- Improved Twig template debugging for formatting templates.
- Updated **Tailwind** formatting template to have instructions below the field label instead of below the input.
- Updated the JSON payload from forms to no longer contain details of the user(s) that created and last updated the form.
- Updated the **Field Type Manager** to store field types set to hidden in the database instead of the Project Config file.

### Fixed
- Fixed a bug where Freeform fields with long handles could cause Craft's search indexing jobs to fail.
- Fixed a bug where Export Notifications would fail if they contained multiple email addresses.
- Fixed a bug where conditional rules were outputted to the browser console.
- Fixed a bug where some integration could fail due to model properties being before EVENT_BEFORE_SAVE.
- Fixed a bug where Notification Persistence would throw errors if the form upsert event has errors.

## 5.1.16.1 - 2024-04-29

### Fixed
- Fixed a bug where the update migration for 5.1.16 was not automatically being triggered.

## 5.1.16 - 2024-04-26

### Added
- Added the ability to set conditional rules that can prematurely fully submit the form if matched.
- Added the ability to set conditional rules on Submit, Back and Save buttons.
- Added support for using conditional rules across different pages, e.g. _Field C_ on _Page 2_ will display if _Field A_ on _Page 1_ is _X_.

### Changed
- Updated the color of submit buttons inside the form builder to dark gray to prevent confusion with actual action buttons.

### Fixed
- Fixed a bug where saving any Freeform settings would clear the Form Builder "defaults" settings.
- Fixed a bug where submissions flagged as spam would still send data off to integrations instead of being queued.
- Fixed some potential issues with submitting Checkbox fields.

## 5.1.15 - 2024-04-24

### Changed
- Updated GraphQL to include HTML and Rich Text field types.

### Fixed
- Fixed a bug where the _Export Profiles_ page in the CP would display an error in Craft 5 when any profiles exist.
- Fixed a bug where Stripe Payment submissions were still processed when flagged as spam by Freeform.
- Fixed a bug where the form builder included two labels for Checkbox fields.
- Fixed a bug where creating a new Site and setting it to be the Primary site (at the same time) would fail due to Freeform.
- Fixed a bug where the old approach of `FreeformField_Submit` and `FreeformField_Save` were still showing under `fields` in the GraphQL explorer.

## 5.1.14 - 2024-04-19

### Added
- Added `freeform/submissions/generate` CLI command to allow the creation of fake submission data into forms. Useful for testing and troubleshooting some issues.
- Added support for searching into specific fields in the CP Submission element search bar (e.g. `firstName:bob`). You may need to run `./craft freeform/submissions/resave --update-search-index` once for this to work.

## 5.1.13.1 - 2024-04-18

### Fixed
- Fixed an issue with the **Dotdigital** integration.

## 5.1.13 - 2024-04-17

### Added
- Added Craft element search support for submissions. You may need to run `./craft freeform/submissions/resave --update-search-index` once for this to work.
- Added `freeform/submissions/resave` CLI command to resave all existing submissions.
- Added `freeform/submissions/fix-titles` CLI command to fix the migration of Element titles for sites that have upgraded from Craft 4 to Craft 5. This command needs to be run once after the migration.

### Fixed
- Fixed a visual issue with the Quick Export modal styles in Craft 5.
- Fixed a bug where the Express Forms migration utility would not work if there were no fields in a form.
- Fixed a race condition issue where refreshing the browser in the form builder would sometimes show stale or invalid data on section tabs.

## 5.1.12 - 2024-04-12

### Changed
- Changed all `PUT` requests to `POST` requests for sites with strict method allowlists.
- Updated the form builder to include the Craft notice banner for successes and errors when saving the form.
- Updated success/error notices throughout the plugin.
- Updated breadcrumbs throughout the plugin.

### Fixed
- Fixed a bug where error validation was not working correctly in the form builder.

## 5.1.11 - 2024-04-11

### Changed
- Refactored Captcha loaders and added lazy load support.
- Improved Failure Behavior settings for Captchas inside the form builder.
- Improved display of **HTML**, **Rich Text**, **Hidden** and **Invisible** field types in the form builder.
- Updated the `fields` and `lists` limits in the **ActiveCampaign** integration.
- Updated the `stripe/stripe-php` dependencies to include `^13.0`.
- Updated the `symfony/property-access`, `symfony/finder`, `symfony/filesystem` and `symfony/expression-language` dependencies to remove `2.8|^3.0|^4.0`.
- Updated the `symfony/serializer` dependency to include `^5.0` and `^7.0`.
- Updated the `hashids/hashids` dependency to include `^5.0` and remove `^2.0`.
- Removed the `composer/composer` dependency requirement.

### Fixed
- Fixed an issue with the **Dotdigital** integration.
- Fixed a deprecation error when viewing the CP Submissions index in Craft 5.
- Fixed an alignment issue with the Submit buttons in the CP Submission and Spam detail pages.
- Fixed a bug where Element integration field mapping was not reliably loading and saving data.
- Fixed a bug where Email Marketing integrations could fail when field mapping is updated.
- Fixed a bug in which the form's **Updated** date was not correctly updated.
- Fixed a bug where the Javascript Test was loading inside the CP Submission detail page.
- Fixed a bug where an element query could execute before Craft loaded.

## 5.1.10 - 2024-04-04

### Changed
- Updated the `symfony/property-access`, `symfony/finder`, `symfony/filesystem` and `symfony/expression-language` dependencies to include `^7.0`.
- Updated the `nesbot/carbon` dependency to include `^3.0.0"`.

## 5.1.9 - 2024-04-03

### Added
- Added an updated **Dotdigital** email marketing integration.
- Added an `EVENT_UPDATE_PAYMENT_METADATA` developer event for modifying Stripe metadata.

### Changed
- Refactored the form submit and submit button lock process to work reliably when more complex features and flows are active (e.g. Stripe, Captchas, File Drag & Drop, etc).
- Updated the domain extension length maximum from `6` to `15` for **Website** fields.
- Exposed the **Maximum Length** field setting for GraphQL.

### Fixed
- Fixed a bug where Freeform fields could not map to Salesforce Checkbox fields.
- Fixed an issue with Stripe subscriptions and empty product names. Also added a failsafe for the product name.
- Fixed a bug where the Page tab label editor in the builder was broken when a scrollbar was present (lots of pages).
- Fixed a bug where the **Max Length** setting was not present for applicable fields.
- Fixed a bug where deleting **Group** fields could delete other fields that share its row in the builder.
- Fixed a bug where the **reCAPTCHA v2 Checkbox** was not being validated if left empty on a form with Stripe.
- Fixed a bug where the Submit button was prematurely becoming active again before the Stripe payment form was fully processed.
- Fixed a bug where Captchas were loaded inside the CP submission detail page when enabled.

## 5.1.8 - 2024-03-27

### Added
- All _reCAPTCHA_ and _hCaptcha_ versions are now available for the **Lite** edition (as well as **Pro**).

### Changed
- Updated the delete icon display and behavior for managing page tabs in the builder.

### Fixed
- Fixed a bug where the form submit button was not always locking correctly with forms using Stripe or File Upload fields.
- Fixed a bug where deleting Group fields in the builder was not also removing the fields inside.
- Fixed a visual issue when editing page tab names in the builder when using the Safari and Firefox browsers.
- Fixed a visual issue for field blocks in the builder when using the Microsoft Edge browser.
- Fixed a bug where long field labels overlapped other field labels in the builder.
- Fixed a bug where conditional rules for forms might not always migrate correctly from Freeform 4.

## 5.1.7 - 2024-03-22

### Fixed
- Fixed a bug where reCAPTCHA would conflict with forms using Stripe Payments.
- Fixed a bug where mapping a field with a handle of `name` would break the Stripe integration.
- Fixed a bug where running the migration from Freeform 3.x could trigger a `Base table or view already exists` error.
- Fixed some Safari browser stying issues with the horizontal scrollbar for page tabs.

## 5.1.6 - 2024-03-20

### Added
- Added a **Not Like** option for filtering on field values in Export Profiles.

### Changed
- Improved the form builder so it doesn't prematurely close the property editor when a click and drag happens beyond it (e.g. selecting a value in a text input field to clear and replace it).

### Fixed
- Fixed a bug where fields could have conditional rules for each other and cause an infinite loop.
- Fixed a bug where removing conditional rules did not always remove the correct rule set.
- Fixed a bug where using multiple Stripe fields from multiple Stripe accounts in the same form would not work correctly.

## 5.1.5.1 - 2024-03-20

### Fixed
- Fixed a bug where a change for setting `nocache` headers in Freeform 5.1.5 broke the Craft CLI.

## 5.1.5 - 2024-03-19

### Changed
- Updated the CSRF input to send `nocache` headers when rendering. 
- Adjusted the Diagnostics page to include PHP 8.2 and 8.3 compatibility.

### Fixed
- Fixed a bug where Freeform was opening unnecessary sessions.
- Fixed a bug where page tabs would become inaccessible when the number of tabs exceeds the width of the browser window.
- Fixed a bug where mapping to the _Enabled_ setting on Craft Entry element integrations was not working.
- Fixed a bug where user group selection in the User element integration did not allow multiple choice.
- Fixed a bug where files were not being uploaded for regular File Upload fields when not using AJAX.
- Fixed a bug where the User element integration was not always working when used in _edit_ mode.
- Fixed a bug where changes to email notifications in the form builder were not sticking if you switched to another tab.
- Fixed a bug where the default theme option for Stripe was incorrectly named `default` instead of `stripe`, causing console errors in the browser.

## 5.1.4 - 2024-03-14

### Added
- Added the ability to add attributes to Captcha containers at the template level.

### Changed
- Reverted the Campaign Monitor to API tokens (instead of OAuth) to address several complications.

### Fixed
- Fixed a bug where the Freeform 4 to 5 migration could fail when creating submission database tables in some circumstances.
- Added clarity to the **Activate Users** setting for the User element integration.

## 5.1.3 - 2024-03-11

### Fixed
- Fixed a bug where Dynamic Recipient fields could sometimes fail in the Freeform 4 to 5 migration.
- Fixed a bug where saving submissions via console commands would fail on the saved notes feature.
- Fixed a bug where the Calculations field type was not triggering the Stripe element refresh when set as the dynamic amount field while set as _Plain Text_.
- Fixed a bug where the Empty Option Label feature was showing on fields other than Dropdowns.

## 5.1.2 - 2024-03-07

### Fixed
- Fixed a bug where Success Templates were not being mapped in the Freeform 4 to 5 migration.
- Fixed a bug where the Calculations field type was not triggering the Stripe element refresh when set as the dynamic amount field.
- Fixed a bug where changing a form's Form Type had no effect.
- Fixed a bug in conditional rules logic that prevented fields with handles beginning with a number from working correctly.

## 5.1.1 - 2024-03-06

### Added
- Added compatibility with Craft 5 beta (in addition to Craft 4.x).
- Added back support for an optional Empty Option Label for Dropdown fields that are populated by Elements or Predefined options.
- Added support for Automatic Spam Purging in the **Lite** edition of Freeform.

### Changed
- Refactored the Submission Purge functionality to use the Craft queue.
- Updated the Stripe dynamic amount field setting to accept Calculation fields.

### Fixed
- Fixed a bug where the default Status set inside the builder was not being respected.
- Fixed a bug where only the first File Upload field would work if using multiple File Upload fields in the form.
- Fixed a bug where the Freeform 4 to 5 migration was setting the Success Behavior setting to _Reload_ for all forms instead of matching what was set in Freeform 4.
- Fixed a bug where converting a field with Array data to a different field type without (e.g. _Checkboxes_ to _Dropdown_) would trigger an error.
- Fixed a bug where the Calculation field was not being added to the Special field group on migration.

## 5.1.0 - 2024-03-01

### Added
- Added a Calculation field type, which allows you to perform dynamic calculations based on user-input values within forms.
- Added a migration from the Express Forms plugin. It will import forms and fields, submissions, and notification templates.
- Added ability to set dynamic notifications using GraphQL.

### Fixed
- Fixed a bug where page buttons were not translatable.

## 5.0.16 - 2024-02-28

### Fixed
- Fixed a bug where the Stripe payments field would not load correctly when logged out.

## 5.0.15 - 2024-02-27

### Changed
- Updated all sample formatting templates to include complete Stripe appearance API customization.

### Fixed
- Fixed a bug where connecting to new integrations using OAuth 2.0 were not working due to the redirect URI being empty.

## 5.0.14.1 - 2024-02-23

### Fixed
- Fixed some remaining issues related to the migration from Freeform 4.

## 5.0.14 - 2024-02-23

### Changed
- Updated the reserved words list to make exceptions for `name`, `type`, and `username` as they are more likely to be used and don't appear to cause any issues.

### Fixed
- Fixed a bug where Confirm fields were present in email notifications.
- Fixed a bug where the Page Skipping feature for Conditional Rules was not working correctly.
- Fixed a bug where forms would error when Freeform Date fields were mapping to Craft date fields (e.g. Post Date, Expiry Date, etc) in Element integrations.
- Fixed a bug where setting a template override for the submission status was not working.

## 5.0.13 - 2024-02-23

### Added
- Added a reserved word validator using Craft's reserved words to check against field handles.
- Added the ability to map directly to the **Full Name** in the Craft User element integration.

### Changed
- Updated Confirm fields to no longer store data when targeting a Password field.

### Fixed
- Fixed a bug where creating new forms with special or foreign characters would cause the form not to be created due to an invalid form handle.
- Fixed a bug where the _Fill Form Values from the GET Query String_ setting was not being respected.
- Fixed a bug where editing existing users via the Craft User element integration in a Freeform form did not affect **First Name** and **Last Name** fields.
- Fixed a bug where the Page Skipping feature for Conditional Rules was not working at all.
- Fixed a bug where the Stripe Payments field was not working with the Tailwind sample formatting template (and potentially some custom templates).
- Fixed a bug where some sample formatting templates showed unnecessary styling wrappers around Stripe Payments fields.

## 5.0.12 - 2024-02-19

### Added
- Added support for querying conditional rules for fields and pages in GraphQL.

### Fixed
- Fixed a bug where migrated forms with a Dynamic Recipients field (not User Select) would trigger an error loading/submitting the form.
- Fixed a bug where Regex fields were triggering an error if left empty when submitting the form.

## 5.0.11 - 2024-02-16

### Added
- Added a setting to allow users to enable dashes in field handle names.

### Fixed
- Fixed several issues related to the migration from Freeform 4, including table prefixes and field handles that are too long getting corrupted.
- Fixed an issue where table prefixes were not being respected on fresh installs.
- Fixed the precedence order for overriding attributes in formatting templates. Overrides in the template loading the form now take precedence over the formatting template overrides within it.
- Fixed a bug where editing/saving a submission inside the control panel could sometimes error about a user ID being `0`.
- Fixed a bug where the Stripe Webhook URL was incorrectly including a CP admin path. Any existing Stripe integrations will need to be manually adjusted.
- Adjusted the JS in a few of the sample templates.

## 5.0.10 - 2024-02-08

### Added
- Added PKCE (Proof Key for Code Exchange) implementation for integrations using the OAuth2.0 flow.
- Added support for PKCE (Proof Key for Code Exchange) in the Salesforce integration.

### Fixed
- Fixed a bug where the CP Submission detail pages were not handling conditional rule logic correctly.
- Fixed a bug where the **Send Digest Email** job was added to the Craft queue even when turned off.
- Fixed a bug where the Stripe Payments field would not load in the form when the **Freeform Script Insertion Location** setting was set to _Page Header_.
- Fixed a bug where the **Use Option Labels when Exporting** setting was causing exports to fail.
- Fixed a bug where the Freeform was causing _Element query executed before Craft is fully initialized_ errors to be logged in the Craft logs.

## 5.0.9 - 2024-02-07

### Fixed
- Fixed a bug where the Mailchimp integration was only showing one audience/mailing list.
- Fixed a bug where the `fieldIdPrefix` parameter was not working.
- Fixed a bug where the Conditional Rules value input was not being hidden for empty condition rule types.

## 5.0.8 - 2024-02-06

### Changed
- Implemented better cache busting for loading script pointers.
- Implemented a unified entry point for loading scripts based on current settings.
- Implemented a single Stripe script loader and mutation observer.
- Updated dropdown settings to have a clearer distinction between mapped/unmapped items.

### Fixed
- Fixed a bug where the Stripe Payment field would sometimes not load on the front end.
- Fixed a bug where all old integrations were not being cleared during the migration from Freeform 4.

## 5.0.7 - 2024-02-02

### Added
- Added support for querying page Submit buttons in GraphQL.

### Changed
- Updated the HubSpot integration to use v3 of the API and the private app token approach instead of OAuth flow.

### Fixed
- Fixed a bug where hyphens were allowed in form and field handles.
- Fixed a bug where the GraphQL cache was not resetting after making form updates.
- Fixed a bug where `maxLength` was included in Text and Textarea field types in GraphQL when not applicable.

## 5.0.6.1 - 2024-02-01

### Fixed
- Fixed a bug where integration settings pages were not being displayed in the navigation when `allowAdminChanges` was set to `false`.
- Fixed a bug where the Dynamic Template Notifications feature was not working correctly.

## 5.0.6 - 2024-01-31

### Added
- Added the ability to manually render Submit buttons in forms.

### Changed
- Adjusted the _Manual Form_ extra template in the demo templates to use the new manual Submit button approach.

### Fixed
- Fixed a bug where the **replace** syntax for template overrides (e.g. `=class`) was not working correctly.
- Fixed a bug where `0` was not considered a valid value for a Number field with the `required` validator.
- Fixed a bug where regular File Upload fields were not working correctly if the field was set to be required.
- Fixed a bug where field values/default values were not returning correctly in GraphQL queries.
- Fixed a bug where some sample formatting templates did not correctly style the File Upload Drag & Drop fields.

## 5.0.5 - 2024-01-30

### Changed
- Updated form rendering to work when iterating over rows directly in the `form` object for better backward compatibility with the Freeform 4.x approach.
- Updated `form.successMessage`, `form.errorMessage` and `field.rulesHtmlData` to have fallbacks (that are empty) to prevent hard errors in old formatting templates that use them.

### Fixed
- Fixed a bug where user permissions were not correctly being considered on form cards on the Forms dashboard page.
- Fixed a bug where the weekly/daily email Digest feature was not always working correctly.
- Fixed a visual bug where the breadcrumbs in the form builder would formulate incorrectly when refreshing the browser.

## 5.0.4 - 2024-01-29

### Added
- Added a _Page Header_ option for the **Freeform Javascript Insertion Location** setting.

### Fixed
- Fixed a bug where sites using Postgres would fail during migration.
- Fixed a bug where an error about `includeAttachments` on notification templates could occur for some sites.
- Fixed a bug where Captchas would display on all pages instead of just the last page.
- Fixed a bug where misconfigured options fields could crash the Freeform 4 migration.
- Fixed a visual bug where the breadcrumbs in the form builder could formulate incorrectly when saving the form.

## 5.0.3 - 2024-01-26

### Changed
- Updated the weekly/daily email Digest to use Craft's queue jobs.
- Updated form rendering to not hard error if `form.customAttributes` is used in an older formatting template. It now logs a notice to the Craft deprecation warning log.

### Fixed
- Fixed a bug where manually coded forms were not having the form method being set automatically.
- Fixed a bug where rendering a form through the Freeform Form field on another element could trigger an error.
- Fixed a bug where multi-option fields were not working correctly with Conditional Rule values.
- Fixed a bug where File Upload Drag & Drop fields were not working correctly if the field was set to be required.
- Fixed a bug where mandatory attributes were showing up in the form builder attribute editor.
- Fixed the `extras/manual-form` demo template to work correctly with Freeform 5.

## 5.0.2 - 2024-01-25

### Added
- Verified compatibility with Craft 4.7.

### Fixed
- Fixed a few compatibility issues with PHP 8.0.
- Fixed a bug where the migration could potentially convert a couple of the default fields incorrectly in the first form.

## 5.0.1 - 2024-01-23

### Fixed
- Fixed an `instanceof` check issue on the Freeform 5 migration.
- Fixed a bug where default form success/error messages were not being added if empty.

## 5.0.0 - 2024-01-22

> [!WARNING]
> If upgrading from Freeform 4, please see the special [upgrade guide](https://docs.solspace.com/craft/freeform/v5/setup/updating-freeform-4/) before proceeding.

### Added
- **Form Builder**
    - Fields can be saved as **Favorites** for quick use in other forms.
    - Fields from other forms can be **searched** and reused in your form.
    - A **Field Type Manager** has been added to the form builder. It allows you to show/hide field types, arrange them into groups, and color code them.
    - Created/Updated dates and author information are now stored for each form and visible inside the form builder.
    - The **Limited Users** feature allows you to easily customize the form builder experience for specific users or groups, ensuring that these users are not overwhelmed by advanced settings and prevents them from accidentally breaking your forms or site.
    - Configure email notifications in the form builder using complex conditional rules based on field data.
    - A wide range of form builder settings can now have default values set for them, and can also be locked to that value. For example, you can force the Tailwind 3 formatting template to be used for every form.
- **Fields**
    - The **Group** field type allows you to nest multiple fields inside. Additionally, conditional rules can be applied to Group fields.
    - Fields being populated with **Element** or **Predefined** data can now have the data converted to **Custom** options so they can be modified, added to, removed, reordered, etc.
    - Fields can now be individually encrypted.
    - **Custom field types** are available to be created now.
- **Email Notifications**
    - Configure email notifications in the form builder using complex conditional rules based on field data.
- **Integrations**
    - Captchas now include a setting inside the form builder to force a country code, e.g. `en`, `de`, etc. If left blank, the locale will be auto-detected.
- **Templating**
    - The **Template Overrides** feature enables modification of attributes for the form, fields, and buttons, as well as overriding field labels, values, and instructions at the template-level.
    - The **Settings** object allows you to access all of the form's settings assigned to it in the form builder, e.g. `form.settings.errorMessage`.
    - The **Multipage All Fields** formatting template replaces the **Bootstrap 5 Multipage All Fields** template.
    - The `labels` and `labelsAsString` methods are now available for all _option_ field types. This allows you to choose between displaying option labels instead of values when loading submission data in front end or email notifications.
    - The `implements` method is available to all fields for Twig-friendly implementation checks, e.g. `field.implements('options')`.
    - The global `freeform` variable allows shorthand for template queries, e.g. `freeform.form` instead of `craft.freeform.form`.
- **Stripe Payments**
    - The Pro edition now includes fresh support for the newer **Stripe Payment Element**.
    - Support for **Stripe Link**, **Apple Pay**, **Google Pay**, **PayPal** (within Europe), **bank payments**, **deferred payments** and many other options.
    - Ability to include more than one Stripe payment element field in a form. When used with conditional rules, you can show/hide one Stripe element at a time (e.g. use a dropdown field to allow the user to choose between one-time or recurring payments).
- **Surveys & Polls**
    - The _Freeform Surveys & Polls_ plugin features are now included in the _Pro_ edition of Freeform. Please see the special [upgrade guide](https://docs.solspace.com/craft/freeform/v5/setup/updating-freeform-4/) before proceeding.

### Changed
- **Control Panel**
    - The **Dashboard** and **Forms** pages have been combined and redesigned.
    - The **Email Notifications** subnav menu item has been renamed to **Notifications**.
    - All settings and references of `behaviour` have been updated to `behavior`.
- **Form Builder**
    - Completely redesigned the form builder.
        - Settings and other features are now in full-page tabs to allow for lots of room to configure.
        - Fields are specific to forms and added by dragging fresh field types into the layout.
    - Reorganized all form settings and behaviors into multiple subsections of a unified **Settings** tab.
    - Reorganized **Email Marketing**, **CRM**, **Element**, **Stripe**, **Captcha**, **POST Forwarding** and **Google Tag Manager** settings into multiple subsections of a unified **Integrations** tab.
    - The **Conditional Rules** tab has been greatly improved to include a field map along with visual cues, making configuration faster and less confusing.
    - Some of the option values for the **Duplicate Check** (formerly _Limit Form Submission Rate_) setting have been changed.
- **Fields**
    - Fields are now created and specific to each form (vs. being global to all forms).
    - Fields can be saved as Favorites or searched upon to be reused in other forms.
    - Fields can now be changed to other field types at any point, but be aware that data loss could occur when switching incompatible field types.
    - The **Checkbox Group** field type has been renamed to **Checkboxes** (and `checkboxes` in formatting templates).
    - The **Radio Group** field type has been renamed to **Radios** (and `radios` in formatting templates).
    - The **Select** field type has been renamed to **Dropdown** (and `dropdown` in formatting templates).
    - The field type handles of `cc_details`, `confirmation`, `file_drag_and_drop`, `multiple_select`, `opinion_scale`, and `rich_text` have been renamed to `credit-card`, `confirm`, `file-dnd`, `multiple-select`, `opinion-scale`, and `rich-text`, respectively.
    - The **Dynamic Recipients** field type has been replaced with the **User Select** feature, which provides the ability to assign a notification layer to any Dropdown, Checkboxes, Radios, or Multi-Select field types.
    - The **Email Marketing**/**Mailing List** special field type has been replaced with the ability to assign the Email Marketing integration to any existing Checkbox or Hidden field. This will also allow you to keep a record in Freeform of whether the mailing list was subscribed to by the user.
    - The **reCAPTCHA v2 Checkbox** and **hCaptcha Checkbox** special fields are now inserted into the form automatically (before the Submit button).
    - The **Submit** and **Save & Continue Later** buttons are now automatically inserted at the end of each form page.
    - The **Opinion Scale** field type markup for manual templating has been adjusted slightly.
- **Email Notifications**
    - The form builder now has a **Notifications** tab dedicated to configuring all types of email notifications (except for template-level ones).
- **Integrations**
    - The **Element Connections** feature is now referred simply to **Element** integrations.
    - **Element** integrations are now set up in the Freeform settings area and then configured per form.
    - **Webhook** integrations are now configured per form (but still set up initially in the Freeform settings area).
    - Integrations with more than one type have been unified with expanded functionality:
        - _Salesforce Leads_ and _Salesforce Opportunities_ are now in a single _Salesforce_ integration.
        - _Pipedrive Leads_ and _Pipedrive Deals_ are now in a single _Pipedrive_ integration.
        - _Zoho Leads_ and _Zoho Deals_ are now in a single _Zoho_ integration.
    - Integrations that map to more than one endpoint allow more flexibility when choosing which endpoints to map to.
    - The Salesforce integration has been changed to OAuth validation (instead of username/password).
    - The Pipedrive integration has been changed to OAuth validation.
    - The **dotmailer** integration has been updated and renamed to **Dotdigital**.
    - All **MailingList**/**mailing_list**, etc, references in the code and database have been renamed to **EmailMarketing**/**email_marketing**, etc.
- **Settings**
    - The _Formatting Templates_, _Email Templates_ and _Success Templates_ settings pages have all been combined into a single **Template Manager** settings page.
    - Reorganized and adjusted settings pages.
    - The **Limit Form Submission Rate** setting has been renamed to **Duplicate Check**. Available options have been revised and renamed for clarity as well.
- **Spam Protection**
    - The **Freeform Honeypot** and **Javascript Test** features have been decoupled, overhauled, and set up as integrations. They can now be enabled/disabled and configured per form. The Javascript Test is now a simpler approach that will streamline use with caching or headless implementations.
    - The **Javascript Enhancement** feature has been renamed to **Javascript Test**.
    - **Captchas** are now stored as integrations, can have multiple configured per site, and can be turned on/off and further configured at the form level inside the form builder, e.g. stricter settings, different behavior, etc.
    - The **reCAPTCHA v2 Checkbox** and **hCaptcha Checkbox** special fields are now inserted into the form automatically (before the Submit button).
- **Templating**
    - The `suppress` parameter (for suppressing email notifications and integrations when editing submissions on the front end) has been renamed to `disable` and has had the `dynamicRecipients`, `submitterNotifications`, `connections` parameter names changed to `userSelectNotifications`, `emailFieldNotifications`, and `elements`, respectively. An additional `conditionalNotifications` parameter has been added to account for the new _Conditional Notifications_ feature.
    - All formatting templates have been updated and improved.
    - The **Bootstrap 5 Multipage All Fields** formatting template has been transitioned to a "Basic" non-Bootstrap version, now called **Multipage All Fields**.
    - Form settings and behaviors can now all be accessed in templates via `freeform.settings.settingName`.
    - The `option.checked` property has been updated to an approach that compares `option.value` to `field.value`.
    - The `disableRecaptcha` parameter is now `disableCaptcha`.
    - All references to `loading` (text and/or spinner indicator displayed on the submit button) are now `processing`.
    - All references to `spinner` (spinner indicator displayed on submit button) are now `processingSpinner`, etc.
    - The `limitSubmissions` parameter has been renamed to `duplicateCheck` and the values have been renamed for clarity.
    - The `submissionLimitReached` property in the Form object has been renamed to `duplicate`.
    - All references to `freeform-file-drag-and-drop` for CSS overrides have been updated to `freeform-file-dnd`.
- **Javascript**
    - The defaults for `errorClassBanner`, `errorClassList`, `errorClassField` and `successClassBanner` plugin options for JS overrides have been adjusted to `freeform-form-errors`, `freeform-errors`, `freeform-has-errors` and `freeform-form-success`, respectively (`ff-` changed to `freeform-`).
- **GraphQL**
    - The `FreeformFormInterface` fields `showSpinner`, `showLoadingText` and `loadingText` have been renamed to `showProcessingSpinner`, `showProcessingText` and `processingText` respectively.

### Deprecated
- The _PHP Sessions_ and _Database Table_ options for the Freeform Session Context setting are deprecated and are planned to be removed in Freeform 6. Encrypted Payload continues to be the assumed and recommended approach, but can still be overrided to PHP Sessions or Database Table in project config.

### Removed
- **Control Panel**
    - The **Field Manager** area (**Freeform → Fields**) has been removed, as fields are no longer globally shared unless they are saved as Favorites.
    - The **Dashboard** page has been removed in favor of a redesigned **Forms** page.
    - Exporting "shortcuts" from the Dashboard is no longer available with the removal of the **Dashboard** page, but many other exporting options are available.
    - The **What's New** feature has been removed in favor of using Craft's Announcements feature.
    - The **Resources** area inside the Freeform control panel has been removed.
    - The **Form Builder Tutorial** and **Install Demo Banner** settings have been removed.
    - The **Stats** widget has been removed as it isn't very relevant anymore.
    - The **Form Values** widget has been removed.
- **Settings**
    - The **Access Fields** and **Manage Fields** permissions have been removed as they are no longer applicable.
    - The **Display Order of Fields in the Form Builder** setting has been removed as it is no longer applicable.
    - The following **Project Config** items have been removed due to the improvements to Form Builder defaults:
        - `defaultTemplates` - use `includeSampleTemplates: true` under `defaults:` instead.
        - `renderFormHtmlInCpViews` - use `previewHtml: true` under `defaults:` instead.
        - `twigInHtml` - use `twigInHtml: true` under `defaults:` instead.
        - `twigInHtmlIsolatedMode`. - use `twigIsolation: true` under `defaults:` instead.
        - `formattingTemplate` - use `value: basic-light/index.twig` under `defaults:` → `settings:` → `general:` → `formattingTemplate:` instead.
        - `ajaxByDefault` - - use `value: '1'` under `defaults:` → `settings:` → `processing:` → `ajax:` instead.
    - The `freeformHoneypot`, `freeformHoneypotEnhancement`, `customHoneypotName`, `customErrorMessage`, `recaptchaBehaviour`, `recaptchaEnabled`, `recaptchaErrorMessage`, `recaptchaKey`, `recaptchaSecret`, `recaptchaLazyLoad`, `recaptchaMinScore`, `recaptchaSize`, `recaptchaTheme` and `recaptchaType` settings have been removed from **Project Config**, as Honeypot and Captchas are stored as integrations now.
    - The **Additional Optional Checks** setting for the **Update Warnings & Notices** feature has been removed.
    - The **Freeform Session Context** setting has been removed. _Encrypted Payload_ continues to be the assumed and recommended approach, but can still be overrided to _PHP Sessions_ or _Database Table_ deprecated options in project config.
    - The `freeform_lock` database table has been removed as it is no longer used.
- **Templating**
    - The **Bootstrap 3**, **Bootstrap 4**, **Bootstrap 5 Multipage All Fields** and **Tailwind 1** formatting templates have been removed.
    - The `overrideValues` parameter for Form queries has been removed. Please use the `value` parameter in the new **Template Overrides** feature.
    - The `option.checked` property has been removed. Please use and compare `option.value` to `field.value`.
    - The `disableRecaptcha` template parameter has been removed. Please use `disableCaptcha` instead.
    - The `limitFormSubmissions` property has been removed from the `form` object. Please use `form.settings.limitSubmissions` instead.
    - The `freeform/fields/create` field creation console command has been removed as it is no longer applicable.
    - The following attribute control parameters have been removed and replaced by accessing them via the new `attributes` object: `inputClass`, `submitClass`, `rowClass`, `columnClass`, `labelClass`, `errorClass`, `instructionsClass`, `class`, `id`, `name`, `method`, and `action`.
- **GraphQL**
    - The `extraPostUrl`, `extraPostTriggerPhrase`, `gtmId`, and `gtmEventName` fields have been removed from `FreeformFormInterface` in GraphQL. Please use the new interface instead (TBD).
    - The `inputAttributes`, `labelAttributes`, `errorAttributes`, and `instructionAttributes` fields have been removed from `FreeformFormInterface` in GraphQL. Please use the `FreeformAttributesInterface` instead.
    - The `hash` field has been removed from `FreeformFieldInterface` in GraphQL as it is no longer relevant.
- **Stripe Payments**
    - The **Stripe Payment** feature has been removed and replaced by all-new support for the newer Stripe Payment Element.

## 4.1.24 - 2025-01-24

### Added
- Verified support for Craft 4.14.x.

### Changes
- Updated the plugin icon.
- Updated some internal dependencies to prevent security warnings.

## 4.1.23 - 2024-11-19

### Added
- Verified support for Craft 4.13.x.

### Changed
- Updated the `phpoffice/phpspreadsheet` dependency to include `^2.0` and `^3.0`.

## 4.1.22 - 2024-09-06

### Added
- Verified support for Craft 4.12.x.

### Fixed
- Fixed a bug where uploading assets to submissions in the control panel would incorrectly mark them as unfinalized assets to be deleted.
- Fixed a bug where an error could occur when creating new forms on some MySQL installs.

### Security
- Updated `axios` dependencies to address potential security vulnerabilities.

## 4.1.21 - 2024-08-14

### Fixed
- Fixed a bug where the `layoutJson` column change migration in 4.1.20 would fail on PostgreSQL.

## 4.1.20 - 2024-07-30

### Changed
- Updated Diagnostics page to verify compatibility for Craft 4.10.x.
- Updated the Website field validation to allow longer domain extensions.
- Changed the `layoutJson` column in the `freeform_forms` database table to `longtext` to handle larger forms.

### Fixed
- Fixed a bug where the **Bypass All Spam Checks for Logged in Users** setting did not correctly bypass all spam checks for logged-in users when a form is cached.

## 4.1.19 - 2024-05-31

### Changed
- Updated Diagnostics page to verify compatibility for PHP 8.2 and Craft 4.9.x.
- Removed the _Resources_ subnav link.

### Fixed
- Fixed a bug where _en_ and _em_ dash characters were not saving in submission data on Radio, Select, and Opinion Scale field types.

## 4.1.18 - 2024-05-08

### Fixed
- Fixed a bug where setting `allowedGraphqlOrigins` to `false` in _general.php_ would cause form submissions to crash.

## 4.1.17 - 2024-04-29

### Added
- Added Craft element search support for submissions. You may need to run `./craft freeform/submissions/resave --update-search-index` once for this to work.
- Added `freeform/submissions/resave` CLI command to resave all existing submissions.

### Changed
- Updated GraphQL to include HTML and Rich Text field types.

### Fixed
- Fixed a bug where setting a Calendar Event to be **All Day** in the Calendar Events integration was not working.
- Fixed a bug where submission saving in the console could fail.

## 4.1.16 - 2024-04-04

### Changed
- Updated the CSRF input to send `nocache` headers when rendering.

### Fixed
- Fixed a bug where Freeform was opening unnecessary sessions.

## 4.1.15.1 - 2024-03-01

### Fixed
- Fixed a bug where Automatic Spam Purge Craft queue jobs would fail when using a database prefix.

## 4.1.15 - 2024-03-01

### Added
- Added support for Automatic Spam Purging in the **Lite** edition of Freeform.

### Changed
- Refactored the Submission Purge functionality to use the Craft queue.

### Fixed
- Fixed a bug where the _Fill Form Values from the GET Query String_ setting was not being respected.

## 4.1.14 - 2024-02-09

### Fixed
- Fixed a bug where Dynamic Recipients fields were not correctly selecting the option chosen when viewing submissions in the CP detail page.
- Fixed a bug where the Field Manager would force `camelCase` without exceptions for field handles.

## 4.1.13 - 2023-11-23

### Changed
- Updated all references to field names in the control panel and exported files to use the default field label as a fallback if the field label is blank in the form settings. To preserve legacy functionality, rendering forms on the front end will continue showing the field label blank.

## 4.1.12.1 - 2023-11-15

### Fixed
- Fixed a bug where some Freeform widgets would error in the Craft dashboard due to a change in the previous version (4.1.12).

## 4.1.12 - 2023-11-09

### Changed
- Updated GraphQL mutations to support multiple email marketing mailing list fields.

### Fixed
- Fixed an issue where submission purge logic was not always working reliably.
- Fixed a bug where form heading Success and Error messages were not being escaped.
- Fixed a bug where removing a field from a form was not automatically removing it from any configured export profiles for that form.

## 4.1.11 2023-10-24

### Changed
- Updated the GraphQL submission mutation to no longer require the custom header.

### Fixed
- Fixed a bug where locales were not working correctly when using more than one Date & Time field in the same form.

## 4.1.10 2023-10-05

### Changed
- Updated File Upload fields to be realigned with Craft's file kind/extensions defaults. Removed the custom Freeform MIME type checks.
- Updated existing feature announcement integrations to be visible to Admins only.

## 4.1.9 - 2023-09-29

### Changed
- Updated the Salesforce integration to allow mapping to encrypted fields in Salesforce.
- Updated the Craft compatibility check in the Diagnostics page to include Craft 4.5.x.

### Fixed
- Fixed a bug where hCaptcha was not working correctly.

## 4.1.8 - 2023-09-15

### Changed
- Updated `stripe/stripe-php` dependency to align with Craft Commerce.
- Updated the Stripe Payments integration to support mapping Phone field types.

## 4.1.7 - 2023-09-08

### Added
- Added support for `.stl` files in the Freeform file helper validation.

### Changed
- Updated the Pipedrive Leads integration to use the new Notes endpoint.

### Fixed
- Fixed a bug where not all Stripe validation errors were accounted for and could cause the form to break.
- Fixed a bug where CRM integration errors were sometimes too long to be logged in Freeform. Updated the column size to resolve this.

## 4.1.6 - 2023-07-20

### Added
- Exposed all remaining form settings/properties for GraphQL queries, including `successMessage` and `errorMessage`.

### Changed
- Refactored the Freeform lock service to use Yii's cache to prevent performance issues.

### Fixed
- Fixed issues with reCAPTCHA when querying forms via GraphQL.

## 4.1.5 - 2023-07-11

### Added
- Added conditional rule logic for form fields in GraphQL.

## 4.1.4 - 2023-07-05

### Added
- Added support for mapping to multiple groups/interests in the Mailchimp integration.

### Fixed
- Fixed a bug where the element query was being executed before Craft was fully initialized.

## 4.1.3 - 2023-06-28

### Fixed
- Fixed a bug where server-side field validation error messages were replaced with a GraphQL query error in production mode.

## 4.1.2 - 2023-06-26

### Fixed
- Fixed a bug where forms using reCAPTCHA v2 Checkbox or hCaptcha Checkbox could behave incorrectly.

## 4.1.1 - 2023-06-22

### Changed
- Updated the Diagnostics page to still show when the `allowAdminChanges` setting is set to `false`.

### Fixed
- Fixed a bug where the Submission Purge console commands were not working correctly.
- Fixed a bug where the Submission Purge feature was not removing associated Asset files as well.
- Fixed a bug where an error would occur when dynamically creating a sub-directory for file uploads upon submission of form.

## 4.1.0 - 2023-06-13

> [!IMPORTANT]
> If currently using GraphQL and/or headless javascript frameworks such as Vue.js, Next.js, React JS, etc, please proceed carefully and test your forms thoroughly after updating.

### Added
- Added support for GraphQL Mutations.
- Added interactive demos for Vue.js, React JS, and Next.js frameworks.

### Changed
- Changed the way reCAPTCHA is handled in headless setups.

### Deprecated
- Deprecated `FormInterface` for GraphQL. Please use `FreeformFormInterface` instead.
- Deprecated `FieldInterface` for GraphQL. Please use `FreeformFieldInterface` instead.
- Deprecated `PageInterface` for GraphQL. Please use `FreeformPageInterface` instead.
- Deprecated `RowInterface` for GraphQL. Please use `FreeformRowInterface` instead.
- Deprecated `OptionsInterface` for GraphQL. Please use `FreeformOptionInterface` instead.
- Deprecated `ScalesInterface` for GraphQL. Please use `FreeformOpinionScaleInterface` instead.
- Deprecated `KeyValueMapInterface` for GraphQL. Please use `FreeformAttributeInterface` instead.
- Deprecated `hash` and `timestamp` in `FreeformHoneypotInterface` for GraphQL. Please do not use.

## 4.0.26 - 2023-06-12

### Fixed
- Fixed a bug that could cause issues when using the JS Honeypot enhancement.
- Fixed a bug where an error would sometimes occur when opening the Diagnostics page.

## 4.0.25 - 2023-06-05

### Fixed
- Fixed a bug where the Field Values Chart widget would error when attempting to add to the dashboard.

## 4.0.24.1 - 2023-05-18

### Fixed
- Fixed a bug where an error would occur when attempting to submit a form that has the "Store Submission Data" setting disabled on sites using the Freeform 4.0.24 version.

## 4.0.24 - 2023-05-16

### Added
- Added the ability to limit forms to be submitted once per email address only.
- Added a setting to the Mailchimp integration to allow choosing between appending existing Contact Tags with new ones when updating an existing contact.

### Fixed
- Fixed a bug where attempting to Allow a spam submission for a Payment form would result in an error.

## 4.0.23 - 2023-04-28

### Added
- Added the ability to map submission data to `postDate` and `expiryDate` for Craft Entries.
- Added the ability to preparse Twig for the "Predefined Assets" setting in email notification templates. This allows for things like dynamically attaching an asset to the email notification based on a user's selection, etc.

### Changed
- Updated Freeform template path settings to now include template folder autosuggestions.

### Fixed
- Fixed a bug where the hidden input for File Upload Drag & Drop fields was not getting an ID attribute applied to it.
- Fixed some minor issues with demo templates.

## 4.0.22 - 2023-04-14

### Added
- Added a "Floating Labels" basic formatting template. Ready-to-go and does not require any frameworks or toolkits.
- Added support for `dwg`, `dxf`, `stp`, `step`, `sia` mime types in Freeform's internal file validation helper.

### Changed
- Overhauled and refreshed demo templates area. Easily try on a wide range of sample formatting templates for your forms, view submission data, check out advanced setups, etc.

### Fixed
- Fixed a bug where the Submission and Spam Purge features were not always working correctly.
- Fixed a few minor styling issues in most of the sample formatting templates.

## 4.0.21 - 2023-03-29

### Fixed
- Fixed a bug where the Honeypot could trigger an unserialization error in some cases.

## 4.0.20 - 2023-03-23

### Changed
- Updated the JS Honeypot Enhancement feature to use the encrypted payload instead of PHP sessions when the 'Form Session Context' setting is 'Encrypted Payload'.
- Various improvements and adjustments to the Basic Light and Dark example formatting template examples.

### Fixed
- Fixed a bug where the Constant Contact integration could timeout when connecting if there were too many lists.

## 4.0.19 - 2023-03-21

### Added
- Added two basic formatting template examples (dark and light modes) that are self-contained and complete to be added to any page. Does not require any frameworks or toolkits.
- Added setup guides directly into API integration settings pages.

### Changed
- Updated the `form_posted` cookie to only be created for users if a form uses a cookie check for limiting how many times a user can submit the form.

## 4.0.18 - 2023-03-10

### Changed
- Addressed some minor Craft 4.4 compatibility issues in the control panel.
- Made some minor adjustments to the CP Submission detail view.
- Improved Dutch (NL) language translations.

### Fixed
- Fixed a bug where the "Email Template" dropdowns in the form builder displayed both Database and File groups even if just one was selected.

## 4.0.17 - 2023-03-01

### Added
- Added support for `.eps` files in the Freeform file helper validation.
- Added support for `.webp` files in the Freeform file helper validation.

### Changed
- Updated English and Dutch (NL) translations to include all missing items.
- Updated default Freeform formatting templates to no longer include `lineHeight` for Stripe fields.
- Updated Signature field data to be included in exports.

### Fixed
- Fixed a bug where many items were not translatable in the Freeform control panel.
- Fixed a bug where using "Database Tables" for session storage context would result in an error when submitting forms.
- Fixed a bug where some migrations and integrations could error when using Guzzle JSON calls.

## 4.0.16 - 2023-02-23

### Fixed
- Fixed a bug where an erroneous database index existed for email marketing integrations that would sometimes cause an error.

## 4.0.15 - 2023-02-14

### Added
- Added an example Conversational style formatting template that displays one field at a time and smoothly scrolls down to the next question until complete.

### Fixed
- Fixed a bug where AJAX errors were not displaying correctly for Opinion Scale and Table fields.

## 4.0.14 - 2023-02-08

### Fixed
- Fixed a bug where unfinalized files were not immediately being cleared upon successful submit of forms that are set not to store submission data.
- Fixed a bug where POST Forwarding was not being triggered when approving submissions in the Spam Folder.
- Fixed a bug where the `initHoneypot` function was triggering error warnings about premature initiation.
- Fixed a bug where Multi-Select fields were missing some styles inside the form builder.

## 4.0.13 - 2023-01-26

### Fixed
- Fixed a bug where single checkboxes were always being checked by default (as of 4.0.11).
- Fixed a bug where custom table attributes were firing on all element types.

## 4.0.12 - 2023-01-25

### Added
- Added additional data to the AJAX submit response payload for multi-page forms.
- Added an example Bootstrap 5 formatting template that includes a preview/review of all fields across all pages.

## 4.0.11 - 2023-01-19

### Fixed
- Fixed a bug where the opt-in data storage checkbox was not saving submission data when checked.
- Fixed a bug where Dynamic Recipient fields would not export with labels when the "Use Option Labels when Exporting" setting was enabled.

## 4.0.10 - 2023-01-17

### Fixed
- Fixed a bug where the "Once per logged in Users only" option for duplicate checking wasn't preventing guests from submitting the form.
- Fixed a bug where extra returns were being inserted above H2s inside Rich Text fields (in the builder) when they were clicked on.
- Fixed a bug where deleting a spam submission from the CP detail view was not working.

## 4.0.9 - 2022-12-09

### Added
- Added back Excel support for exporting submissions.
- Added `submissionLimitReached` to the Form object, allowing you to check if the user has already submitted the form when using the **Limit Form Submission Rate** setting (hide form and/or display an error message to the user instead of waiting until they attempt to submit the form).

### Changed
- Updated to only load reCAPTCHA scripts when form(s) have reCAPTCHA enabled.
- Updated to support Craft's `sameSiteCookieValue`.

### Fixed
- Fixed a bug where the Submissions/Spam CP index includes an "Edit Submission" option that isn't usable.

## 4.0.8 - 2022-11-21

### Changed
- Refactored the submission delete process to use batching.

### Fixed
- Fixed a bug where links to view/edit individual submissions in the CP disappeared in Craft 4.3.2+.
- Fixed a bug where the Freeform Form element field type was not sorting form options alphabetically.
- Fixed a bug where email notifications were sometimes not being fetched when fetched by handle.
- Fixed a bug where two sets of "set status" actions would appear in CP submissions index.
- Fixed a bug where fetching existing tags for the ActiveCampaign integration was not working correctly.

## 4.0.7 - 2022-10-25

### Added
- Verified compatibility with Craft 4.3.

### Fixed
- Fixed a bug where database notification templates were not being loaded properly in the form builder.
- Fixed a bug where a warning was output in the Craft logs when loading dashboard widgets.
- Fixed a bug where the "All Submissions" filter in Submissions and Spam Folder CP indexes would crash when there were more than 60 forms present.

## 4.0.6 - 2022-10-12

### Added
- Added a "Floating Labels" version of the Bootstrap 5 example formatting template.

### Fixed
- Fixed a bug where garbage collection was not working on Freeform submissions.
- Fixed a bug where the "Send Additional Notification" feature was not working correctly.
- Fixed a bug where searching `freeform.submissions` across multiple forms was not working correctly.

## 4.0.5 - 2022-10-05

### Added
- Added success events for AJAX forms with `redirect-to-url` behavior.

### Fixed
- Fixed a bug that would prevent some integrations with OAuth 2.0 from being able to authorize.
- Fixed a bug where the Email Template dropdown select in the form builder would not show all templates if using a mix of database and file templates.
- Fixed a bug where users could create file-based email notification templates in the form builder when the setting for it is disabled.
- Fixed a bug where using the `back` button in multi-page forms could potentially cause issues when using default browser validation.

## 4.0.4 - 2022-09-28

### Added
- Added a setting to disable the creating and editing of File-based email notification templates.

### Changed
- Updated the AJAX response payload to include posted values.

### Fixed
- Fixed a bug where attempting to create a new status would not work.
- Fixed a bug where some field types would not correctly store updates with empty values in multi-page forms.
- Fixed a bug where users without permissions to Freeform could add Freeform widgets to the Craft dashboard.
- Fixed a bug where the `form` reserved keyword was being allowed for field handles.

## 4.0.3 - 2022-09-12

### Changed
- Updated the Google Tag Manager event to include the AJAX response.

### Fixed
- Fixed a race condition issue where loading values when editing an element would sometimes not work.
- Fixed a bug where duplicating forms could cause an error when more than one user group has permission to manage it.
- Fixed a bug where the Tailwind 3 sample formatting template was not including custom input attributes when rendering Select fields.

## 4.0.2 - 2022-08-24

### Fixed
- Fixed a bug where non-latin characters were being allowed in field handles in the field manager area.
- Fixed a bug where predefined assets in email notifications showed a full file path instead of just the filename.
- Fixed a bug where multiple instances of the same form are added when moving the form instance around the DOM.

## 4.0.1 - 2022-08-23

### Added
- Added a v3 version of the Tailwind example formatting template.
- Added a new version of the HubSpot API integration to address new Private App token requirement. This is a required change for any existing HubSpot users.

### Changed
- Updated the Campaign third party plugin email marketing integration to use new `FormsService::createAndSubscribeContact` method.

### Fixed
- Fixed a bug where attempting to sort submissions in the CP submissions index page was not working.
- Fixed a bug where non-latin characters were being allowed in field and form handles.
- Fixed a bug where attempting to view a related Freeform submission element in the slideout in another element could error.
- Fixed a bug where attempting to attach a _Predefined Asset_ to an email notification template would give an Internal server error.

## 4.0.0 - 2022-08-05

> [!WARNING]
> This is a larger and more complex update than usual, and there's a higher chance of a failed update attempt happening. Please ensure you have a recent database backup, and we recommend you test the update on a local/staging environment before updating your production server. Please follow the [Upgrading from Freeform 3.x guide](https://docs.solspace.com/craft/freeform/v4/setup/updating-freeform-3/).

### Added
- Added compatibility with Craft 4.x.
- Added Export Email Notifications feature. Allows you to send exports as email notifications automatically.
- Added more information to email notification error logging. It now includes the email notification approach and the Email field name (if applicable) to track down where the issue is coming from.
- Added setting to have CSV exports use field handles for headings instead of field labels.
- Added support for permanently deleting soft-deleted submissions.
- Added `data-skip-html-reload` form attribute option to bypass HTML reload for AJAX forms (when not using render method).
- Added `EVENT_GET_CUSTOM_PROPERTY` developer event, which lets you inject your own properties on forms to expand their application.
- Added `EVENT_CONFIGURE_CORS` developer event, which lets you modify the CORS headers that will be sent with the request.
- Added a check in the diagnostics, install welcome screen, and settings pages to alert users if the "Freeform Script Insert Type" setting will not work as Static URLs (and needs to be switched to Files).
- Added the ability to map Mailchimp Interests to a form field. Limited to finding and passing a single Interest based on first match within Interest names part of a List.
- Added support for date fields in the Campaign Monitor integration.

### Changed
- Changed all existing forms with the **Success Behavior** setting set to _No Effect_ to now be _Reload Form with Success Message_. This will behave somewhat similarly to old behavior, but will no longer automatically redirect to a different URL upon success. Please review all forms and set the desired behavior for each in the **Success Behavior** setting. If you wish to continue to override the behavior at template level, you can do that as well.
- Changed all sample formatting templates to include a library version number on them and also end with the `.twig` extension (e.g. `foundation.html` is now `foundation-6.twig`). Freeform 4 will automatically migrate all existing forms using sample formatting templates to use the new file names. You shouldn't need to change anything. Where this might become an issue is if you are using the `formattingTemplate: 'template-name.html'` parameter at template level and relying on an older version of the sample template name.
- Changed all sample formatting templates use `|t('freeform')` only (instead of a mix of `|t` and `|t('freeform')`). If you're using static translations on sample formatting templates, you'll need to move `site.php` translations over to `freeform.php`.
- Changed the rendering of single checkboxes to now use the value set inside the form builder. No action should be necessary here. If you have a custom module in place to override this behavior, you can likely undo that now.
- Changed Email fields to no longer store data as an array. The migration will comb through your database and convert all values of Email field types (e.g. `["test@x.x"]` will become `test@x.x`). If you were relying on this feature to collect more than one email address, only the first email address will be kept (e.g. `["a@x.x","b@x.x"]` will become `a@x.x`). No action is necessary here, but if you relied on this functionality, it is a breaking change with no alternative option currently (aside from adding multiple **Email** fields to your forms, one for each email address). If you have a custom module that is working around this in any way, you should be able to disable it now.
- Updated newly created email notification templates' "From" email address and name to default to the newer way (via Project Config), e.g. `craft.app.projectConfig.get('email.fromEmail')`). If you are using the older approach in existing email notification templates, be sure to update them.
- Changed the way form submission data is stored. A new table for each form is now created and updated rather than storing all forms' submission data in a single shared database table. This solves several issues, including running out of fields and paves the way for more exciting future improvements to the form builder. Fields are still global and available to every form. No action should be necessary by the admin, as Freeform includes a migration script that automatically converts the data to be split into multiple database tables. This process may take a little longer if you have a very large site.
- All Freeform fields are now stored as the `TEXT` MySQL type instead of a combination of `TEXT`, `VARCHAR(100)` and `VARCHAR(255)`. This is a change that is applied to all existing fields as well since they are recreated in the migration. No action should be necessary here. In the rare case where your site has some kind of customization that relies on a MySQL type other than `TEXT`, you may have to adjust the database manually.
- Updated Dashboard, Forms listing and Survey & Polls dashboard to hide forms for users that do not have any form or submission access permissions to them.
- Switched over CP scripts to use local copies of external scripts.
- Upgraded the GraphQL interface calls to use the new Craft GraphQL API.
- Updated the "Use Return URL" success behavior to skip reloading the form (briefly) when using AJAX.
- Updated the sample formatting templates to include `ff-fieldtype-FIELDTYPE` classes to field-surrounding divs.
- Updated `league/flysystem`, `symfony/property-access`, `symfony/finder` and `symfony/filesystem` dependency version requirements to resolve some conflicts.

### Fixed
- Fixed a bug where POST Forwarding would still send through spammy submissions.
- Fixed a bug where the form builder tutorial would show an error if the `allowAdminChanges` setting was disabled.
- Fixed a bug where Drag & Drop File Upload fields would not respect all site URL setups.
- Fixed a bug where users with permissions to Create New Forms would encounter issues in the New Form wizard modal.
- Fixed a bug where users without Create New Forms permission would see the New Form button in the CP Forms page.
- Fixed a bug where Number fields with a minimum value above `0` would still allow `0` as a valid value.
- Fixed a bug where an error could sometimes occur on the Form Behavior settings page.
- Fixed a bug with conditional Post Forwarding options check.
- Fixed a bug where the Drag & Drop File Upload field type was requiring a file to be selected even when hidden by the Conditional Rules feature.
- Fixed a bug where the predefined "Yesterday" option for Export Profiles returned yesterday 0:00 until now instead of yesterday 0:00 to 23:59.
- Fixed a bug where the form builder would not show an error message when creating a new notification template if the email notification template directory path was not set.
- Fixed a bug where the Save & Continue Later field was not available in GraphQL schema.
- Fixed a bug where Dynamic Recipient fields would still send email notifications while hidden via Conditional Rules.
- Fixed a bug where uploaded file attachments in email notifications showed a full file path instead of just the filename.
- Fixed a bug where Stripe subscription plan names could possibly show up blank if no price plan description was provided. If so, Freeform will now autogenerate plan names.
- Fixed a bug where the form builder didn't warn that `author` is a reserved handle name.
- Adjusted the NL translation word for `any` in the conditional rules feature.
- Fixed a bug where incorrectly including a twig file in the Success Template Directory path would break settings and form builder.
- Fixed a bug where the Stripe Payments integration could be causing a customer as well as guest account in Stripe for the same transaction.
- Fixed a bug where credit card numbers were not showing up in Stripe's payment methods for customer accounts.

### Security
- Addressed some potential XSS vulnerabilities.

### Removed
- Removed the old Pardot CRM and Constant Contact email marketing API integrations. Please switch to the newer Pardot and Constant Contact integrations if you haven't already, and delete the old legacy ones before upgrading to Freeform 4.
- Removed the `phpoffice/phpspreadsheet` dependency to prevent install conflicts. Excel exporting inside Freeform is temporarily disabled until a new library is implemented.
- Removed the `league/flysystem` dependency as it is not needed.

## 3.13.38 - 2024-09-25

### Fixed
- Fixed a bug where uploading assets to submissions in the control panel would incorrectly mark them as unfinalized assets to be deleted.

## 3.13.37 - 2024-05-08

### Fixed
- Fixed a bug where setting a Calendar Event to be **All Day** in the Calendar Events integration was not working.

## 3.13.36 - 2024-04-12

### Fixed
- Fixed a bug where not all Stripe validation errors were accounted for and could cause the form to break.

## 3.13.35 - 2024-03-01

### Fixed
- Fixed a bug where the _Fill Form Values from the GET Query String_ setting was not being respected.

## 3.13.34 - 2024-02-09

### Fixed
- Fixed a bug where the File Upload Drag & Drop field type could use an incorrect URL if search params are used.

## 3.13.33 - 2023-12-19

### Added
- Added support for additional mimetype file upload validation on `.dwg`, `.dxf`, `.msg`, `.sia`, `.stl`, `.stp`, and `.step`.

## 3.13.32 - 2023-07-24

### Changed
- Refactored the Freeform lock service to use Yii's cache to prevent performance issues.

## 3.13.31 - 2023-07-11

### Added
- Added support for mapping to multiple groups/interests in the Mailchimp integration.

## 3.13.30 - 2023-07-04

### Changed
- Updated the Diagnostics and Craft 4 Preflight pages to still show when the `allowAdminChanges` setting is set to `false`.

### Fixed
- Fixed a bug that could cause issues when using the JS Honeypot enhancement.
- Fixed a bug where an erroneous database index existed for email marketing integrations that would sometimes cause an error.

## 3.13.29 - 2023-05-08

### Changed
- Updated the JS Honeypot Enhancement feature to use the encrypted payload instead of PHP sessions when the 'Form Session Context' setting is 'Encrypted Payload'.

## 3.13.28 - 2023-04-04

### Fixed
- Fixed a bug where some migrations and integrations could error when using Guzzle JSON calls.

## 3.13.27 - 2023-03-23

### Fixed
- Fixed a bug where the Constant Contact integration could timeout when connecting if there were too many lists.

## 3.13.26 - 2023-03-10

### Changed
- Addressed some minor Craft 3.8 compatibility issues in the control panel.
- Improved Dutch (NL) language translations.

## 3.13.25 - 2023-03-01

### Added
- Added support for `.eps` files in the Freeform file helper validation.
- Added support for `.webp` files in the Freeform file helper validation.

### Changed
- Updated the Dutch (NL) translations to include many missing items.

### Fixed
- Fixed a bug where many items were not translatable in the Freeform control panel.
- Fixed a bug where using "Database Tables" for session storage context would result in an error when submitting forms.

## 3.13.24 - 2023-02-14

### Fixed
- Fixed a bug where the Freeform Form element field type was not sorting form options alphabetically.
- Fixed a bug where the `freeform_integrations` table's `accessToken` column was not being set to TEXT on fresh installs.

## 3.13.23 - 2023-02-08

### Fixed
- Fixed a bug where POST Forwarding was not being triggered when approving submissions in the Spam Folder.

## 3.13.22.1 - 2022-12-09

### Fixed
- Fixed a bug where the `sameSiteCookieValue` fix in 3.13.22 was not compatible with PHP 7.2.x.

## 3.13.22 - 2022-12-06

### Changed
- Updated to only load reCAPTCHA scripts when form(s) have reCAPTCHA enabled.
- Updated to support Craft's `sameSiteCookieValue`.

## 3.13.21 - 2022-10-25

### Added
- Added success events for AJAX forms with `redirect-to-url` behavior.

### Fixed
- Fixed an error caused by different Craft version API's.

## 3.13.20 - 2022-09-28

### Changed
- Updated the AJAX response payload to include posted values.

### Fixed
- Fixed a bug where some field types would not correctly store updates with empty values in multi-page forms.
- Fixed a bug where users without permissions to Freeform could add Freeform widgets to the Craft dashboard.
- Fixed a bug where the Diagnostics page could fail on remnants of old plugins.
- Fixed a bug where the `form` reserved keyword was being allowed for field handles.

## 3.13.19 - 2022-09-12

### Changed
- Updated the Google Tag Manager event to include the AJAX response.

### Fixed
- Fixed a race condition issue where loading values when editing an element would sometimes not work.
- Fixed a bug where non-latin characters were allowed in field handles in the field manager area.
- Fixed a bug where fetching existing tags for the ActiveCampaign integration was not working correctly.
- Fixed a bug where duplicating forms could cause an error when more than one user group has permission to manage it.

## 3.13.18 - 2022-08-23

### Added
- Added a new version of the HubSpot API integration to address new Private App token requirement. This is a required change for any existing HubSpot users.

### Fixed
- Fixed a bug where non-latin characters were being allowed in field and form handles.
- Fixed a bug where attempting to view a related Freeform submission element in the slideout in another element could error.
- Fixed a bug where multiple instances of the same form are added when moving the form instance around the DOM.

## 3.13.17 - 2022-08-05

### Fixed
- Fixed a bug where the amCharts library was not using a local copy for its scripts in the CP.
- Fixed a bug where there was a warning about failing to load a source map in the CP.
- Fixed a bug where incorrectly including a twig file in the Success Template Directory path would break settings and form builder.
- Fixed a bug where some upgrades could encounter an error about the Export Notifications database table.
- Fixed a bug where the Stripe Payments integration could be causing a customer as well as guest account in Stripe for the same transaction.
- Fixed a bug where credit card numbers were not showing up in Stripe's payment methods for customer accounts.

### Security
- Addressed some potential XSS vulnerabilities.

## 3.13.16 - 2022-07-25

### Changed
- Switched over CP scripts to use local copies of external scripts.
- Adjusted language and warnings around email notification template storage types on settings, preflight and diagnostics pages.

### Fixed
- Fixed a bug where the Stripe Payments integration could be causing a customer as well as guest account in Stripe for the same transaction.

### Security
- Addressed some potential XSS vulnerabilities.

## 3.13.15 - 2022-07-19

### Added
- Added the ability to map Mailchimp Interests to a form field. Limited to finding and passing a single Interest based on first match within Interest names part of a List.

### Fixed
- Fixed a bug where conditional rules would break when applied to multiple select fields.
- Fixed a bug where some special field types were showing in the field type dropdown for creating new fields in the form builder.
- Fixed a bug where Date fields could sometimes error when editing a Craft Entry via Freeform's element connections feature.
- Fixed a bug where Freeform's mutation observer was not attaching to nested forms.
- Adjusted the NL translation word for `any` in the conditional rules feature.

## 3.13.14 - 2022-06-30

### Changed
- Updated Dashboard and Forms listing to hide forms for users that do not have any form or submission access permissions to them.

### Fixed
- Fixed a bug where Dynamic Recipient fields would still send email notifications while hidden via Conditional Rules.
- Fixed a bug where `craft.freeformPayments.payments` was no longer working. It is deprecated though, so use `craft.freeform.payments` instead.
- Fixed a bug where Stripe subscription plan names could possibly show up blank if no price plan description was provided. If so, Freeform will now autogenerate plan names.
- Fixed a bug where the form builder didn't warn that `author` is a reserved handle name.

## 3.13.13 - 2022-06-15

### Fixed
- Fixed a bug where passing through anonymous calls to the `freeform/api/form` endpoint alias was not working.
- Fixed a bug where the Save & Continue Later field was not available in GraphQL schema.

### Security
- Addressed some potential XSS vulnerabilities.

## 3.13.12 - 2022-06-08

### Added
- Added support for date fields in the Campaign Monitor integration.

### Changed
- Updated the "Use Return URL" success behavior to skip reloading the form (briefly) when using AJAX.

### Fixed
- Fixed a bug where exporting to Excel could fail if a field's value began with `=`.

## 3.13.11 - 2022-06-01

### Added
- Added setting to have CSV and Excel exports use field handles for headings instead of field labels.
- Added `data-skip-html-reload` form attribute option to bypass HTML reload for AJAX forms (when not using render method)

## 3.13.10 - 2022-05-24

### Added
- Added Export Email Notifications feature. Allows you to send exports as email notifications automatically.

### Fixed
- Fixed a bug where the Drag & Drop File Upload field type was requiring a file to be selected even when hidden by the Conditional Rules feature.
- Fixed a bug where the predefined "Yesterday" option for Export Profiles returned yesterday 0:00 until now instead of yesterday 0:00 to 23:59.
- Fixed a bug with conditional Post Forwarding options check.

## 3.13.9 - 2022-04-04

### Fixed
- Fixed a bug where users with permissions to Create New Forms would encounter issues in the New Form wizard modal.
- Fixed a bug where users without Create New Forms permission would see the New Form button in the CP Forms page.
- Fixed a bug where the Formatting Template setting in the New Form wizard modal would not default correctly if custom formatting templates did not exist.
- Fixed a bug where Number fields with a minimum value above `0` would still allow `0` as a valid value.

## 3.13.8 - 2022-04-02

### Changed
- Updated `symfony/property-access`, `symfony/finder` and `symfony/filesystem` dependency version requirements to resolve some conflicts.

### Fixed
- Fixed a bug where Drag & Drop File Upload fields would not respect all site URL setups.
- Fixed a bug where spammy submissions were not storing the author user ID (if available).
- Fixed a bug where exporting submissions would sometimes not work if adding a Table field to a form layout after some submissions already exist.
- Fixed a bug where there was missing support for File Upload Drag & Drop fields mapping to Element Connections and Integrations.
- Fixed a bug where POST Forwarding would still send through spammy submissions.
- Fixed a bug where Checkbox Groups and Multi-Select field types would not show default options in rendered form.
- Fixed a bug where the form builder tutorial would show an error if the `allowAdminChanges` setting was disabled.

## 3.13.7 - 2022-03-28

### Added
- Added a preflight page to check for potential issues with upgrading to Freeform 4 (Craft 4) in the future.

### Fixed
- Fixed a bug where the Diagnostics page could trigger some PHP version-related errors.
- Fixed a bug where CORS handling might still not work under certain circumstances. Also added events for possible customization.
- Fixed a bug where some permission-related migrations could cause issues with Project Config.

## 3.13.6 - 2022-03-16

### Fixed
- Fixed a bug where some older migrations could error when upgrading to Freeform 3.13+.
- Fixed a bug where mass allowing spam submissions would not populate field values in email notifications correctly.
- Fixed a bug where the CORS Origin header was not working correctly when `allowedGraphqlOrigins` enabled.

## 3.13.5.1 - 2022-03-11

### Fixed
- Fixed a bug where an error could display on sites using older versions of PHP.

## 3.13.5 - 2022-03-11

### Fixed
- Fixed a bug where the reCAPTCHA v2 Checkbox field was not displaying in forms in Freeform Lite edition.
- Fixed a bug where hCaptcha would error if the "Send to Spam Folder" setting was enabled.
- Fixed a bug where creating or editing fields inside the Field Manager area could trigger an error in Freeform Pro edition.
- Fixed a bug where the "New Form" wizard modal could sometimes not correctly continue to form when using an irregular admin URL.

## 3.13.4 - 2022-03-04

### Changed
- Updated the Constant Contact email marketing integration to work with the latest API changes. This is an important update for anyone using Constant Contact with their forms.

## 3.13.3 - 2022-03-03

### Changed
- Updated the "New Form" wizard to auto-focus the Form Name field.

### Fixed
- Fixed a bug where the reCAPTCHA v2 Checkbox field was not displaying in forms.

## 3.13.2 - 2022-03-02

### Added
- Added "Send Additional Notification" feature, allowing you to send email notifications of submissions to other email addresses at any point.

## 3.13.1 - 2022-03-01

### Fixed
- Fixed a bug where some special field types were not displaying in forms.
- Fixed a bug where the demo templates submission edit link was not working correctly.

## 3.13.0 - 2022-02-25

### Added
- Added "New Form" wizard that shows the key settings to ease users into creating new forms.
- Added "Success Behavior" feature inside form builder, now capable of allowing you to explicitly specify whether the form should reload the form and display a success message, load a success template, or redirect to a URL. To preserve legacy and avoid breaking existing installs, the default for all existing forms will be "No effect", meaning it continues to behave like Freeform 3.12 and older. We would encourage you to eventually review each form and update to the explicit behavior you actually want for each form (and keep in mind that these can also be overwritten at template level still).
- Added "Success Templates" feature, which allows you to create a message or content of your choosing to take place of the form contents upon successful submit of the form.
- Added the ability to stop new form submissions from happening after a specified date.
- Added the ability to allow only logged in users to submit forms.
- Added the ability to limit forms being submitted more than once by logged in users in the "Limit Form Submission Rate" setting.
- Added user/author data to Freeform submission views and front end templating.
- Added storing of user IDs for any submission made while the user is logged in. Authors can also be assigned to or removed from submissions inside the control panel.
- Added a setting to allow logged in users to bypass all spam protection measures.
- Added bulk spam approval from the Spam Folder index page.
- Added support for additional form types (available as separate add-on plugins).
- Added user/group permission for showing/hiding Quick Export and the "Export as CSV" option in the CP Submissions index page.
- Added field `options` alias for Rating and Opinion Scale field types.

## 3.12.13 - 2022-02-01

### Added
- Added a setting to allow global control of whether exporting submissions uses the field option labels or option values.

### Fixed
- Fixed a bug where Export Profiles were not correctly factoring in timezones when using date ranges on exports.
- Fixed a bug where Drag & Drop File Upload fields were not working correctly with IE11.

## 3.12.12.1 - 2022-01-26

### Fixed
- Fixed a bug where the default rendering of Rating fields would right-align the stars instead of left-align.

## 3.12.12 - 2022-01-21

### Added
- Added support for HEIC and HEIF image file types in Freeform's mime validation layer.

### Fixed
- Fixed a bug where fields with default values specified in the form builder would not show the default value/option on multi-page forms after the first page.
- Fixed a bug where self-deletion feature (`deleteSubmissionByToken`) was only working for admins.
- Fixed a bug where the Diagnostics page would error if there are plugins/modules that don't have the `enabled` property present.

## 3.12.11 - 2021-12-23

### Added
- Added a setting for Date & Time fields in the form builder that lets you set/force a locale for the field.

### Changed
- Updated Stripe validation errors to also respect the "Automatically Scroll to top of the Form on AJAX submit" setting.

### Fixed
- Fixed a bug where the custom form properties feature wasn't working correctly.

## 3.12.10 - 2021-12-20

### Added
- Added the GraphQL `allowedGraphqlOrigins` config setting for CORS support in Freeform.

### Changed
- Updated form re-rendering (via AJAX) to be bypassed if no formatting template is specified in the form builder.

### Fixed
- Fixed a bug where spammy submissions with hidden mailing list fields were not sending off to Email Marketing integrations when approved.
- Fixed a bug where Webhooks were still firing when a submission was flagged as spam.
- Fixed a bug where File Upload Drag & Drop fields were not showing as options in Conditional Rules tab.
- Fixed a bug where deleting an uploaded file could clear the submission data.
- Fixed a bug where blocked email and keyword error messages were not being displayed underneath applicable fields when the corresponding settings are enabled.

## 3.12.9.1 - 2021-12-07

### Fixed
- Fixed a bug where new installs could potentially error on the `league/flysystem` dependency version.

## 3.12.9 - 2021-12-07

### Added
- Added support for hCaptcha, which works similar to reCAPTCHA but easily complies with GDPR and other global data laws.

### Fixed
- Fixed a bug where the Pardot integration was not correctly passing off array values.
- Fixed a bug where it was not possible to submit forms directly to the Submit controller.
- Fixed a bug where Opinion Scale fields were not defaulting to using values as labels if labels are empty.

## 3.12.8 - 2021-12-02

### Changed
- Updated Freeform CSS to not load in templates where forms don't contain the Drag & Drop File Upload or Opinion Scale field types.
- Updated all Freeform CSS to be minified.

### Fixed
- Fixed a bug where multi-page forms with single checkbox field types would always assume checkboxes were checked.
- Fixed a bug where instructions for single checkbox fields were not being displayed in the Bootstrap demo template as well as the Bootstrap 4, Bootstrap 5 and Tailwind sample formatting templates.

## 3.12.7 - 2021-11-29

### Added
- Added the ability to map the Freeform submission ID, submission token, submission title, form ID, form handle and form name to other element fields via the Element Connections feature.

### Fixed
- Fixed a bug where File Upload Drag & Drop image previews would not use reduced thumbnail files when editing submissions or going back/forth in multipage forms.
- Fixed a bug where the Resend Email Notifications feature was not working correctly.
- Improved the way form sessions are used.
- Fixed a bug where sessions would not timeout (if set to expire early) for PHP Sessions and Database Table contexts.
- Removed info log entry for the SharpSpring CRM integration.

## 3.12.6 - 2021-11-23

### Fixed
- Fixed a bug where hidden Mailing List fields were not working properly.
- Fixed a bug where Payment forms were showing a success message to the user even if the card was declined.

## 3.12.5 - 2021-11-19

### Fixed
- Added backwards compatibility for forms without submit buttons with `data-freeform-action`.
- Added a more resilient check on posted form hash parts for Sentry ([#201](https://github.com/solspace/craft-freeform/issues/201#issuecomment-973878911)).
- Fixed a bug where the `lang="en"` attribute was being added to Number fields.
- Fixed a bug where submit buttons were not being aligned correctly in the new sample Bootstrap 5 formatting template.

## 3.12.4 - 2021-11-17

> [!IMPORTANT]
> Freeform 3.12+ introduces additional Form Session Context options for processing forms. For sites that have already upgraded to 3.12.0 - 3.12.3, we recommend switching the Freeform Session Context setting to "Encrypted Payload" instead of "PHP Sessions" and carefully reviewing your forms. If you prefer to continue using PHP Sessions and are caching your forms, you will need to add `{% do form.registerContext %}` below your form when loading the form in a cached template.

### Changed
- Updated default Form Session Context to "Encrypted Payload" instead of PHP Sessions. If you previously updated to Freeform 3.12 and/or prefer to continue to use PHP Sessions and are caching your forms, you will need to add `{% do form.registerContext %}` below your form when loading the form in a cached template.

## 3.12.3 - 2021-11-17

### Fixed
- Added backwards compatibility with `freeform/api/form` controller action.
- Fixed a bug where Invisible fields were no longer collecting data.
- Fixed a bug where the Encrypted Payload session context was not being included with `form.json` function.
- Fixed a bug where any keystroke would reset field option values when creating fields in the Field Manager area of the control panel.

## 3.12.2 - 2021-11-15

### Fixed
- Fixed a bug where Dynamic Notifications (template parameter) were not working.
- Fixed an error caused by querying submissions in a console request.
- Fixed a bug where queue jobs could fail on an undefined request method.
- Fixed a bug where incomplete craft plugin info could cause the Freeform Diagnostics page to not display.

## 3.12.1 - 2021-11-11

### Fixed
- Fixed a bug where element editing was not correctly mapping Lightswitch and Checkbox fields as well as Relation fields that mapped to single option field types.
- Fixed a bug where some of the Welcome install wizard's new 3.12 settings weren't saving correctly.

## 3.12.0 - 2021-11-10

> [!IMPORTANT]
> Freeform 3.12+ introduces additional Form Session Context options for processing forms. The default Form Session Context will be switched to "Encrypted Payload" instead of "PHP Sessions" upon upgrade. If you prefer to continue to use PHP Sessions and are caching your forms, you will need to add `{% do form.registerContext %}` below your form when loading the form in a cached template.

### Added
- Added a Drag & Drop File Upload field type (Pro).
- Added "Save & Continue Later" feature, which allows users to save their form progress and return later to complete the form.
- Added self-diagnostics page to help troubleshoot issues and identify potential issues.
- Added a Bootstrap 5 example formatting template.
- Added 'Extras' section to demo template to show additional common uses with forms.
- Added the ability to limit how many submissions a form can have.
- Added a setting to automatically scroll to the top of the Form on AJAX submits. This is especially beneficial when you have longer forms and success/error messages at the top of the form become out of sight.
- Added ability to disable the Freeform Honeypot per form at template level.
- Added ability to set payload forwarding to form render params.
- Added support for blocking phrases in spam keyword blocking feature (e.g. "generate new leads").
- Added a `freeform/fields/create` console command to allow creation of new fields in CLI.
- Added a developer event for registering the form context for cached forms.

### Changed
- Overhauled form processing to allow for alternative session storage options including the new Encrypted Payload default as well as a database table approach.
- Updated sample formatting templates to account for "Save & Continue Later" buttons.
- Updated the custom formatting templates and email notification templates lists to be ordered alphabetically in the form builder.
- Updated field option values to automatically appear as camelCase instead of an exact clone of what is typed as field option label.
- Updated newly created Text, Hidden, Invisible, Phone, Regex, and Website field types from `VARCHAR` to `TEXT` in database. Changed all other newly created field types to be `VARCHAR(255)` instead of `VARCHAR(100)`. This is not a retroactive change - it will affect the creation of new fields only.
- Updated the CP Field Manager page to sort fields alphabetically.
- Updated to the Honeypot field to include `autocomplete="off"`.
- Updated the `submitClass` parameter to control styling of "Save & Continue Later" buttons in addition to regular Submit buttons.
- Updated translation files to contain some missing field validation strings.
- Adjusted all references of "Mailing List" integrations to "Email Marketing".
- Moved form behavior-related settings out of General Settings and into a new Form Behavior settings page.
- Refactored edit submissions and edit element logic.

### Fixed
- Fixed a bug where the auto-scroll anchor inserted in Freeform forms could sometimes impact form styling. Added a `display:none;` inline CSS to the `div`.
- Fixed a bug where the 'overrideValues' parameter does not apply to fields on pages after first page when AJAX is enabled.
- FIxed a bug where the "loading" indicators for the submit button were not working in Safari.
- Fixed a bug where spam reasons in the submission view page would show up as many times as there are pages when a multi-page form triggered spam blocking protection.
- Fixed a bug where attempting to sort on Spam Reasons in the Spam folder would trigger errors. Sorting by Spam Reasons is disabled for now.
- Fixed a bug where submitting an AJAX form a second time (whole new second submission) would always fail the spam test when the "Minimum Submit Time" setting was enabled.
- Fixed a bug where Freeform would not display an error if the database table reached its limit.
- Fixed a bug where the display of the page tabs inside the CP Submission detail pages were not compatible with Craft 3.7+.
- Fixed a bug where the 3.11.0 permissions migration can trigger an error on the Craft Solo edition.
- Fixed a bug where the Constant Contact integration would routinely trigger an authentication error in Freeform log.

## 3.11.13.1 - 2021-11-10

### Fixed
- Fixed a bug where multi-option field types would not display their selected options in edit mode.

## 3.11.13 - 2021-11-09

### Fixed
- Fixed a bug where Dynamic Recipients could run into performance issues when the options are populated by many Elements.
- Fixed a bug where User accounts would not be created if the User Element Connection settings don't have any user groups checked.

## 3.11.12 - 2021-10-29

### Added
- Added support for 'epub' mime type in file validation.
- Added `implements \Countable` to the `Form` object to support Twig loop variables.
- Added field definitions in TypeManager::prepareFieldDefinitions in GraphQL.

### Fixed
- Fixed a bug where Dynamic Recipients fields would not populate their existing value when editing submission on the front end.
- Fixed a bug where the Constant Contact integration would incorrectly log failed connection checks.
- Fixed a bug where entry reindexing would sometimes fail on cookie set.

## 3.11.11.1 - 2021-09-24

### Fixed
- Fixed a bug where the SharpSpring CRM integration wasn't working fully with custom fields.

## 3.11.11 - 2021-09-15

### Changed
- Updated the SharpSpring CRM integration to work with the latest API changes.

### Fixed
- Fixed a bug where Freeform could sometimes log an error for successful Stripe payments. 
- Fixed a potential issue with Stripe subscription payments.
- Fixed a bug where multi-page forms were not clearing visual errors correctly (after correction) when using with AJAX.

## 3.11.10 - 2021-07-22

### Fixed
- Fixed a bug where sites using GMP and Stripe could run into issues when payment amounts included decimals/cents.
- Fixed a bug where multipage Stripe Payment forms could not go back to the previous page unless credit card details were filled out.

## 3.11.9 - 2021-07-01

### Added
- Added ability to display the Stripe credit card icon on Credit Card fields (with `event.showCardIcon = true` for the `freeform-stripe-styling` event).

### Fixed
- Fixed a potential performance issue with the Freeform services check (e.g. Update Notices, Purge Submissions, etc) by adding a `dateCreated` index for the `freeform_lock` table.
- Fixed a bug where the new Pardot v5 integration could trigger a 400 error when posting data.
- Fixed a bug where using the `overrideValues` parameter to set a value on a number field would not correctly handle decimals in some cases.
- Fixed a bug where the "spam reasons" feature database table migration could error in some cases.

## 3.11.8 - 2021-06-23

### Fixed
- Fixed a bug where searching on submissions inside the Freeform CP could still sometimes trigger an error.
- Fixed a bug where forms were not being correctly parsed within other element types when using GraphQL.

## 3.11.7 - 2021-06-21

### Added
- Added an updated OAuth version of the Pardot integration to be compatible with the new API. Recommended replacement to anyone currently using the old Pardot integration.
- Added support for custom date fields in the HubSpot integration.

### Fixed
- Fixed a bug where searching on submissions inside the Freeform CP could sometimes trigger an error.
- Fixed a bug where entering an invalid path to the template folders that contain Formatting Templates and Email Templates could sometimes cause the settings page to crash.

## 3.11.6 - 2021-06-10

### Fixed
- Fixed a bug where adding contents to a Table field inside the form builder wouldn't work if you clicked the Table field in the list to add it to the layout.
- Fixed a bug where the Purge Submissions feature was not removing the File Upload field type's actual file from the server.
- Adjusted Hidden fields to no longer have a forced max length of 250 as a temporary workaround for those that have switched the field to TEXT in the database to collect longer data.

## 3.11.5 - 2021-06-03

### Added
- Added support for mapping to custom fields and more standard fields in the Constant Contact integration.

### Fixed
- Fixed a bug where the form builder would still showing fields added to the layout (rather than removing them from the list).
- Fixed a bug where the Zoho authentication check was partially flawed.
- Fixed a bug where GraphQL form fields were not resolving the selected form.

## 3.11.4.1 - 2021-05-12

### Fixed
- Fixed a bug where the Stripe Payments integration could error if the BC Math PHP extension is not enabled on the server. Added a fallback for GMP and basic multiplication for calculating amounts.

## 3.11.4 - 2021-05-06

### Fixed
- Fixed a bug where some amounts could add 1 cent to the amount sent off to Stripe in the Payments integration.
- Fixed a bug where the Status sort order select was not working in Postgres.

## 3.11.3 - 2021-04-29

### Added
- Added a post validation check for form errors.

### Fixed
- Fixed a bug where the 3.11.0 user group permission migrations would not work correctly on setups with `allowAdminChanges` disabled.

## 3.11.2 - 2021-04-28

### Added
- Added a new 'Freeform Script Insert Type' setting so you can now choose whether you want Freeform scripts loaded as static URLs, files, or inline.

### Fixed
- Fixed an issue where sites with server rules applied to JS and CSS files could cause Freeform forms to no longer submit correctly.
- Fixed a bug where the 'Previous' button was not correctly going backwards in Safari.

## 3.11.1 - 2021-04-22

### Fixed
- Fixed an issue with submissions and element indexing requests.

## 3.11.0 - 2021-04-20

> [!IMPORTANT]
> Freeform 3.11+ introduces changes to how its front-end scripts are inserted into pages (as static URLs). If you have server rules applied to JS and CSS files, you may run into issues. If this is the case, you can switch the behavior back to previous approaches ('As Files' introduced in 3.10 or 'Inline' as it worked previously before 3.10+). This is a new setting available in the General Settings of Freeform or with Project Config as `scriptInsertType: files`.

### Added
- Added the ability to edit other Craft Elements, including special support for Craft Users.
- Added the ability to map to Calendar Events with the Element Connections feature.
- Added a full screen HTML & Twig and Rich Text editor inside the form builder.
- Added the ability to update file-based email notification templates directly inside the CP (optional).
- Added a migration tool for migrating from Database to File-based email notifications.
- Added support for searching by submissions' field values in the CP Submissions index.
- Added a toggle for showing field handles in the Conditional Rules feature selectors.
- Added GraphQL support to the Forms fieldtype.
- Added support for Freeform to track page movement in multi-page forms, allowing for robust movement when using the "Previous" button and Conditional Rules page skipping logic.
- Added more granular submission viewing and management permissions. You can now set read-only permissions in addition to management permissions.
- Added a setting that allows you to optionally restrict the Forms element field type to only show forms which the user has manage permissions for.
- Added ability to load reCAPTCHA scripts (and checkbox field if using v2 Checkbox) only once the site visitor interacts with the form.
- Added JSON export of a form instance for frontend frameworks (`freeform.form('myForm').json`).
- Added export profile developer events, allowing the addition of custom exporters.
- Added a developer event for modifying the AJAX response payload.

### Changed
- Reworked the way Freeform's JS and CSS are loaded by the plugin in front end templates. Developers can now also manually load the Freeform JS in templates with `<script src="/freeform/plugin.js"></script>` and the CSS (currently only applicable to the Opinion field type) with `<link rel="stylesheet" href="/freeform/plugin.css">`.
- Updated Freeform's JS to no longer fetch field specific scripts if the fields aren't present in the form.
- Updated Freeform's JS to no longer include excess polyfills.
- Changed the automatic scroll anchor tag from `<a>` to `<div>` (when reloading the page for errors or loading the next page on non-AJAX forms).
- Updated exporting to no longer export submissions flagged as spam (unless you're exporting directly from the Spam folder).
- Updated the "Install Demo Templates" banner to be hidden automatically if the `allowAdminChanges` Craft config setting is enabled.

### Fixed
- Fixed a bug where searching on the CP Submissions index could sometimes trigger an error a column was missing.
- Fixed a bug where Freeform would log a "Slug cannot be blank" error in the Freeform logs when a form is not fully submitted (triggers error/reloads form) with Element Connections.
- Fixed a bug where changing the status of submissions from the CP Submissions index page would not work for user groups with the "Manage All Submissions" permission.
- Fixed a bug where the 'OAuth Return URI' field would clear its value when creating a new integration, making it difficult to copy/paste during set up.
- Fixed a bug where the Stripe Payments integration was not sending emails for charge success and failure events.

## 3.10.11 - 2021-03-25

### Changed
- Updated the `egulias/email-validator` dependency version requirements to include `3.x` for compatibility with other plugins using it.

### Fixed
- Fixed a bug where changing the status of submissions from the CP Submissions index page would not work for user groups with the "Manage All Submissions" permission.
- Fixed a bug where the Stripe Payments integration was not sending emails for charge success and failure events.
- Fixed a bug where exporting submissions as XML could sometimes fail if it contained empty array field data.
- Fixed a bug where the Freeform JS plugin `init` included automatic submit disabling that could potentially interfere with some site setups.

## 3.10.10 - 2021-03-15

### Added
- Added a toggle for showing field handles in the Conditional Rules feature selectors.

### Changed
- Changed the automatic scroll anchor tag from `<a>` to `<div>` (when reloading the page for errors or loading the next page on non-AJAX forms).

### Fixed
- Fixed a bug where a source map loading error would occur in the browser console when loading Freeform forms.

## 3.10.9 - 2021-03-05

### Added
- Added a setting to disable Search Index updating after each submission of a Freeform form.
- Added developer events for manipulating Table fields.

### Fixed
- Fixed a bug where session data could sometimes not be written when no custom attributes were passed.
- Fixed a bug where empty required File Upload fields could sometimes not show an error/halt the form.
- Fixed a bug where creating new fields in the Field Manager would not show a clear error about a field handle already existing.
- Fixed a bug where an error could sometimes be incorrectly logged for the Mailchimp integration.

## 3.10.8 - 2021-02-16

### Added
- Added ability to include Twig in the Admin Recipients email textarea in the form builder (for more complex conditional notifications).
- Added the possibility to re-subscribe people to Mailchimp.
- Added customization options for the form auto-scroll feature (to account for floating navigation, etc).

### Fixed
- Fixed a bug where reCAPTCHA v2 Invisible would sometimes conflict with other scripts on the page and not work correctly.
- Fixed a bug where the Payments subscription cancelation URL was not correct.

## 3.10.7 - 2021-02-08

### Added
- Added ability to include Payment info when exporting submission data.

### Fixed
- Fixed a bug where Purge Submissions jobs would fail due to an issue with Guzzle 7.x on Craft 3.6.x.
- Fixed a bug where the datepicker JS would load on all Date/Time fields regardless of their setting for using the built-in datepicker JS.

## 3.10.6 - 2021-02-04

### Fixed
- Fixed a bug where non-AJAX forms with an empty file upload field would trigger an error.

## 3.10.5 - 2021-02-02

### Added
- Added support for Birthday field types in the Mailchimp mailing list integration.

### Fixed
- Fixed a bug where adding the sample formatting template through the CP was not working correctly.
- Fixed a bug where the "Conditional Rules" editor in CP form builder would run off the screen on smaller screens.
- Fixed a bug where File Upload fields might log errors in some rare cases.
- Fixed an issue where the "What's New" dashboard feature could skip some updates that might be relevant in the future.

## 3.10.4 - 2021-01-26

### Added
- Added a way to conveniently get a reCAPTCHA v2 Checkbox field instance from a manually constructed form with `form.get('recaptcha')`.

## 3.10.3 - 2021-01-22

### Fixed
- Fixed a bug in the Export Profiles migration that prevented updating profiles with no date ranges set.

## 3.10.2.1 - 2021-01-18

### Fixed
- Fixed a bug where front end forms could display an error when loading in some rare cases.

## 3.10.2 - 2021-01-15

### Added
- Added developer events for modifying included Freeform script tags and honeypot.

### Changed
- Updated HTML block fields in to no longer render Twig code inside the CP submissions view to prevent potential issues.

### Fixed
- Fixed a bug where Freeform was incompatible with Craft 3.4.x in some places and could error.

## 3.10.1 - 2021-01-14

### Fixed
- Fixed a bug where Freeform CP Widgets with charts were not loading the chart library.
- Fixed a bug where creating new elements would not automatically update the Craft search indexes.
- Fixed a bug where the Form Builder HTML editor would go outside of viewing area on smaller screens.
- Fixed a bug where there were no valid mime-types for empty text file uploads.
- Fixed a potential issue with Pardot accounts that don't have custom fields.

## 3.10.0 - 2021-01-05

### Added
- Added support for GraphQL.
- Added support for Google Tag Manager with AJAX.
- Added the ability to specify a custom Payment description inside the form builder to show inside Stripe payments.
- Added ability to specify a Text-only version of email notifications when using the template file approach (`{# text #}{# /text #}`).
- Added the ability to render Twig in form layout HTML blocks, allowing for things like having users review entered data from previous pages, etc.
- Added the ability to duplicate Email Notification templates.
- Added the ability to delete all submissions at once (including paginated results) for a form.
- Added a client-friendly stats-only Digest email option (in addition to the 'developer' one with alerts and notices).
- Added the ability to choose between weekly or daily Digest email notifications.
- Added the ability to choose which day of the week that the weekly digest is sent on.
- Added a setting which lets you send Weekly Digest email notifications only on production environments (looking for 'production' environment name).
- Added a post-install welcome / settings wizard to streamline and guide setting up new installs of Freeform.
- Added the 'step' property setting to Number fields in form builder.
- Added a built-in feedback widget to show only during beta releases for ease of reporting beta issues.
- Added support for the Multi-select Picklist field type in the Zoho API integrations. ([#72](https://github.com/solspace/craft-freeform/pull/72))

### Changed
- Updated AJAX to work with multipage forms.
- Updated Payments to work with multipage forms.
- Overhauled the Freeform Javascript plugin to be more robust, reworked the API to make it way more developer friendly. Made it easier for anyone to adjust or complement form functionality regardless of their particular site setup.
- Updated Freeform to automatically generate Text-only versions when no Text-only version is specified in the email notification template.
- Updated reCAPTCHA error logging to include better descriptions for failures.
- Updated references to 'Composer' (Freeform's form builder) to 'Form Builder'.
- Updated the settings page to have a new category for form builder-specific settings.
- Updated email notification templates to automatically trim stray spaces for email fields.
- Updated the Digest email notifications to account for new intervals, and updated the language of subject lines.
- Updated the Weekly Digest and Email Alert email notifications to reference the custom plugin name (when specified) instead of 'Freeform' (Pro).
- Updated the Weekly Digest email notifications' form names to link to submissions pages instead of form edit view.
- Updated the CP Forms index to order forms alphabetically again (vs using the custom order specified on the Dashboard).
- Updated the 'Disable Submit Button on Form Submit' setting to be on by default for new installs.

### Fixed
- Fixed a bug where uploaded asset ID's weren't being set as the File Upload field value after upload.
- Fixed autocomplete not being turned off on datepicker enabled fields.
- Fixed a bug where the Active Campaign mailing list integration was not pulling in all mailing lists above 100.
- Fixed a bug where the Mailchimp mailing list integration was not properly detecting duplicates when passing non-lowercase emails.
- Fixed a bug where the Weekly Digest and Email Alert email notifications were not respecting the "testToEmailAddress" config setting.
- Fixed a bug where textareas inside the CP Submissions detail view were unnecessarily escaping data.
- Fixed a bug where Signature fields were redrawing incorrectly on high DPI displays.

## 3.9.11 - 2020-12-17

### Fixed
- Fixed a bug where the Active Campaign mailing list integration was not pulling in all mailing lists above 100.

## 3.9.10 - 2020-12-16

### Added
- Added support for the Multi-select Picklist field type in the Zoho API integrations. ([#72](https://github.com/solspace/craft-freeform/pull/72))

### Fixed
- Fixed a bug where textareas inside the CP Submissions detail view were unnecessarily escaping data.
- Fixed a potential issue with the 3.9 migration that might affect a small number of customers.

## 3.9.9 - 2020-12-02

### Fixed
- Fixed a bug where older installs of Craft (3.5.9 and lower) could experience an error in the Forms and Fields CP index pages due to the `truncate` filter not existing yet.

## 3.9.8 - 2020-12-01

### Fixed
- Fixed a bug where the Dashboard submissions chart would include spam submissions.
- Fixed a bug where the Dashboard submissions chart would not display correctly in Firefox.
- Fixed a bug where the Active Campaign mailing list API integration was limited to fetching 50 mailing lists.
- Fixed a bug where the Email Notifications CP page was not displaying the file name for file-based email notification templates.

## 3.9.7 - 2020-11-26

### Fixed
- Fixed a bug where reCAPTCHA was not working correctly with IE 11.

## 3.9.6 - 2020-11-20

### Fixed
- Fixed a bug where the Forms CP pages would error on sites using PHP 7.0.

## 3.9.5 - 2020-11-19

### Fixed
- Fixed a bug where the 'Previous' button was not working correctly in multi-page forms.
- Fixed a bug where duplicating forms from the Forms CP index page was not working correctly.
- Fixed a bug where duplicating forms was not retaining the user group permissions for Freeform.
- Fixed a bug where the Weekly Digest email subject was showing a less useful date and used site name instead of system name.
- Fixed a bug where the `fieldIdPrefix` parameter was not adding the prefix to the `for` attribute in the Honeypot field label.
- Fixed a bug where users without permission to Export Profiles could still see the 'Export' button in the dashboard.
- Fixed a bug where an empty tooltip would display when clicking the info icon for Update Notices and What's New features.
- Fixed a bug where fields and forms with very long handle values could force other parts of their respective CP index pages out of view.

## 3.9.4 - 2020-11-13

### Added
- Added the submission token to the AJAX response when submitting forms.

### Fixed
- Fixed a bug where Dynamic Recipients fields were not showing the new Commerce Products source option.
- Fixed a bug where dismissing Update and What's New notices was not working on Craft versions below 3.5.0.

## 3.9.3 - 2020-11-12

### Fixed
- Fixed a bug where the 'getTestToEmailAddress' addition to Freeform would error on Craft versions below 3.5.0.

## 3.9.2 - 2020-11-11

### Added
- Added support for mapping to Contact Tags in the ActiveCampaign Mailing List API integration.
- Added a setting to Payments to disable passing of an email address to the Stripe `receipt_email`, which causes Stripe to automatically send their own email notification to the customer.

### Fixed
- Fixed a bug where the default Formatting Templates might show an extra success banner under certain circumstances.
- Fixed a bug where the loading indicator feature was not working correctly on Payment forms.
- Fixed a bug where declined or high risk Subscription payments were not correctly erroring on the front end.
- Fixed a bug where some field errors were not being escaped.

## 3.9.1 - 2020-11-06

### Added
- Added support for the 'testToEmailAddress' Craft config setting.

### Fixed
- Fixed a bug where sites using PostgreSQL would get an error when updating.
- Fixed a bug where the Dashboard submissions chart was not considering current timezone.

## 3.9.0 - 2020-11-05

### Added
- Added an 'Important Update Notices' area in the Dashboard that keeps you informed about issues that may specifically affect your site.
- Added a 'What's New' area in the Dashboard that lets you know about new features available in the current version you've just updated to.
- Added a 'Weekly Digest' email notification that will keep you in the loop about your website's Freeform form performance and status. It includes a snapshot of the previous week's performance and any logged errors and upgrade notices.
- Added the ability to designate general success and error messages for each form inside the form builder.
- Added settings in form builder for the ability to set and define 'Loading...' indicators on your submit buttons in forms.
- Added setting to display a plugin badge for Freeform navigation menu. Choose whether to show the error/notice count, spam submission count, submission count when enabled.
- Added ability to populate multi-option field types with Commerce Products.
- Added options to set the reCAPTCHA 'Theme' (light/dark), 'Size' (normal/compact) and custom 'Error Message'.
- Added ability to disable reCAPTCHA for forms at template level (use `disableRecaptcha: true`).
- Added optional 'Reply-to Name' field for email notification templates to apply a name to the Reply-to email address.
- Added `$form->hasFieldType(string $type);` better field type presence checking in forms.

### Changed
- Redesigned the Dashboard to be cleaner and now include a built-in notices and warnings area to provide you with more peace of mind as it keeps you informed about issues and new features that may specifically affect your site.
- Updated Export Profiles to have more flexible date range options.
- Updated demo templates and sample formatting templates to account for general success and error messages, as well as 'Loading' indicator and text if set inside the form builder.
- Updated Signature images to be added as an attachment in email notifications if the 'Include Attachments' setting is enabled.
- Updated Stripe payment data to have the ability to include the form handle in metadata.
- Updated Payments behavior to no longer trigger email notifications, Element Connections, and API integrations when the payment is declined.
- Updated the built-in Spam Folder to be enabled by default on new installs.
- Updated Spam Folder individual submission view to include a 'Delete' button, and to have both the 'Delete' and 'Approve' buttons return you to the Spam Folder page for a more intuitive flow.
- Updated reCAPTCHA v3 default error message to be more applicable to the context.
- Updated the Pardot integration to include mapping to many other Pardot fields.

### Deprecated
- Deprecated the 'Reload form' option for the 'Spam Protection Behavior' setting. The Freeform settings interface will no longer show this option and we recommend you choose a different option. In the meantime, you can continue to use this setting by manually specifying it in your Project Config file.

### Fixed
- Fixed a bug where mapping the Stage inside field mapping would not override the Stage set inside the ActiveCampaign CRM integration settings.
- Fixed a bug where AJAX submissions were not being reset in the form if editing a submission on front end.
- Fixed a bug where Craft Solo users might notice an error when creating new forms.
- Fixed a bug where very wide Table fields with many columns would overflow past the field layout in CP Submissions detail view.
- Fixed a bug where the property editor column in the Composer form builder was clipping the top few pixels of text.

## 3.8.4 - 2020-10-07

### Fixed
- Fixed a bug where Freeform jobs would be created upon console request.

## 3.8.3 - 2020-10-05

### Fixed
- Fixed a potential security vulnerability.

## 3.8.2 - 2020-09-25

### Changed
- Updated all API integration settings to have the ability to use environment variables.

### Fixed
- Fixed a bug where Signature fields were not showing up in the Conditional Rules feature.
- Fixed a bug where editing submissions on the front end could be unreliable in rare cases, causing duplicate submissions to be created.
- Fixed a bug where having different statuses set for multiple instances of the same form in the same template would not work correctly.
- Fixed a bug where the Freeform Craft 3 migration could error when trying to migrate from very old versions of Freeform 1.x.
- Fixed a bug where older legacy data from Dynamic Recipients fields might not show up correctly in exports.
- Fixed a bug where Freeform status color styles might not show up correctly in edge cases.

## 3.8.1 - 2020-09-09

### Fixed
- Fixed a bug where the Submissions Purge and Unfinalized Assets Purge jobs were not taking into account timezones, and could also sometimes get stuck in pending status.
- Fixed a bug where selecting "Allowed File Kinds" checkboxes in File Upload fields were no longer working.
- Fixed a bug where the Craft Campaign integration was not performing a duplicate contact check correctly and also not taking into account double opt-in settings.

## 3.8.0 - 2020-08-31

### Added
- Added an Email Alert feature! It allows you to automatically send an email notification alerting the email address(es) specified when an email notification cannot be delivered. Very helpful for catching some website/email issues early.
- Added Pipedrive Leads integration (previous Pipedrive integration is now named Pipedrive Deals).
- Added ability to set a formatting template for a form at template level (e.g. `formattingTemplate: 'template-name.html'`).
- Added Purge console commands for manually purging submissions, spam folder submissions and unfinalized Assets.

### Changed
- Updated reCAPTCHA to now work robustly with multi-page forms. Previously, you could not click the 'Previous' page button if reCAPTCHA v2 Invisible or v3 were enabled.
- Updated the Conditional Rules feature to no longer validate and store data of fields that are hidden by the feature.

### Fixed
- Fixed a bug where the Submissions Purge and Spam Folder Purge features were not reliably working. Switched them to now use Craft jobs. Added more time interval options as well.
- Fixed a bug where API integrations in the dropdown list were not being sorted alphabetically.

## 3.7.5 - 2020-08-27

### Fixed
- Fixed a bug where data of the chosen option of a field being populated with Craft Entries or other elements was no longer being included in email notifications and the Submission object in templates.
- Fixed a bug where selecting a user group option in the User Element Connection feature was no longer working.

## 3.7.4 - 2020-08-13

### Changed
- Updated the `symfony/property-access` and `symfony/finder` requirements to allow v5.x.

### Fixed
- Fixed a bug where users/groups with the "Manage All Submissions" permission would see an error when viewing CP Submissions index.
- Fixed a bug where the CP Submissions index chart was including submissions from all forms including ones a user/group doesn't have access to.
- Fixed a bug where allowing a submission from the Spam Folder would not carry over any updates to the Title and Status.
- Fixed a bug where Date fields were not able to be mapped to with the HubSpot API integration.

## 3.7.3 - 2020-08-12

### Changed
- Updated the `symfony/filesystem` requirement to allow v5.x.

## 3.7.2 - 2020-08-11

### Added
- Added the ability to access the newly created Craft Elements in email notification templates when using the Element Connections feature.

### Fixed
- Fixed a bug where Spam keyword blocking was not correctly working with cyrillic characters.
- Fixed a bug where Radio fields with an option that contained an `&` symbol and set to be the default choice, would not render in the template as selected.
- Fixed a bug where Datepicker localization wasn't respecting 4-character language codes.
- Fixed a bug where user/group permissions were not correctly removing form category options in the Submissions CP index.

## 3.7.1 - 2020-07-24

### Changed
- Updated the "Automatically Scroll to Form on Errors and Multipage forms" feature/setting to no longer automatically insert an anchor at the top of the form if the setting is disabled.
- Optimized the Quick Export feature to only post selected form export preferences.

### Fixed
- Fixed a couple of compatibility issues with Craft 3.5+.
- Fixed a bug where the Bootstrap 4 formatting template used `col-xs-12` instead of `col-12`.

## 3.7.0 - 2020-07-03

### Added
- Added more granular User permissions for form management. New permissions for Creating New Forms, Deleting Forms and per form management.
- Added a setting to Payments integrations that allows you to suppress email notifications and API integrations if the payment fails.

### Changed
- Updated the `status` form parameter to allow updating of status upon editing of submissions in the front end.
- Updated the Salesforce Lead CRM integration to allow mapping to relationship fields and subsquently the Lead Record Type field.
- Updated the POST Forwarding feature to pass along IP address as well.
- Updated uploading of images with EXIF rotation data to now correctly rotate the images upon upload.

### Fixed
- Fixed a bug where installing the Demo Templates could result in a `Undefined index: uriParts` error for some users.
- Fixed a bug where the Export Profile `setting` column in the database could be too small and result in an error.
- Fixed a bug where the SharpSpring CRM integration was not working correctly for mapping to custom fields.
- Fixed a bug where removing a Payments setup from a form would not remove all traces and error on the front end when submitting the form.
- Fixed a bug where clicking on Form filters in the Submissions index page would not update the URL to contain the handle.
- Fixed a bug where the Mailchimp mailing list integration Contact Tags were not being updated when submitting a submission.

## 3.6.12 - 2020-06-11

### Changed
- Updated the Zoho Lead and Deal CRM integrations to be simpler and correct a connection issue. You may need to recreate the integration inside Freeform settings if it doesn't begin to work immediately.

### Fixed
- Fixed a bug where Radio fields were not loading the `required` attribute when the `useRequiredAttribute: true` parameter was set.
- Fixed the `freeform.loadFreeformScripts()` function to no longer load multiple instances of the same scripts in some cases.

## 3.6.11 - 2020-06-04

### Fixed
- Fixed a bug where the refresh token was not fetching for the Constant Contact mailing list integration.

## 3.6.10 - 2020-06-03

### Changed
- Optimized data stored in session as well as introduced a hard limit on active form session instances.

### Fixed
- Fixed a bug where Salesforce Contact Tasks for the Leads integration were not being assigned the correct Salesforce user.
- Fixed a bug where campaigns may not be able to be mapped correctly to the Pardot API integration.

## 3.6.9 - 2020-05-21

### Added
- Added ability for the Salesforce Lead API integration to optionally have submissions converted to Salesforce Contact Tasks for existing Contacts if the email address matches.

### Changed
- Updated the reCAPTCHA v2 Invisible and v3 spam protection features to optionally be enabled per form. Also now automatically disable reCAPTCHA on Payments enabled forms to prevent conflicts between reCAPTCHA and Stripe.
- Updated the reCAPTCHA v2 Checkbox feature to no longer have ability to make it bypass error and send to Spam Folder.

### Fixed
- Fixed a bug where an error would show from Pardot about fields that aren't objects.
- Fixed a bug where error messages from Craft for image uploads would trigger an error on `htmlentities()` method.

## 3.6.8 - 2020-05-14

### Added
- Added Pardot CRM integration.
- Added ability to map GDPR consent / marketing settings to Mailchimp contacts.
- Added ability to map Tags to Mailchimp contacts.
- Added AFTER_UPDATE and BEFORE_UPDATE events to the submission save action.

### Changed
- Updated the ActiveCampaign integration to have a maximum limit of 50 mailing list instead of the default, 20.
- Updated the `fieldIdPrefix` parameter to apply itself to Honeypot field ID's as well.
- Updated Freeform Payments feature to log more detailed failure reasons in control panel.
- Updated the MIME type security check on file uploads to include OpenDocument file type extensions.
- Updated the Craft Campaign plugin mailing list connection in Composer to display and work with available mailing lists across multi-sites.
- Updated the Constant Contact integration to no longer fail when a duplicate email address is submitted to it. Instead, it'll update the existing contact in Constant Contact, and allow for assignment to more than 1 mailing list.

### Fixed
- Fixed a bug where the Phone field error message (when no validation pattern is set) was not translatable.
- Fixed a bug in the Tailwind example formatting template where a space was missing between some field classes.
- Fixed a bug where the new Constant Contact integration would display an Unauthorized error in error log every day.

## 3.6.7 - 2020-04-17

### Added
- Added developer events for Stripe Payments to modify the default description of payments in Stripe.
- Added a hook for other plugins to include info in the CP submission edit view.

### Changed
- Updated Freeform to be compatible with PHP 7.4.
- Updated the Constant Contact mailing list integration to use the new v3 API. The old v2 API option is now deprecated and still works for legacy.
- Updated the POST Forwarding feature to also include the CSRF token, submission ID, submission token, and submission title.

### Fixed
- Fixed a bug where the submissions chart on the CP Submission index page was not correctly localizing.
- Fixed a bug where Stripe Payment success/fail email notifications were not working.
- Fixed a bug where using Stripe Payments and reCAPTCHA v3 together on a form would sometimes double-submit the form.
- Fixed a visual issue with long option names on fields in the Conditional Rules area in Composer form builder.
- Fixed a bug where removing rows for Table fields on front end in IE 11 would not work correctly.
- Fixed a bug where Composer could give a deprecation notice about Code Packs.
- Fixed a bug where there was no translation for "Tried uploading {count} files. Maximum {max} files allowed." front end error.

## 3.6.6 - 2020-04-01

### Changed
- Updated file uploading to check mime type to help prevent possible security issues (e.g. stops `.txt` files being renamed to `.jpg`).
- Updated Freeform Payments feature to use the Stripe PHP 7 library dependency, allowing for PHP 7.4 support.

### Fixed
- Fixed a bug where the Predefined Assets feature was not working correctly.
- Fixed a bug where Table fields with many columns would distort the Composer form builder interface.
- Fixed a bug where adding rows for Table fields on front end in IE 11 would not work correctly.
- Fixed a bug where submitting forms containing reCAPTCHA v3 on IE 11 would not work correctly.
- Fixed a bug where a JS error would occur when creating/editing CRM integrations.

## 3.6.5 - 2020-02-27

### Fixed
- Fixed a bug where Relation fields were not able to be mapped to with the Element Connections feature.
- Fixed a bug where field variables could not be used in CC and BCC fields of email notification templates.
- Fixed a bug where changing User groups (selecting different ones after the fact) for Element Connections would clear out existing field mapping.
- Fixed various display/visual issues in Composer form builder under certain circumstances.
- Fixed a bug where having more than 1 file upload field in a form and using the built-in AJAX would not display validation errors correctly in some cases.

## 3.6.4 - 2020-02-21

### Fixed
- Fixed a bug where Quick Export was not working for some customers.

## 3.6.3 - 2020-02-19

### Added
- Added Spam Reasons feature for the Freeform Spam Folder. It will now log the reason Freeform considered the submission to be spam and placed in the Spam Folder.

### Changed
- Updated the POST Forwarding feature to now convert Email Address field data a string value instead of array.

### Fixed
- Fixed a bug where the API integration queue database table for spam submissions had an incorrect unique index.
- Fixed a bug where Freeform would perform a numeric check on field hashes, and would cause issues if the hash resembled number notations.
- Fixed a bug where Freeform form cookies were being set to expire in 50 years instead of 1 year.
- Fixed a bug where the Quick Save button in Composer was getting incorrect positioning on smaller screens.

## 3.6.2 - 2020-02-04

### Changed
- Updated Composer form builder to now have floating/locking columns, as it did prior to 3.6 UI update.
- Updated Payments integration to automatically map Email field mapping to Stripe's `reciept_email` field in addition to its `email` field, in order for Stripe to send email notification receipts to successful payments.

## 3.6.1 - 2020-01-29

### Fixed
- Fixed a bug where the Save button inside Composer form builder would disappear when changing a form's name.
- Fixed a bug where API integrations would cause the Composer form builder to not load and/or display JS errors.
- Fixed a bug where email notifications would not send if using the Relations feature and including the related element inside the notification template.
- Fixed a bug where Dynamic Recipients fields were not always exporting as the email address value when using Quick Export.

## 3.6.0 - 2020-01-23

> [!IMPORTANT]
> Freeform 3.6+ is now only available for Craft 3.4+, as this update includes various Craft 3.4+ compatibility fixes and changes. Also, existing Zoho CRM users will need to update and reauthorize their integrations due to a change made for better compatibility with European accounts.

### Changed
- Updated Freeform for compatibility with Craft 3.4+.
- Made various improvements and changes to the Composer form builder UI.
- Updated the way Freeform checks for old 2.x versions of its dependencies so it won't break installs.
- Changed the Zoho CRM integrations to now require manual setting of Access Token URL and API Root URL for compatibility with European accounts. Existing Zoho CRM users will need to update and reauthorize their integrations.

### Fixed
- Fixed a bug where using reCAPTCHA v3 and having more than 1 form loaded in the same page would display JS errors.
- Fixed a bug where Composer and the front end could error if setting to hide default formatting templates was enabled, and there are no custom formatting templates available.

## 3.5.10 - 2020-01-14

### Changed
- Updated the Date & Time field type to no longer include the `autocomplete` attribute by default.

### Fixed
- Fixed a bug where the File Upload field type would not correctly display validation errors with built-in AJAX.
- Fixed a bug where Stripe payments that contained decimals values would lose 1 cent in the transaction.
- Fixed a bug where the Freeform error log could sometimes not display the correct dates.
- Fixed a bug where using the Predefined Assets feature could cause the email notification template and form to fail in some cases.
- Fixed a bug where the "Export as CSV" feature for submissions was ordering field columns by field ID instead of matching the Composer layout order.
- Fixed a bug where Placeholders for fields were not translatable.
- Fixed a bug where you could not select a formatting template if only 1 was available.

## 3.5.9 - 2020-01-02

### Changed
- Updated reCAPTCHA settings to have the ability to use environment variables.
- Updated email notification templates to have more settings with the ability to use environment variables.

### Fixed
- Fixed a bug where the GET Query String feature was not working correctly for single checkboxes.
- Fixed a bug where error messages on radio or checkbox group fields were not displaying correctly when options are displayed on a single line and using the built-in AJAX feature.
- Fixed a bug where filtering the submissions overview in the CP by status (e.g. pending or closed), would return a 500 error in some cases.

## 3.5.8 - 2019-12-16

### Added
- Added POST Forwarding feature, allowing your forms to automatically submit an extra POST to pass off submission data to an external API URL upon successful submit and validation of a form.

### Fixed
- Fixed a bug where Stripe payment forms would fail if a declined credit card was used.

## 3.5.7 - 2019-12-11

### Added
- Added Tailwind CSS formatting template to the built-in example options. Due to the nature of Tailwind, this likely won't be useable as-is for most customers, but will serve as a good starting point for creating your own. If you have any suggestions on how we can improve upon this template, please let us know. :)

### Fixed
- Fixed a bug where the 'Disable Submit Button on Form Submit' feature was not working correctly.

## 3.5.6 - 2019-12-09 [CRITICAL]

### Fixed
- Fixed a bug where reCAPTCHA v2 Invisible and v3 was breaking built-in AJAX submitting.
- Fixed a potential security vulnerability with submitting of forms.
- Fixed a bug where Min Date and Max Date settings for Date & Time fields could error incorrectly in some cases.

## 3.5.5 - 2019-12-04

### Added
- Added `allFieldsAndBlocks` to email notification templates, which does the same as `allFields` but includes HTML blocks and Rich Text blocks as well.

### Changed
- Updated Freeform's Composer-based attributes settings for fields and forms to no longer allow Twig parsing.

### Fixed
- Fixed a bug where reCAPTCHA v3 was not working correctly.
- Fixed a bug where Table fields would not generate additional rows correctly if there was only a single column.
- Fixed a bug where the submissions chart in the Submissions CP index page was not correctly factoring in timezones.

## 3.5.4 - 2019-11-14

### Fixed
- Fixed a bug where Table field types could not be shown/hidden with Conditional Rules.
- Fixed a bug where Dynamic Recipients fields would not map to API integrations correctly.
- Fixed a bug where granting users/groups permissions to manage specific forms would not allow them to delete submissions.
- Fixed a bug where allowing spam submissions would error if the email notification template uses a layout.
- Fixed a bug where the multi-page form `currentPage` property was resetting to `0` if page reloaded due to error, etc.
- Fixed a bug where the CP individual submission page was not showing/hiding fields based on Conditional Rules.
- Fixed a bug where the CP individual submission page was showing and validating reCAPTCHA.
- Fixed a bug where field options populated by Entries that are of a Structure type could not be sorted by their correct hierarchy.
- Fixed a bug where setting an incorrect IP address field value for the HubSpot integration would not log an error to the log.

## 3.5.3 - 2019-10-24

### Added
- Added Zoho Leads and Zoho Deals CRM API integrations (Pro).
- Added 'Minimum Submit Time' and 'Form Submit Expiration' form submit control settings to help fight spam or other use-cases.

### Fixed
- Fixed a bug where using reCAPTCHA v2 Invisible would break form submitting in some cases.
- Fixed a bug where you could get an internal server error when someone uploaded a 0 byte file attachment.

## 3.5.2 - 2019-10-18

### Fixed
- Fixed a bug where using Microsoft Edge browser would sometimes create duplicates when the user submitted a form.

## 3.5.1 - 2019-10-01

### Fixed
- Fixed a bug where submissions were falsely being flagged as spam when a custom Honeypot field name was not set.
- Fixed a bug with a navigation permission check on Export Profiles.

## 3.5.0 - 2019-09-25

### Added
- Added Table field type, which allows users to enter repeating data rows into predefined columns (Pro).
- Added Invisible field type, which allows you to collect hidden data in form submissions without a hidden field being present in the template source code (Pro).
- Added 'Custom Honeypot Field Name' and 'Custom Honeypot Failed Error Message' settings for Freeform's built-in spam protection.

### Changed
- Updated File Upload fields to now allow custom subfolder paths. You can also dynamically build subfolder paths with `form.handle`, etc.
- Updated the Users Element Connection to allow assigning users to multiple user groups.
- Updated Freeform to show an error message if the old Freeform Pro 2.x plugin (which should be uninstalled) is still installed after an upgrade from Freeform 2.x to 3.x. Please follow the [Upgrading from Freeform 2.x guide](https://docs.solspace.com/craft/freeform/v3/setup/updating-freeform-2/) for proper upgrading from 2.x to 3.x.
- Adjusted the Craft element toolbar in CP Submissions index to no longer include Export button (as Freeform includes other and better export options, and this approach would not work correctly).

### Fixed
- Fixed a bug where submissions that are flagged as spam would not keep the attached file(s) submitted with it.
- Fixed an incompatiblity issue with the Scout plugin and potentially other plugins.
- Fixed several IE 11 compatibility bugs with special field types.

## 3.4.1 - 2019-09-16

### Changed
- Updated the Signature fieldtype to also include ability to style the signature pad/square and pen size, color, etc, inside Composer.

### Fixed
- Fixed a bug where the Webhooks create/edit page was not showing options for the Type setting.

## 3.4.0 - 2019-09-12

### Added
- Added Signature fieldtype that allows users to handwrite signatures inside forms (Pro).

## 3.3.3 - 2019-09-11

### Added
- Added new `freeform.loadFreeformScripts()` function that allows you to manually insert Freeform's JS in the template.
- Added the ability to check if the multi-page 'Previous' submit button was pressed in the Freeform JS plugin on submit callback.

### Changed
- Updated the Infusionsoft API integration to allow multiple tags to be assigned.
- Updated the Infusionsoft API integration to work with 'Whole Number' and 'YesNo' field types.

### Fixed
- Fixed a bug where using the User Element Connections feature would not work correctly if using a multi-page form and the Password field was on a page other than the last.

## 3.3.2 - 2019-09-05

### Fixed
- Fixed a bug where a migration was causing the Payments `intervalCount` database table column to not allow null, which would trigger errors when submitting the form.
- Fixed a bug where editing submissions on the front end was not remembering updated choices when the form reloaded after triggering errors.
- Fixed a bug where error styling on inputs were not checking for actual changes to the field value before removing error styling when using with AJAX.
- Fixed a bug where the submission ID was no longer be returned on AJAX calls.
- Fixed a bug where the Payments SCA popup was not being triggered in Firefox.
- Fixed a bug where IP address collecting was not as reliable.

## 3.3.1 - 2019-08-21

### Fixed
- Fixed a bug where the return URL was not present in AJAX responses.
- Fixed a bug where rating and single checkbox fields were losing their values when being updated in the CP.
- Fixed a bug where updating submissions status from CP index or front end editing would clear submission Notes.

## 3.3.0 - 2019-08-20

> [!IMPORTANT]
> This update includes a large change to the Stripe Payments integration to support the [Strong Customer Authentication (SCA)](https://stripe.com/docs/strong-customer-authentication) changes to the Stripe API. Please read changelog carefully and review notes in the [Freeform Payments documentation](https://docs.solspace.com/craft/freeform/v3/integrations/stripe-payments/#strong-customer-authentication-sca) to see what possible breaking changes might affect your forms and/or form flow.

### Added
- Added Notes feature for submissions. Allows you to add private notes when updating a submission inside the control panel.

### Changed
- Updated the Stripe Payments integration to support [Strong Customer Authentication (SCA)](https://stripe.com/docs/strong-customer-authentication) changes to the Stripe API. The new EU rule comes into effect on September 14, 2019, so any site based in the EU or accepting payments from EU customers will be affected and need to update to Freeform 3.3+ in order to prevent payments from being declined. Due to some limitations with the API and making things work with Freeform, there are some new limitations that may be breaking changes to your form and/or form flow. Please refer to the [Freeform Payments documentation](https://docs.solspace.com/craft/freeform/v3/integrations/stripe-payments/#strong-customer-authentication-sca) for more information.

### Fixed
- Fixed a bug where the Honeypot input field contained the "aria-hidden" attribute twice.

## 3.2.5 - 2019-08-09

### Added
- Added German translations.

### Fixed
- Fixed a potential security vulnerability with textarea fields.
- Fixed a bug where the `overrideValues` parameter was not correctly setting defaults for checkboxes, checkbox groups, radios and select fields.
- Fixed a bug where the Craft Campaign plugin mailing list integration was not available to setup.
- Fixed a bug where soft deleted Section Entry Types were showing as options in the Element Connections feature.
- Fixed a bug where elements were not being deleted via the Service method.

## 3.2.4 - 2019-08-01

### Changed
- Updated the HubSpot integration to perform duplicate checks on Contacts and Companies and update them accordingly.
- Updated the Salesforce Opportunity integration to perform an additional duplicate check option (check on email address and email domain/website only instead of names) on Contacts and Accounts and update them accordingly.
- Updated the HubSpot and Salesforce Opportunity integrations to have settings that allow checkbox group data to append additional values on updating of Contacts and Companies/Accounts instead of replacing the value.

### Fixed
- Fixed a bug where the date picker wouldn't load for Date fields when loading more than 1 form in the same page.

## 3.2.3 - 2019-07-23

### Added
- Added support for populating Dynamic Recipients field options with Craft Element data.

### Changed
- Updated Conditional Rules to allow fields to be dependant on Hidden fields as well.
- Updated the Date & Time field type to no longer have the "Lowercase AM/PM?" settings for Date Picker.

### Fixed
- Fixed a bug where loading more than 1 of the same form in the same template would not work correctly with built-in AJAX. Be sure to specify `id` parameter so the ID is unique for each form.
- Fixed a bug where some settings-related migrations might error for some users.
- Fixed a bug where exporting with Firefox would not include file extensions for all data types.
- Fixed a bug where Credit Card fields were not working correctly in IE 11.
- Fixed a bug where the email notification template subject was encoding quotes, apostropies, etc.
- Fixed a bug where Mailing List fields were missing their icon in Composer.

## 3.2.2 - 2019-07-17

> [!IMPORTANT]
> This update includes a change to the default loading of the Date & Time field type date picker. If you're using this field type in your custom templates (default sample ones have been updated), please be sure to review and/or remove the `addEventListener("flatpickr-ready"...` JS code from your templates.

### Added
- Added a `Default Formatting Template` general setting, allowing you to set the default formatting template for all new forms.
- Added a Before Initialization developer event for Date & Time field type date picker.

### Changed
- Updated the Date & Time field date picker to no longer use `static: true` (which then loaded a special wrapper element that needed to be styled). This may be a breaking update for your templates, so be sure to review your templates if using this field type, and remove the `addEventListener("flatpickr-ready"...` JS code from your templates (default sample formatting templates have been updated).
- Updated the Notifications service to be accessible via Twig as `craft.freeform.notifications.allNotifications`.

### Fixed
- Fixed a bug where Freeform's error log would show `Cannot send session cookie` errors in Craft 3.2+.
- Fixed a bug where the Date & Time field type date picker may not correctly load correctly for alternative locales.
- Fixed a bug where creating Export Profiles for forms that contained a number in the handle would error.
- Fixed some bugs in the Infusionsoft CRM API integration.

## 3.2.1 - 2019-07-11

### Changed
- Updated Settings area to continue to allow access to API integrations, Statuses and Error Log when the Craft `allowAdminChanges` setting is false (as the aforementioned areas are not true settings available to project config, etc).

### Fixed
- Fixed a bug where the Date & Time field datepicker was not loading other locales when specified.

## 3.2.0 - 2019-07-09

### Added
- Added ability to automate relating of Freeform submissions to other Craft Elements, thus allowing robust comment, ratings/reviews, sign-up forms, and more! (Pro)
- Added Zapier and generic Webhooks integrations (Pro).
- Added ability to have page skipping in Conditional Rules feature skip to a complete submit.
- Added ability to add and remove files to submissions inside the control panel.
- Added ability to orderby and sort Element data populated into Freeform fields.
- Added ability to suppress webhooks.

### Changed
- Updated Slack Webhooks section in Settings to now just be in a consolidated section called 'Webhooks' with the option to use Slack (and Zapier, etc) as a type.
- Updated Freshdesk integration to have more improvements such as mapping off file attachments.
- Improved Submissions object template markup for automated rendering of field data to be more intuitive. No longer need to use `fieldMetadata` and can use `submission[field.handle]` instead of `attribute(submission, field.handle)`.

### Fixed
- Fixed a bug where loading more than one form with reCAPTCHA would not work correctly.
- Fixed a bug where the Stripe Webhook URL was not working correctly and would generate a 404 error.
- Fixed a bug where editing multi-page submissions with Conditional Rules page skip triggered would erase skipped page data.
- Fixed a bug where the `includeAttachments` setting in email notification templates was not working.
- Fixed a bug where unchecking a checkbox when editing a form submission would continue to leave checkbox value as checked.

## 3.1.0 - 2019-06-28

### Added
- Added `suppress` parameter that allows template-level suppression of email notifications, API integrations and Element Connections feature. Can be used for any reason, but more commonly used for editing existing submissions on front end.
- Added Freshdesk CRM (helpdesk) API integration (Pro).
- Added Infusionsoft CRM API integration (Pro).

### Fixed
- Fixed a bug where user registration forms (Element Connections) were not handling email activation process correctly.
- Fixed a bug where Payments forms would not work correctly if the dynamic amount value field was on a different page from the Stripe credit card fields in a multi-page form.
- Fixed a bug where using Export Profiles with PostgreSQL would return an error.

## 3.0.6 - 2019-06-26

### Fixed
- Fixed a bug where a migration could error for some customers using PostgreSQL.
- Fixed a bug where OAuth-based integrations were not connecting properly.

## 3.0.5 - 2019-06-25

### Fixed
- Fixed a bug where Stripe credit card fields were not working in front end templates.
- Fixed a bug where single checkbox fields weren't able to map to a Craft checkboxes field when using Element Connections feature.

## 3.0.4 - 2019-06-25

### Fixed
- Fixed a bug where the Element Connections feature would error when using a multi-option field that was required on Freeform's side.
- Fixed a bug where the Empty Option Label feature with predefined/element data options was not refreshing the value between selecting different fields.
- Fixed a bug where customers using more recent versions of PHP 7 would see a `setcookie() expects parameter 3 to be integer, float given` error.

## 3.0.3 - 2019-06-21

### Added
- Added Active Campaign CRM API integration (Pro edition).
- Added a setting to have the "Enable AJAX" feature checked by default for all new forms.

### Changed
- Updated Honeypot field to ensure screen readers don't see it and it can't be tabbed to, etc.

### Fixed
- Fixed a bug where using a Freeform field to generate the Reply-to, CC, and BCC fields that didn't contain a value (empty from submission) would cause the email notification to fail.
- Fixed a bug where editing a form submission on the front end would not automatically update the submission title.

## 3.0.2 - 2019-06-18

### Fixed
- Fixed a bug where the Freeform 3 migration had some incorrect foreign keys for some Pro and Payments database tables when upgrading from v2 Lite.
- Fixed a bug where a migration was not compatible with PostgreSQL.
- Fixed a bug where filtering submissions by status in templates would return an error.
- Fixed a bug where the Element Connections feature would error when mapping to an entry section that did not have the primary site enabled for it.
- Fixed a bug where single checkbox fields would always be checked by default when placed in multipage forms on page 2 or greater.
- Fixed some very minor visual errors in sample formatting templates.

## 3.0.1 - 2019-06-14

### Fixed
- Fixed a bug where some update migrations were failing when the `allowAdminChanges` Project Config setting was set to `false`.
- Fixed a bug Hidden fields were not always showing up in CP Submission view.
- Fixed an issue where style overrides in sample and demo templates may not always work, depending on what the Javascript Insertion Location setting is set to.
- Fixed a bug where the Freeform 3 migration was missing some meta columns from Pro and Payments database tables when upgrading from v2 Lite.
- Fixed a few minor regression bugs for notification template and formatting template path settings.

## 3.0.0 - 2019-06-11

> [!WARNING]
> This is a larger and more complex update than usual, and there's a higher chance of a failed update attempt happening. Please ensure you have a recent database backup, and we recommend you test the update on a local/staging environment before updating your production server. [Please follow the upgrading guide available here](https://docs.solspace.com/craft/freeform/v3/setup/updating-freeform-2/)!

### Added
- Added support for editing submissions (Pro).
- Added Slack Webhooks API integration (Pro).
- Added support for reCAPTCHA v2 Invisible and v3 (Pro).
- Added ability to choose failed reCAPTCHA behavior (show error or send to Freeform Spam Folder).
- Added Opinion Scale field type (Pro).
- Added Rich Text block special field to allow for easier managing of content inside form layouts (Pro).
- Added support for searching on fields in `freeform.submissions` function (`fieldSearch`).
- Added Excel exporting option (Pro).
- Added ability to limit submissions to 1 per user per form (Pro).
- Added support for GET query strings filling fields with values.
- Added CC and BCC fields in email notifications.
- Added Plain Text email notification template options.
- Added ability to predefine Assets to attach to all emails sent from an email notification template (e.g. a ticket PDF or instructions document, etc) (Pro).
- Added support for using environment variables inside email notification templates.
- Added `EVENT_BEFORE_VALIDATE`, `EVENT_AFTER_VALIDATE`, `EVENT_BEFORE_CONNECT`, `EVENT_AFTER_CONNECT` developer events for Element Connections feature.
- Added Resources area in control panel for quick access to docs/support/feedback, etc.

### Changed
- Consolidated _Lite_, _Pro_ and _Payments_ plugins into a unified Freeform plugin with editions.
- Combined Freeform Payments into the Pro edition of Freeform.
- The following features are no longer be available in the _Lite_ edition of Freeform (but available in _Pro_): Element Connections (mapping to Craft Elements)/ building of User Registration forms, Confirm and Password field types, retroactively resend email notifications, automatically purge submission data, accept Payments with Stripe (requires Pro now) and rename the plugin in CP. Support for reCAPTCHA v2 Checkbox was added to the _Lite_ edition.
- Updated Freeform to use a unified JS plugin to handle all built-in JS. Plugin is extendable too, allowing for easier overriding of defaults.
- Updated the Freeform JS Honeypot to now be regular Honeypot with optional JS enhancement feature (to work the same way).
- Updated Phone, Website and Number field types to default to rendering as corresponding type attribute (e.g. `tel`, `url`, `number`).
- Updated Canadian Provinces predefined field data to include French and Bilingual options in addition to English.
- Improved Element Connections feature to be more robust and handle mapping of data better.
- Updated User Element Connection feature to have option to suppress User Activation email notification for those that wish to Admin activate only.
- Updated User Element Connection feature to allow all user groups, including ones that have access to Craft CP.
- Updated the _Freeform Javascript Insertion Location_ setting to include option to not load it at all (and have the user load it manually).
- Adjusted built-in AJAX feature to automatically remove error styling on blur once a user enters a new value/option.
- Overhauled Freeform demo templates to be simpler and easier to use / understand.
- Updated `carbon` dependency to `^1.22.1|^2.19` for better compatibility with other plugins, and to reduce the chances of seeing deprecation notice.
- Various visual improvements to Composer interface and throughout CP.

### Fixed
- Fixed a bug where the Purge Submissions feature was not also removing associated Assets.
- Fixed a bug where using AJAX and uploading files was keeping Asset files stored even when the form errored.
- Fixed a bug where soft-deleted submissions were being included in exports.
- Fixed a bug where using Radio field type with Freeform Payments forms (for amount) were not working correctly.
- Fixed a bug where Rating field stars sometimes looked plumper than they should in the CP.

## 2.5.27 - 2019-12-09 [CRITICAL]

### Fixed
- Fixed a potential security vulnerability with submitting of forms.

## 2.5.26 - 2019-06-26

### Changed
- Updated Honeypot field to ensure screen readers don't see it and it can't be tabbed to, etc.

### Fixed
- Fixed a bug where the Empty Option Label feature with predefined/element data options was not refreshing the value between selecting different fields.
- Fixed a bug where OAuth-based integrations were not connecting properly.

## 2.5.25 - 2019-06-19

### Added
- Added Active Campaign CRM API integration (Pro edition).

### Changed
- Updated `carbon` dependency to `^1.22.1|^2.19` for better compatibility with other plugins, and to reduce the chances of seeing deprecation notice.

### Fixed
- Fixed a bug where single checkbox fields would always be checked by default when placed in multipage forms on page 2 or greater.
- Fixed a bug where the Purge Submissions feature was not also removing associated Assets.
- Fixed a bug where using AJAX and uploading files was keeping Asset files stored even when the form errored.
- Fixed a bug where soft-deleted submissions were being included in exports.
- Fixed a bug where Rating field stars sometimes looked plumper than they should in the CP.
- Fixed some minor display issues with Date & Time fields in demo templates and sample formatting templates.

## 2.5.24 - 2019-05-16

### Changed
- Updated plugin icon.

### Fixed
- Fixed a bug where entering the root template directory for directory path for Formatting Templates and Email Notifications settings would result in an error.
- Fixed some CSS issues in CP submission detail views.

## 2.5.23 - 2019-05-07

### Changed
- Updated Flatpickr library (for Freeform date picker on Date fields) to v4.5.7, which resolves some issues.
- Updated Pro edition to prepare for future official Editions support in Freeform.

## 2.5.22 - 2019-04-30

### Fixed
- Fixed a bug where the `allowAdminChanges` Project Config setting fix was causing errors for Craft 3.0.x users.

## 2.5.21 - 2019-04-27

### Fixed
- Fixed a bug where Settings area in CP was still visible when the `allowAdminChanges` setting is disabled for Project Config.
- Reverted the Hidden fields order in CP Submission view change from v2.5.19 due to other side effects occurring from it.

## 2.5.20 - 2019-04-25

### Fixed
- Fixed a bug where the return URL could trigger an error in some cases.

## 2.5.19 - 2019-04-23

### Added
- Added Insightly CRM Lead integration for Freeform Pro edition.

### Changed
- Updated Hidden fields to be in their original field position (instead of first in form) when viewing submissions in the CP.
- Updated Date field picker and format getters to be public.

### Fixed
- Fixed a bug where Form Color setting for forms was only visible in Pro edition.
- Fixed a bug where using `DD/MM/YYYY` formatting and Min/Max date setting on Date fields would result in an error upon submit when validating.
- Fixed a bug where the Resend Notifications feature errored when email notifications extended layouts.
- Fixed a bug where permanently deleting fields could cause an error if assigned to an integration field list.

## 2.5.18 - 2019-04-18

### Added
- Added Active Campaign mailing list API integration (Pro edition).
- Added `EVENT_AFTER_GENERATE_RETURN_URL` developer event, allowing modifying of the return URL of forms.

### Changed
- Updated the HubSpot integration to not create blank Deals if no Freeform data is mapped to Deal fields.
- Updated the HubSpot integration to include an IP Address mapping setting, allowing you to map IP addresses to a custom field in Contacts.

## 2.5.17 - 2019-04-08

### Added
- Added a `getTagAttributes()` function to the Form component.

### Fixed
- Fixed a bug where loading more than 1 form in a page with reCAPTCHA would trigger JS errors.
- Fixed a bug where Users element connection was not always displaying all available fields for mapping for some User groups.
- Fixed a bug where the `renderSingleInput()` method would not work with any directly applied custom attributes, etc.

## 2.5.16 - 2019-03-28

### Fixed
- Fixed a bug where multi-page tabs would not allow you to re-arrange them in CP.
- Fixed a bug where user/group permissions for Settings didn't give access to create/edit API integrations and statuses.
- Fixed a bug where trying to load mailing lists for API integration would error when using PostgreSQL.

## 2.5.15 - 2019-03-25

### Changed
- Updated Flash success message to return 'Form submitted successfully' instead of `true`.
- Updated Dutch NL translation.

### Fixed
- Fixed a bug where email notification values were not always being escaped.
- Fixed a bug where API integration values for multi-option fields were being sent as option labels instead of option values.
- Fixed a bug where underscores in email notification templates were not rendering correctly.
- Fixed a bug where filtering submissions in CP by status was not working and returning an error.
- Fixed a bug where the CP Submissions chart could error when using PostgreSQL.
- Fixed a bug where Freeform has unused class that caused the Craft Webhooks plugin to fail.

## 2.5.14 - 2019-03-13

### Fixed
- Fixed a bug where using the User element connection with the Password field on a page other than the last page of multi-page forms would have the mapping fail.
- Fixed a bug where updating a mailing list integration's handle would cause it to duplicate instead.

## 2.5.13 - 2019-02-26

### Changed
- Updated Salesforce integrations to send over field data as strings (instead of integer) when mapping to a Salesforce Phone field type.

### Fixed
- Fixed a bug where the Submissions CP index page would error for some customers.
- Fixed a bug where submissions were not able to be restored (from soft delete) in Craft 3.1+.

## 2.5.12 - 2019-02-21

### Added
- Added getters for all form field attributes.

### Fixed
- Fixed a bug where soft deletes were not correctly affecting visual counts and stats in Freeform CP in Craft 3.1+.
- Fixed a bug where a migration was not working correctly in Craft 3.1+.
- Fixed a bug where User Registration forms would error if an optional User Photo field was setup and mapped and the user submitted without a photo.
- Fixed a bug where saving email notifications in the control panel would error in PHP 7.3. Updated the Markdownify dependency.

## 2.5.11 - 2019-01-30

### Fixed
- Fixed a bug where manually specifying field options (such as `fieldName.label`) in email notification templates were not working.

## 2.5.10 - 2019-01-24

### Fixed
- Fixed a bug where using the Export as CSV option was not exporting file uploads as the filename/full path.
- Fixed a bug where assets for file upload fields in CP submission edit view were no longer linked to be downloadable.

## 2.5.9 - 2019-01-21

### Added
- Added two new methods for the `EVENT_BEFORE_PUSH` developer event: `setValues($values)` which will override all values, and `addValue($key, $value)` which allows for a quick and easy value addition to the existing values.

### Changed
- Updated Freeform API to be accessible via console.
- Updated Salesforce Lead and Opportunity integrations to clear out all empty values before submitting to Salesforce.

### Fixed
- Fixed a bug where existing HTML block fields in Conditional Rules tab did not include their hash (for identifying which was which when more than one existed).
- Fixed a bug where an error could be triggered when Email Address spam blocking was enabled and shown below email fields.
- Fixed a potential XSS vulnerability on Email field types.
- Fixed a bug where the 'Add criteria' button in Conditional Rules feature was not working in Firefox.

## 2.5.8 - 2019-01-11

### Added
- Added 'Automatically Scroll to Form on Errors and Multipage forms' setting to allow the ability to disable this feature.

### Changed
- Updated Freeform's developer events for compatibility with the Craft Webhooks plugin's [updated approach](https://github.com/craftcms/webhooks/blob/develop/CHANGELOG.md#112---2018-12-21) (v1.1.2).
- Updated Freeform to reset `siteId` for submissions in `elements_sites` database table to whichever site is the primary site (to prevent CP viewing issues if the primary site is switched along the way).

### Fixed
- Fixed a bug where adding a new File Upload field to a form that had defaults of no file kinds checked (implying allow ALL file types) would not save correctly to the form layout and error when submitting the form.
- Fixed a bug where fields shown/hidden with Conditional Rules feature that were dependent on checkboxes were not being correctly included in email notifications or showing in the submission detail view in control panel.
- Fixed a bug where attempting to change the status of a submission in the control panel was not working.
- Fixed a bug where the "Manage All Submissions" user/group permission was not allowing users to view or allow spam submissions.

## 2.5.7 - 2018-12-20

### Changed
- Updated Freeform's developer events for compatibility with the Craft Webhooks plugin.
- Improved file cleanup and submission/spam purge. File cleanup no longer triggers on every request and does not clear DB cache to check for table existence.
- Optimized the Submission element.
- Disabled automatic emoji conversion support for now to allow for significant performance improvements.

### Fixed
- Fixed a bug where updating submissions inside control panel would detach uploaded files.

## 2.5.6 - 2018-12-18

### Added
- Added `EVENT_AFTER_RESPONSE` developer event for all API integrations.

### Changed
- Updated Demo Templates installer to be compatible with Craft 3.1.
- Updated File Upload fields in Freeform CP Submissions list to use Craft element viewer/editor.

### Fixed
- Fixed a bug where the `overrideValues` parameter was not working for pre-selecting options for multi-option field types.
- Fixed a bug where Freeform Payments was logging credit card decline/fail errors to the Freeform error log.

## 2.5.5 - 2018-12-11

### Fixed
- Fixed a bug where Conditional Rules feature settings inside Composer stopped working correctly after 2.5.4 update.
- Fixed a bug where Freeform would error when attempting to install on Craft 3.1.
- Fixed a bug where the 2.5.4 update migration would error for PostgreSQL users.
- Updated `freeform.submissions` to check if a Form object is passed to submission query form variable, and extract the form handle from that.
- Fixed a bug where Freeform Payments subscription transactions that failed would not contain any (failed) Payment information attached to the submission.

## 2.5.4 - 2018-12-07

### Changed
- Updated Salesforce Lead and Opportunities integrations to work with Date/Time fields and more reliably with numeric data.

### Fixed
- Fixed a bug where File Upload fields would hold the form back from submitting if required and also hidden due to Conditional Rules logic.
- Fixed a bug where loading the same form twice in the same template (even with `fieldIdPrefix` parameter) would have issues with JS-related features and fields.
- Fixed a potential issue where multi-page forms could be submitted more than once, causing the form to fail/lose its place.
- Fixed a bug where `status` filtering on Submissions in front end was not correctly working with pagination.
- Fixed a bug where the Min Date and Max Date feature for Date & Time fields were not accounting for all formatting variations.
- Fixed a bug where users and user groups with the 'Manage all Submissions' permission (only) were not able to export submissions as CSV.
- Fixed a bug where payments for Freeform Payments were not going through correctly when using with Built-in AJAX feature.
- Fixed a bug where the US States predefined options list contained more than official states. Also added a States & Territories list that contains official states and territories.
- Fixed a bug where the 'Spam Automatic Purge enabled' line in settings overview on Dashboard would always show 1 day.

## 2.5.3 - 2018-11-28

### Fixed
- Fixed a bug where the Recent Submissions widget was causing an error in the Craft Dashboard page.
- Fixed a bug where `rand()` and other native SQL functions did not work for `orderBy` parameter in `freeform.submissions`.

## 2.5.2 - 2018-11-27

### Added
- Added Freeform Submissions Element Fieldtype.

### Changed
- Updated Conditional Rules feature to now include Submit buttons and HTML blocks.

### Fixed
- Fixed a bug where Conditional Rules feature was not working with File Upload fields.
- Fixed a bug where setting a `class` attribute in Composer for field labels would sometimes not render correctly.
- Fixed a bug where the Built-in AJAX feature was still attempting to redirect after successful submit.
- Fixed a bug where Conditional Rules feature was not excluding fields hidden from logic in email notifications.
- Fixed a bug where ordering Submissions in front end was not working correctly.

## 2.5.1 - 2018-11-20

### Changed
- Updated the way reCAPTCHA handles loading in templates. When loading an entire form via AJAX, you'll need to manually load the reCAPTCHA JS yourself now. AJAX demo templates are updated to reflect this.

### Fixed
- Fixed a bug where restricted users/user groups were able to see form submissions they didn't have access to when clicking on 'All Submissions' filter in control panel.
- Fixed a bug where the 'Export to CSV' feature would error for some users/user groups.
- Fixed a bug where the 'Freeform Javascript Insertion Location' setting was not defaulting to 'Inside Form' (which offers better support for AJAX and other convenience features, etc) for fresh installs.

## 2.5.0 - 2018-11-14

### Added
- Added ability to 1-click enable built-in AJAX (anywhere) for forms via Composer.
- Added ability to set attributes for labels, inputs, errors and instructions directly inside Composer property editor for fields.
- Added ability to resend email notifications from the control panel for submissions.
- Added ability to set static and relative date restrictions to the Date fieldtype.
- Added Composer setting for Radio fieldtype to display options on a single line (instead of list).
- Added Salesforce Opportunities API integration (Pro edition).
- Added Assets element data feeder option for populating multi-option fieldtypes.
- Added Quick Form Craft Dashboard Widget. Allows you to set up support forms for clients, etc.
- Added setting to disable rendering of HTML inside Composer & Submissions views.
- Added `status` parameter to `freeform.form` function so that statuses can be set at template level.

### Changed
- Updated Salesforce Lead integration to optionally accept credentials to be entered via the Freeform control panel and saved to database.
- Updated the Craft User Element connection to respect Craft's `autoLoginAfterAccountActivation` config setting and allow new user registrations to automatically be logged in.
- Updated CRM integrations to allow for Freeform Payments data to be mapped to CRM fields.
- Updated CRM integrations with more than 1 object mapped to have separate field mapping tables (cleaner interface).

### Fixed
- Fixed a bug where Phone fieldtype would have a JS map file error in front end.
- Fixed a bug where some update migrations could error for PostgreSQL users.
- Fixed a bug where default formatting templates and demo templates had incorrect order of Twig filters that prevented HTML from being rendered in field option labels.
- Fixed a bug where File uploads were not being mapped to User Photo field correctly (in User Element Connection).
- Fixed some visual bugs in Composer for required asterisks.

## 2.4.3 - 2018-11-09

### Fixed
- Fixed a bug where CSV exports could sometimes error on multi-option field types.
- Fixed a bug where Widgets and the Freeform Dashboard could error in PostgreSQL and some versions of MySQL.

## 2.4.2 - 2018-11-06

### Added
- Added dotmailer mailing list integration for Freeform Pro edition.

### Fixed
- Fixed a bug where Exports were not showing the correct localized dates.
- Fixed a bug where Payments would sometimes error when using dynamic subscription-based payments.
- Fixed a bug where viewing the Submissions and Spam Folder pages would error when a form contained a dash in its handle.
- Fixed a bug where Rating fields were not displaying the stars CSS in rendered forms and CP submissions view.
- Fixed a bug where there were a couple different JS warnings in CP submissions view and inside rendered forms, when using certain special fields.
- Fixed a bug where submitting a form would error if an invalid/removed email notification template was assigned to that form.
- Fixed a bug where the 'Reset' spam button would incorrectly show in CP Forms list page when using with Spam Folder.

## 2.4.1 - 2018-10-12

### Changed
- Updated Spam Counts for forms to correctly reflect the number of submissions in spam folder when Spam Folder is enabled.
- Updated Element Feeder feature to include Site selector to allow specifying of alternate site content.

### Fixed
- Fixed a bug where all API integrations were always forcing a refresh on each form edit page.
- Fixed a bug where the Email field type and some others were not correctly mapping to Craft Entries in Element Connections feature.
- Fixed a bug where the Phone field type JS validation was causing issues in IE11.
- Fixed a bug where uploaded files were no longer displaying in CP submission detail view.

## 2.4.0 - 2018-10-05

### Added
- Added Conditional Rules logic for fields and pages!
- Added Dashboard that gives you convenient insights and an overview of your forms, submissions, settings and logged errors.
- Added an Error Log page inside Freeform control panel area to conveniently check for Freeform-related errors.

### Changed
- Improved Composer's Property Editor heading to have tabs and larger titles instead of buttons.
- Consolidated Freeform's many error log file writing into a unified `freeform.log` error log file.
- Updated Dashboard widget charts to start at `0` and no longer display negative numbers when there's no data.
- Updated column breakpoint for Bootstrap formatting templates to be `sm` instead of `lg`.

### Fixed
- Fixed a bug where File Upload fields weren't respecting `extraAllowedFileExtensions` config override setting.
- Fixed a bug where the Freeform 2.3 migration would not work correctly with PostgreSQL.
- Fixed a bug where `Submission::__isset()` check was sometimes returning `null` instead of `false`.

## 2.3.4 - 2018-09-24

### Changed
- Updated page changes (forward and backward) in multipage forms to auto-scroll to form (helpful if your form is lower down on page).

### Fixed
- Fixed a bug where Salesforce API connections were still holding their connection.
- Fixed a bug where the SharpSpring API integration was not connecting to SharpSpring.
- Fixed a bug where forms would error on front end if Spam Blocking keywords contained regex-specific symbols.

## 2.3.3 - 2018-09-17

### Fixed
- Fixed a bug where Payments were not working correctly in Flexbox and Grid formatting templates and demo templates.

## 2.3.2 - 2018-09-14

### Fixed
- Various bug fixes for Freeform Payments feature.
- Bug fixes and improvements to demo and sample templates to better accommodate Freeform Payments.

## 2.3.1 - 2018-09-13

### Changed
- Updated reCAPTCHA field to only be allowed once per form (disappears from Special fields list when in use).
- Various tweaks and improvements for Freeform Payments compatibility.

## 2.3.0 - 2018-09-12

### Added
- Added ability to set Mailing List fields as hidden fields (automatically opting in users).

### Changed
- Updated for compatibility with future Freeform Payments add-on plugin.
- Number fieldtype is no longer Pro only, and part of Lite edition (in consideration for Freeform Payments plugin).
- Updated CP single submission view to include a note for Mailing List fields that mentions data is not stored for this field type.
- Updated new field creation to error if maximum number of fields are reached.
- Updated Freeform's automatically inserted JS to no longer include `type="text/javascript"`.
- Updated reCAPTCHA settings to be stored in Freeform Lite rather than Pro.
- Updated Element Connections feature to only attempt to fire when it's properly set up (to eliminate form errors if accidentally partially added).
- Improved AJAX script in Demo Templates to better handle script loading and IE11 compatibility.

### Fixed
- Fixed a bug where Salesforce API connections were not holding for more than a day or so.
- Fixed a bug where the Freeform 1.x to 2.x (Craft 2.x to 3.x) migration path could error in some cases.
- Fixed a bug where deleting forms and fields were not returning proper AJAX return statuses.
- Fixed a bug where the Constant Contact integration was not correctly working.
- Fixed a bug where the Dynamic Recipients field as Checkbox OR Radio would default to the first option being checked IF none were selected and form reloaded after an error was triggered.
- Fixed a bug where required asterisks were out of position in CP submission view.
- Fixed a bug where permissions for Manage Export Profiles was not working correctly.

## 2.2.2 - 2018-08-02

### Changed
- Updated Element Connections feature to allow mapping Freeform File Upload fields to the User Photo field.
- Updated SharpSpring integration to work with all custom field types.

### Fixed
- Fixed a bug where the Element Connections feature would display an error for customers using Solo edition.

## 2.2.1 - 2018-07-19

### Changed
- Updated HubSpot integration to load custom fields from Contacts, Companies and Deals endpoints now, not just Deals.
- Updated Composer to display an error if any fields are using the same handle.

### Fixed
- Fixed a bug where some users were getting a 'fieldlayout' table doesn't exist error in Composer.
- Fixed a bug where JS validation for the Phone fieldtype was not working.
- Fixed a bug where admin notification recipients were not being collected when using Windows newlines.

## 2.2.0 - 2018-07-18

> {tip} There are updates in this release that affect the Phone fieldtype and Confirmation fieldtype. Please review change log below for more info.

### Added
- Added ability to map submission data to Craft Entries and Users!
- Added Password fieldtype, meant typically for using with Users connection for registration forms.
- Added Bootstrap 4 example formatting template (to choose inside Composer).
- Added support for mapping to website, URL, dropdown, radio, date and zip fields in Mailchimp integration.
- Added `fieldIdPrefix` parameter to `freeform.form` function to set a prefix value on field output. Helpful if you have more than 1 form on the same template and are sharing fields.

### Changed
- Updated Phone fieldtype (Pro) to now optionally use JS validation for generating the correct format. Breaking change for existing users: change `x` to `0` going forward.
- Updated Confirmation fieldtype to now be a Special field instead, and available for Lite as well. Legacy Confirmation fields will still work, but you should consider switching them out (removing and replacing) with the special field instead.
- Updated Composer interface to now be translatable.
- Updated the default English language file to include all language strings and removed obsolete ones.
- Updated the autogeneration of form handles to only happen for the first naming of the form. After that, it will not change by itself when updating a form name.
- Updated Submission object and submission view in control panel to use LitEmoji to render emojis.

### Fixed
- Fixed several issues with browser compatibility in AJAX demo templates.
- Fixed a bug where CSV exporting from Export Profiles section were not including email field data.
- Fixed a bug where form names weren't showing in Submissions breadcrumbs.
- Fixed a bug where the Composer Tutorial was not lining up properly in Craft 3.0.12+.
- Fixed a bug where the 'Reset' button was showing in property editor on some fields that shouldn't have had it.
- Fixed a bug where the Formatting Template select menu options were not displaying cleaned up version of names in Composer.
- Fixed a bug where some headings in the Property Editor were incorrect.

## 2.1.1 - 2018-07-03

### Fixed
- Fixed a bug where longer form layouts would not scroll vertically in Composer with Craft 3.0.13+.
- Fixed a bug where submitting forms with emojis would result in an error and not correctly store submission.

## 2.1.0 - 2018-06-27

### Added
- Added the ability to move fields from one page to another in multipage forms in Composer.
- Added ability to rearrange pages in multipage forms in Composer.
- Added ability to render Dynamic Recipients as checkboxes, and now allows submitter to select more than 1 option.
- Added ability to easily duplicate forms (from Forms list) and duplicate fields.
- Added 'Reset' button inside Property editor for fields to reset/update them to the defaults set for the 'main' field (Freeform -> Fields area).

### Changed
- Updated Composer UI to match the Craft 3.0.12 UI changes.
- Updated and improved the Forms list control panel page.
- Updated File Upload field exporting to load the full URL path to file fields, or file name only if the Asset preference does not have a public URL.
- Updated Date & Time field date picker to have several more translation options available.
- Updated Demo Templates to no longer install duplicate template routes.
- Updated Demo Templates routes to be extension agnostic.
- Updated the 'Disable Submit Button on Form Submit' setting to be disabled by default.
- Updated plugin icon to be a little more spicy.
- Renamed the 'Save and continue editing' button in Composer to just 'Quick Save'.
- Renamed the 'Include Freeform scripts in the page's footer?' setting to 'Freeform Javascript Insertion Location' with a dropdown for choices.

### Fixed
- Fixed a bug where the Composer Save button was off position if there was a Craft notice at top of page.
- Fixed a bug where the Date & Time fieldtype would incorrectly display time picker when the field was set to Date only.
- Fixed a bug where the Dynamic Recipients field would not show the selected options in Submission object and single submission view in control panel.
- Fixed a bug where the Dynamic Recipients field would not render as Radios in single submission view in control panel if it was set to render that way.
- Fixed a bug where form field options were not being escaped.
- Fixed a bug where some reCAPTCHA files were not correctly named for case sensitivity.
- Fixed a bug where Demo Templates installer was stripping `-` from URI paths.
- Fixed a bug where the "Manage all Submissions" permission was not allowing valid users to delete submissions.

## 2.0.4 - 2018-05-31

### Added
- Added a variety of thorough AJAX examples to demo templates!
- Added `EVENT_BEFORE_RENDER` event for developers.

### Changed
- Updated Field editor and Composer to error if field handles are using a reserved word such as `title` or `id`.

### Fixed
- Fixed a bug where the Craft automated tasks feature would error on because of the Freeform fieldtype being used inside Matrix fields under certain circumstances.
- Fixed a bug where the Salesforce fetch token URL regex restriction was not allowing all types of URLs to pass through.
- Fixed some issues Freeform when using with AJAX.

## 2.0.3 - 2018-05-25

### Changed
- Updated Symfony dependencies to avoid conflicting with other plugins.

## 2.0.2 - 2018-05-24

### Changed
- Updated `hashids` dependency to `^2.0` so Freeform doesn't conflict with other plugins.
- Updated form validation to no longer allow a single space as a valid value for required fields.

### Fixed
- Fixed a bug where the reCAPTCHA feature would error when trying to add to forms for some users.
- Fixed a bug where Categories and Tags Feeders could break due to a JS error when setting the source.
- Fixed a bug where setting the `maxlength` option for text and textarea fields to a value, and then changing back to nothing would result in Freeform not having the field accept no values.

## 2.0.1 - 2018-05-17

### Added
- Added automated Submission Purge feature.
- Added automated Spam Folder Purge feature.
- Added `beforePush` and `afterPush` developer hooks for API integrations.

### Changed
- Updated Composer UI to closer match Craft 3 styling.
- Updated and rearranged options in Form Settings property editor area in Composer.

### Fixed
- Fixed a bug where Craft Campaign mailing list option would show as available to create if you didn't have the plugin installed.

## 2.0.0 - 2018-05-14

### Added
- Added mailing list integration support for the Craft Campaign plugin, available on Lite and Pro editions.

### Fixed
- Fixed a bug where 'Display error messages' option for Spam Protection Behavior setting would trip on submitting a form if it initially errored.
- Fixed a bug where submitting a form with a mailing list would error in some cases.
- Fixed a bug where the Salesforce fetch token URL regex restriction was not allowing for less common URLs to pass through.
- Fixed a bug where saving field mapping for HubSpot was not always saving fields correctly.

## 2.0.0-beta.20 - 2018-05-09

### Added
- Added Form Submission Throttling setting to help prevent against spam or attacks.

### Fixed
- Fixed a bug where the 'Empty Option Label' input would not show when using Element Feeder (but did for Predefined Feeder) for Select fields.
- Fixed a bug where having an option with a value of `0` with Feeders, and an 'Empty Option Label' set, it would select the option with the value of `0` by default, not the Empty Option.

## 2.0.0-beta.19 - 2018-05-07

### Added
- Added optional 'Empty Option Label' input for Select fields that use the Data Feeders feature, so the first option can be `Please Select` or whatever you like.
- Added 'Collect IP Addresses' setting inside Composer form setting area to disable IP address collecting per form.
- Added ability to include collected IP addresses when exporting.

### Changed
- Changed Freeform to store all numeric submission data as strings instead of integers to be more reliable.

### Fixed
- Fixed a bug where the hidden Spam honeypot field label was missing the 'for' attribute.
- Fixed a bug where the Status indicators were incorrect for the Recent Submissions widget.

## 2.0.0-beta.18 - 2018-05-02

### Changed
- Updated Composer to no longer have a default Form Name, and also auto-generate a Form Handle based on the Form Name.

### Fixed
- Fixed a bug where clicking the Settings link from the Plugins list was not rerouted correctly.
- Fixed a bug where the Spam Folder API queue migration did not account for prefixed database tables.
- Fixed a bug where updates could error if you updated Pro before Lite.
- Fixed a bug where fields with numeric options were not always using the correct values.

## 2.0.0-beta.17 - 2018-05-01

### Added
- Added field option Element and Data Feeders for Checkbox group, Radio group, Select and Multi-select fieldtypes. You can now populate these fields with Entries, Categories, Tags, Users, or one of our many predefined options: States, Provinces, Countries, Languages, Number ranges, Year ranges, Months, Days and Days of the Week. Freeform Data Feeders also offer flexible control over formatting and/or which data fills option labels and option values.
- Added Multi-select fieldtype.
- Added Spam Folder feature. Never miss a valid lead again! You can optionally enable this to have submissions flagged as spam (from failed honeypot or blocked keywords/emails/IP addresses) be saved to the database an placed into Freeform's Spam Folder. Submissions can then be reviewed (and optionally edited) and allowed, retroactively generating missed email notifications and passing along of data to API integrations.
- Added Spam Protection options to ban email addresses, keywords and IP addresses. Wildcards are permitted on email addresses and keywords.
- Added reCAPTCHA spam protection integration for Freeform Pro edition.

### Changed
- Updated the Spam Protection Behavior setting to allow one of three options: Simulate Success, Display errors (good for debugging), and Reload form.
- Updated form submissions to collect the IP address of submitters.
- Improved the appearance of the Property Editor in Composer by having instructions for each setting displayed in a tooltip instead.
- Increased the size of image thumbnails when viewing submissions in control panel.
- Updated Forms list in CP to have submission counts link to Submissions area.
- Updated Hidden fields to allow up to 250 characters.

### Fixed
- Fixed an XSS security vulnerability with submitting forms.
- Fixed a bug where searching into Submissions in the CP would return an error.
- Fixed a bug where submitting a form with a single File Upload field without `[]` would error.
- Fixed a bug where the Date & Time fieldtype datepicker path was incorrect.
- Fixed a bug where 'max length' error messages for text and textarea fields were not translatable.
- Fixed a bug where creating and editing statuses would not correctly update the status handle.
- Fixed a bug where Freeform 1 to 2 migration would not correctly update the Form Fieldtype for Craft 3.
- Fixed a bug where exporting odd checkbox data could result in an error.

## 2.0.0-beta.16 - 2018-04-11

### Fixed
- Fixed a bug where Radio group and Checkbox group options were rendering without unique values for the ID attribute by default.
- Fixed a bug where hidden system files would display in Formatting and Email Notification templates lists.
- Fixed a bug where a "Handle Missing" error would display incorrectly for Mailing List fields in Composer layout.
- Fixed a bug where file uploads could sometimes error when viewing submissions.

## 2.0.0-beta.15 - 2018-04-05

### Added
- Added new setting for Salesforce CRM integration for assignment rules in Salesforce.
- Added warnings in Composer to show if a field has a blank handle.

### Changed
- Updated Pipedrive API integration to have USER ID and STAGE ID settings.
- Updated various translations in Composer interface.

### Fixed
- Fixed a bug where email notification templates were not able to be deleted.

## 2.0.0-beta.14 - 2018-04-04

### Fixed
- Fixed a bug where Freeform would error about `Client` constant in Craft 3.0.0 GA release, as the Client edition was removed.

## 2.0.0-beta.13 - 2018-03-27

### Added
- Added a setting for spam protection that allows you to control the behavior of submit return (to simulate a successful submit).
- Added form, field values, submission and notification properties to `SendEmailEvent` object.
- Added improved error logging.

### Fixed
- Fixed a bug where Freeform Lite would show Pro field types in Field editor area.
- Fixed a bug where the 'Save' button in Composer was not in correct position after Craft 3 RC16 update.
- Fixed a bug where new CRM integrations could not be edited or created due to a code error.
- Fixed a bug where Freeform was not fully compatible with PostgreSQL.
- Fixed a bug where Freeform 1 to 2 migration would error on `fileCount`.
- Updated Return URL for forms to default to empty and redirect the user back to where the form was rendered (when left empty).

## 2.0.0-beta.12 - 2018-03-21

### Added
- Added Pipedrive CRM integration for Freeform Pro edition.
- Added new setting for Salesforce CRM integration for accounts using custom URLs in Salesforce.

### Fixed
- Fixed a bug where HTML blocks, submit buttons, and file uploads were being included in the `allFields` array in email notifications.
- Fixed a bug where non-existent Freeform controllers were showing in console command help list.
- Improved the Mailing List integration code in Composer.
- Fixed a bug where translations were not being loaded correctly in control panel (aside from Composer which is unavailable currently).
- Fixed a bug where reinstalling Demo Templates would generate extra duplicate routes.
- Fixed a bug where Freeform 1.x to 2.x migration might not work correctly and error about foreign key drop statements.
- Fixed a bug where the "Manage All Submissions" permission was not granting users and user groups access to Quick Export feature.

## 2.0.0-beta.11 - 2018-03-09

### Changed
- Updated multi-page limit in Composer to 100 pages.

### Fixed
- Fixed a bug where the single submission view page in CP would error for submissions with file uploads from before multiple upload capabilities.
- Fixed a bug where formatting templates were not correctly handling Dynamic Recipients fields as radio options.
- Fixed a bug where `field.label` would only ever render as 'Submit', regardless of any customized value specified in Composer.
- Fixed a bug where the Maximum Length setting for text inputs was not correctly being applied.

## 2.0.0-beta.10 - 2018-03-01

### Added
- Added unique token to all form submissions. Useful if you want to more securely display a users submission data in the front end after they submit the form (with token in the URI). Available as `token` property in Submission object and `token` parameter in `freeform.submissions` function for filtering.
- Added `deleteSubmissionByToken()` function to allow users to delete their own submissions (see demo template example).
- Added _Opt-In Data Storage Checkbox_ option for form settings in Composer to allow users to decide whether the submission data is saved to your site or not (but still sends email notifications). To use it, add a checkbox field to your form and pair the setting with that field. The checkbox will have to be checked to have data stored in Freeform.

### Changed
- Updated File Upload fields to have the ability to accept multiple files.
- Updated Checkbox fieldtype to show a warning in Composer when no value is set.

### Fixed
- Fixed a bug where using Dynamic Recipients fieldtype as Radio display would not send email notifications (reinstall or adjust demo templates).
- Fixed a bug where default value and placeholder attributes were not being saved for Text and Confirmation field types.
- Fixed a bug where a migration was not running correctly.

## 2.0.0-beta.9 - 2018-02-16

### Changed
- Updated Dynamic Recipients fields to allow multiple email addresses per option (separated by commas).

### Fixed
- Fixed a bug where radio fields would not display errors if left empty.
- Fixed a bug where the demo templates errored on submission views after Craft 3 RC 10 update.

## 2.0.0-beta.8 - 2018-02-14

### Fixed
- Fixed a bug where the CP Submissions list page broke after Craft 3 RC 10 update.

## 2.0.0-beta.7 - 2018-02-13

### Added
- Added Dutch translations.

### Changed
- Updated the install and uninstall process to be smarter (Lite vs Pro order, etc).

### Fixed
- Fixed a bug where Export CSV feature for Lite was not respecting the Remove Newlines setting.
- Fixed a bug with user / user group permissions.
- Fixed a bug where dashboard widgets' titles could not be overwritten.
- Fixed a bug where an error on install could sometimes occur.

## 2.0.0-beta.6 - 2018-02-02

### Added
- Added a 'Use Double Opt-in?' setting for Mailchimp integrations.
- Added `onBeforeSubmit` and `onAfterSubmit` events.
- Added an optional `renderSingleInput` method to render single Checkbox fields' input without an additional hidden input.

### Changed
- Changed Mailing List fieldtype `renderInput` to now only output the input field (without a label).

### Fixed
- Fixed a bug where the chart on Submissions list page inside CP was sometimes not displaying new submissions based on timezone.
- Fixed a bug where permissions weren't allowing Admins to change status of submission(s).

## 2.0.0-beta.5 - 2018-01-31

### Fixed
- Fixed a bug where the Freeform 1.x to 2.x (Craft 2.x to 3.x) migration path could error in some cases.
- Fixed a bug where an error could be triggered from the cleanup of unfinalized files.

## 2.0.0-beta.4 - 2018-01-30

### Fixed
- Fixed a bug where Freeform would trigger permission related errors when trying to edit users or user groups.
- Fixed a bug where Freeform would not hide pages from navigation that a user did not have permission access to.
- Fixed various permission issues throughout Freeform.

## 2.0.0-beta.3 - 2018-01-29

### Added
- Added Freeform 1.x to 2.x (Craft 2.x to 3.x) migration path.

### Fixed
- Fixed a bug where Email Notification templates would not update correctly.
- Fixed a bug in Export Profiles view.
- Fixed a bug for sites with databases table prefixes.
- Fixed a bug for some sites with issues installing Freeform.

## 2.0.0-beta.2 - 2018-01-25

### Fixed
- Fixed a bug where Submissions list in control panel would not display any results.
- Fixed a bug where some users encountered install errors/issues.
- Fixed a bug where the Freeform Form element field type would display an error.
- Fixed a bug where the Save button in Composer was sometimes out of position.

## 2.0.0-beta.1 - 2018-01-24

### Added
- Added compatibility for Craft 3.x.
