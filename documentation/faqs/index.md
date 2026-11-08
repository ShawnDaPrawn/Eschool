# Frequently Asked Questions (FAQs)
Popular questions about eSchool to help you get started!

## How to Fix Image Issues
If uploaded images don't show, the link from the admin panel's `public/storage` folder to its storage is missing. The NavigateEd server image creates it every time it starts. On a server you run yourself, open a terminal in the admin panel folder, delete the existing `public/storage` link and run `php artisan storage:link`. (The old `your-admin-url/storage-link` address has been removed.)

## What is the default password?
- **For Students**: There is no default password. Each parent or guardian receives an email with a link to choose the learner's password; for families without email, the school prints a sign-in sheet with a one-time set-up code (see [Learner sign-in](/features/student-management#learner-sign-in)). Learners sign in with their GR number.

- **For Parents**: A new account receives a random temporary password or an invitation to choose one. If you have no invitation or temporary password, choose **Forgot Password** using your registered email address. A temporary password must be changed before accessing school records.

- **For Teachers and Staff**: Choose **Forgot Password** using your registered email address, then open the reset email to choose your password. The link expires and can be used only once. There is no date-of-birth password.

Accounts still using a date-of-birth password must change it before accessing school records. New passwords need at least 10 characters with letters and numbers, and passwords found in known data breaches are refused.

## Why is the application calendar not showing any events?
The calendars in the application display months based on the current session year, which can be set from the admin panel: System Settings -> General Settings -> Session Year. If the current date is outside the session year, the calendars will only show the current month with today highlighted, but no events. To fix this issue, select the correct session year or create a new one from the admin panel's Session Year screen.

## Compatibility Requirements
To ensure optimal performance of the eSchool system, please follow the compatibility guidelines below:

### Server Requirements
- **Hosting Type**: Compatible with both Shared Hosting and VPS servers
- **Recommended Hosting Provider**: Hostinger

### Backend Compatibility
- **PHP Version**: 8.3
- **Framework**: Laravel 12

### Flutter App Compatibility
- **Flutter Version**: 3.44
