The admin panel also serves the school's public website from the same Laravel application: a home page, About Us, photo and video galleries, Contact Us and an online student registration form. This page explains which routes are public, which controller and views render each page, where the content comes from, how sections are switched on and off and themed, and how the contact form and online registration are processed.

Click-by-click content management is documented in the Docusaurus [Web Pages](https://shawndaprawn.github.io/Eschool/web-page/) section: [Content Settings](https://shawndaprawn.github.io/Eschool/web-page/content-settings/), [Sliders](https://shawndaprawn.github.io/Eschool/web-page/sliders/), [Educational Programs](https://shawndaprawn.github.io/Eschool/web-page/educational-programs/), [News & Events](https://shawndaprawn.github.io/Eschool/web-page/news-events/), [Photos](https://shawndaprawn.github.io/Eschool/web-page/photos/), [Videos](https://shawndaprawn.github.io/Eschool/web-page/videos/), [FAQs](https://shawndaprawn.github.io/Eschool/web-page/faqs/), [Contact Us](https://shawndaprawn.github.io/Eschool/web-page/contact-us/), plus [Student Self Registration](https://shawndaprawn.github.io/Eschool/features/student-registration/).

## Public routes

Everything below is in [`routes/web.php`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/routes/web.php) and needs no login. The site pages are in a `Route::group(['middleware' => 'language'])` (`LanguageManager`, which applies the `locale` stored in the session).

| Method and URL | Route name | Handler | Notes |
|---|---|---|---|
| `GET /` | none | `WebController::index` | Home page (`web/index.blade.php`) |
| `GET /about` | `about.us` | `WebController::about` | `web/about-us.blade.php` |
| `GET /contact` | `contact.us` | `WebController::contact_us` | `web/contact-us.blade.php` |
| `GET /photo` | `photo` | `WebController::photo` | Album list, `web/photos.blade.php` |
| `GET /photo-gallery/{id}` | `photo.gallery` | `WebController::photo_details` | Images of one album, `web/photos-details.blade.php` |
| `GET /video` | `video` | `WebController::video` | `web/videos.blade.php` |
| `POST /contact-us/store` | `contact_us.store` | `WebController::contact_us_store` | `throttle:5,1` (5 per minute per address) |
| `GET /registration` | `student-registration.index` | `WebController::registrationIndex` | `web/registration.blade.php` |
| `POST /student-register` | `student-registration-store` | `WebController::studentRegistration` | `throttle:10,1` |
| `GET /error-page` | `error-page` | `WebController::errorPage` | `web/error-404.blade.php` |
| `GET /web-set-language/{lang}` | `web.set.language` | `LanguageController::set_language` | Stores `locale` and `language` in the session if the code exists in `languages`, then redirects back |
| `GET /privacy-policy`, `GET /terms-conditions` | none | closures | `echo` the `privacy_policy` / `terms_condition` setting as stored (HTML, unescaped). Linked from the site footer; the apps use them too. |
| `GET /common/js/lang` | `common.language.read` | `Controller::readLanguageFile` | `window.languageLabels = <resources/lang/<code>.json>` for the admin JavaScript, falling back to `en.json` |
| `GET /login`, `POST /login`, password-reset routes | `login`, `password.request`, `password.email`, `password.reset`, `password.update` | `Auth\*` controllers | Admin panel login; `password/email` is `throttle:login`. There is no self-registration route. |
| `POST /webhook/{razorpay,stripe,paystack,flutterwave}`, `GET /response/{paystack,flutterwave}/success` | `paystack.success`, `flutterwave.success` | `WebhookController` | Payment gateways; see [Payments](Payments#payment-confirmation-webhooks) |
| `GET /up` | none | Laravel health route | Configured with `health: '/up'` in `bootstrap/app.php` |
| `/install/...` | `install.*` | `dacoto/laravel-wizard-installer` (in `packages/`) | Installer; its `ToInstallMiddleware` is prepended to the `web` group. See [Installation-Wizard](Installation-Wizard). |

Every response also passes through the global `SecurityHeaders` middleware (`X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, HSTS over HTTPS) and the `web` group additions in `bootstrap/app.php`, including `RejectDangerousUploads` for the two forms that accept files. See [Security-Model](Security-Model).

The app APIs that read the same content are public too: `GET /api/sliders`, `GET /api/get-events-list`, `GET` or `POST /api/get-events-details`, `GET /api/holidays`, `GET /api/get-gallery-albums`, `GET /api/get-gallery-images`, `GET /api/get-gallery-videos` (see [API-Overview](API-Overview)).

## Pages and their data

| Page | Controller | Data shown |
|---|---|---|
| Home | `WebController::index` | Sliders with `type` 2 (web) or 3 (both); the `about_us`, `programs` (all `EducationalProgram` rows), `events` (news: upcoming `Event` rows with their `multipleEvent` schedule merged with upcoming `Holiday` rows, sorted by date, first 6), `photos` (all `Media` with `type` 1), `videos` (all `Media` with `type` 2), `faqs` (`Faq` with `status` 1) and `app` (download links) sections; `getSettings()` for everything else |
| About Us | `WebController::about` | `about_us`, `who_we_are`, and the `teacher` section, which lists every `Teacher` with name and image plus the total teacher and student counts |
| Contact Us | `WebController::contact_us` | The `question` section with the contact form, reCAPTCHA widget when enabled, and a map iframe from the `maplink` setting |
| Photos | `WebController::photo` | Albums (`Media` `type` 1) when the `photos` section is on |
| Album | `WebController::photo_details($id)` | All `MediaFile` rows of the album (not gated by the section status) |
| Videos | `WebController::video` | All `Media` `type` 2 (YouTube links); the view renders them only when the `videos` section is on |
| Registration | `WebController::registrationIndex` | Classes with medium and stream, active student categories, the dynamic student fields, a preview admission number and today's date |

The layout is `web/master.blade.php` with `web/header.blade.php` and `web/footer.blade.php`. The master layout queries `web_settings` itself for the navigation flags, so the header and footer only show About Us, Photos, Videos, Contact Us and Registration links when those sections are enabled. The page title is `config('app.name')`.

### Models

| Model (table) | Columns | Managed in |
|---|---|---|
| `WebSetting` (`web_settings`) | `name`, `tag` (section label), `heading`, `content` (500), `image`, `status` (1 shown, 0 hidden). `image` accessor returns a full URL. | Web Settings > Content Settings |
| `Slider` (`sliders`) | `image`, `type` (1 app, 2 web, 3 both) | Sliders |
| `EducationalProgram` (`educational_programs`) | `title`, `image` | Web Settings > Educational Program |
| `Faq` (`faqs`) | `question`, `answer` (1024 each), `status` | Web Settings > FAQs |
| `ContactUs` (`contact_us`) | `first_name`, `last_name`, `email`, `phone`, `message` (1024), `date` | Filled by the public form; read in Web Settings > Contact Us |
| `Media` (`medias`) | `name`, `type` (1 image album, 2 video), `thumbnail`, `youtube_url`, `session_year_id`; appends `embeded_url` (`embedUrl` and `thumbnailUrl` built from the YouTube id) | Gallery > Photos / Videos |
| `MediaFile` (`media_files`) | `media_id` (FK, cascade), `file_url` | Images inside an album |
| `Event`, `MultipleEvent`, `Holiday` | see [Admin-Modules](Admin-Modules#events) | Events, Holiday List |

The `web_settings` rows are seeded by migrations, not created in the UI: `about_us`, `who_we_are`, `teacher`, `events`, `programs`, `photos`, `videos`, `faqs`, `app`, `question` (in `2024_02_02_111843_create_all_tables.php`, with placeholder text and image paths `websettings/content/aboutus.png` and `whoweare.png`) and `registration` (in `2024_09_05_121951_create_3.3.0_version.php`). The `status` column was added by `2024_03_12_135926_add_column_type_in_events_table.php` with default 1. The seeded image files are not in the repository, so a fresh install shows broken About/Who-we-are images until new ones are uploaded.

[`GalleryService`](https://github.com/ShawnDaPrawn/Eschool/blob/main/admin-panel/app/Services/GalleryService.php) formats albums (`id`, `title`, `thumbnail_url`), album images and video URLs for the app APIs (`ApiController::getGalleryAlbums/Images/Videos`, paginated with `per_page` 1-100) and the student and teacher dashboards (`getGallery()`, five of each). The website views query `Media`/`MediaFile` directly and do not use it.

## Turning the site and its sections on and off

There is no setting that disables the whole website: `GET /` always renders the home page once the application is installed. What can be controlled:

- **Sections.** Each `web_settings` row has a `status`. Content Settings (`WebSettingController::content_update_bulk` / `content_update`) saves `tag`, `heading`, `content`, `status` and an optional image (stored under `storage/app/public/websettings/content/`). A section with `status` 0 is skipped by the pages, its navigation links disappear, and on the home page the corresponding query is not run.
- **Contact page.** With the `question` section off, `contact-us.blade.php` renders nothing inside the layout; the `POST /contact-us/store` route still accepts submissions.
- **Online registration.** With the `registration` section off, `GET /registration` redirects to `/error-page`. `POST /student-register` does not check the section status.
- **App download block.** The `app` section uses the `app_link`, `ios_app_link`, `teacher_app_link` and `teacher_ios_app_link` settings from App Settings.

## Theming and school details

All values come from the `settings` table (General Settings page, `SettingController::update`, permission `setting-create`). They are read on each request, so they work with cached config; since commit `e13ac7d` nothing is copied into `.env`.

| Setting | Where it appears |
|---|---|
| `theme_color`, `secondary_color` | `web/footer.blade.php` writes them as the CSS variables `--primary-color` and `--secondary-color1` (`secondary_color` is seeded as `#38A3A5`) |
| `logo1` | Header and footer logo via `schoolLogoUrl('logo1')`, falling back to `public/assets/logo.svg` |
| `favicon` | `<link rel="shortcut icon">` via `schoolSetting('favicon')` in `web/master.blade.php` and `web/error-404.blade.php` |
| `school_name` | `config('app.name')`: `SettingsConfigService` copies `school_name` onto `app.name` at boot. Used for the page title and the footer copyright (linked to `config('app.url')`) |
| `school_email`, `school_phone`, `school_address` | Header contact bar and footer (the views fall back to placeholder text when unset) |
| `facebook`, `instagram`, `linkedin` | Social links in header and footer |
| `maplink` | `src` of the Contact Us map iframe |
| `recaptcha_status`, `recaptcha_site_key`, `recaptcha_secret_key` | reCAPTCHA on the contact and registration forms |

Languages: the header has a language switcher listing every row of `languages` (`get_language()`); choosing one calls `web.set.language`. If the session language has `is_rtl`, `web/master.blade.php` sets `dir="rtl"` on `<html>`. See [Localization](Localization).

Customising the look beyond these settings means editing `resources/views/web/*.blade.php` and the CSS they load.

## Contact form

`WebController::contact_us_store`:

1. Validates `first_name`, `last_name`, `email` (email), `phone`, `message` (all required).
2. `reCaptcha()` checks `g-recaptcha-response` with Google (`https://www.google.com/recaptcha/api/siteverify`) only when the `recaptcha_status` setting is 1 and `recaptcha_secret_key` is set. The flag and secret come from settings, never from the request, so a client cannot switch the check off; when enabled but unconfigured the check is skipped.
3. Saves a `ContactUs` row with the current date. No email is sent to the school.

Staff read and answer messages at **Web Settings > Contact Us** (`WebSettingController`, permission `contact-us`): `GET contact-us/web` (`contact_us.index`), the list JSON `GET contact-us-list` (`contact_us.show`, search on id only), `POST contact-us/reply/{id}` (`contact_us.reply`) which emails the reply (with optional attachments) to the posted address using `web_settings/testmail.blade.php`, sender `config('mail.from.address')` and `config('app.name')`, and `DELETE contact-us/delete/{id}`. See [Notifications](Notifications#which-emails-are-sent).

## Online registration

`WebController::studentRegistration` creates inactive accounts for a family applying online:

1. Validates the student fields (`first_name`, `last_name`, optional `mobile` of 7-16 digits, `image` jpeg/png up to 2 MB, `dob`, `class_id`, `category_id`, `admission_date`, addresses) and `parent_guardian_type` (`Parent` or `Guardian`) with the matching father/mother or guardian fields; dates of birth must be `d-m-Y`.
2. Checks parent emails for conflicts before verifying reCAPTCHA (Google invalidates a token on its first check, so a resubmission after fixing an unrelated field would otherwise fail; the JSON carries `recaptcha_error: true` so the page resets the widget).
3. Generates the admission number on the server: the current session year's name followed by the last student id + 1 (soft-deleted students included). The form only shows a preview.
4. Creates `users` rows with `status` 0 for each new parent (role `Parent`) and the student (role `Student`, the admission number as login "email"), with the date of birth (`ddmmyyyy`) as the initial password; existing parents with the same email are reused. Images are stored under `storage/app/public/parents/` and `students/`.
5. Saves dynamic fields (see below) into `students.dynamic_fields` as JSON (uploaded files under `student/` on the public disk) and creates the `students` row with `application_type = 'online'`, `class_id`, and no class section.
6. A duplicate-email race ending in a unique-key error returns "The email has already been taken."; other errors return the generic message without exception details.

Dynamic fields: the public form renders and saves the fields defined for **Student** (`form_fields.for` = 1). The admin Online Registrations page loads the fields defined as **Self Student Registration** (`for` = 4).

Staff then review applications in **Students > Online Registrations** (`Student\OnlineRegistrationController`): `GET online-registration` (`online-registration.index`, permission `online-registration-list`), `GET online-registration-list`, and `POST update-active-status` (`update-active-status`, permission `student-edit`). Accepting (`status` 1) assigns a class section, activates the user, creates the current `student_sessions` row and emails the parents their credentials (template `student_registration`); rejecting sets the user's `status` to 2 and emails the `student_application_regect` template. `DELETE permanent-delete/{id}` (permission `student-delete`) removes an application. See [Notifications](Notifications#which-emails-are-sent).

## Content management in the admin panel

| Sidebar item | Routes | Controller | Views | Permissions |
|---|---|---|---|---|
| Sliders | resource `sliders` (except `create`, `edit`) | `SliderController` | `sliders/index.blade.php` | `slider-list` / `slider-create` / `slider-edit` / `slider-delete`; image jpeg/png up to 2 MB, `type` 1/2/3 |
| Events | resource `events`, `DELETE multiple-event/{id}`, `GET view-schedule/{id}`, `POST events-update` (`events.update-schedules`) | `EventController` | `events/` | `event-list` / `event-create` / `event-edit` / `event-delete` |
| Holiday List | resource `holiday`, `holiday-list`, `holiday-view` | `HolidayController` | `holiday/` | `holiday-list` / `holiday-create` / `holiday-edit` / `holiday-delete` |
| Gallery > Photos | `photos` (`photo.index`), `photos/store`, `photos-list`, `photos/edit/{id}`, `photos/update`, `photos/delete/{id}`, `image/update`, `image/delete/{id}` | `MediaController` | `web_settings/photos.blade.php`, `edit_photos.blade.php` | `media-create` (pages, add), `media-list` (list JSON), `media-edit`, `media-delete`. An album needs a name, a png/jpg thumbnail and images (jpeg/png up to 2 MB each), stored under `storage/app/public/gallery/` |
| Gallery > Videos | `videos-index` (`video.index`), `videos/store`, `videos-list`, `videos/update/{id}`, `videos/delete/{id}` | `MediaController` | `web_settings/videos.blade.php` | same; `youtube_url` validated by `App\Rules\YouTubeUrl` |
| Web Settings > Content Settings | `content` (`content.index`), `content-bulk-update`, `content/{id}` | `WebSettingController` | `web_settings/content_settings.blade.php` | `content-create` (page), `content-edit` (save) |
| Web Settings > Educational Program | `educational-program`, `educational-program/store`, `educational-program-list`, `educational-program/update`, `educational-program/delete{id}` | `WebSettingController` | `web_settings/educational_program.blade.php` | `program-list` / `program-create` / `program-edit` / `program-delete`; image png/jpg/svg/jpeg required |
| Web Settings > FAQs | `faq`, `faq/store`, `faq-list`, `faq/update/{id}`, `faq/delete/{id}` | `WebSettingController` | `web_settings/faq.blade.php` | `faq-list` / `faq-create` / `faq-edit` / `faq-delete` |
| Web Settings > Contact Us | see [Contact form](#contact-form) | `WebSettingController` | `web_settings/contact_us.blade.php` | `contact-us` |

The sidebar shows Web Settings for users with any of `content-create`, `program-create`, `faq-create`, `contact-us`, and Gallery for `media-create`. Note that `contact-us` also opens the unrelated **System Settings > Contact Us** page (the app's contact text, `SettingController::contact_us_index`). The educational program delete URL really is `educational-program/delete{id}` (no slash). Full module reference: [Admin-Modules](Admin-Modules).

## See also

- [Admin-Modules](Admin-Modules)
- [Configuration](Configuration)
- [Notifications](Notifications)
- [Localization](Localization)
- [Security-Model](Security-Model)
- [API-Overview](API-Overview)
- [Documentation-Site](Documentation-Site)
