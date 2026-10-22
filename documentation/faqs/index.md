# Frequently Asked Questions (FAQs)
Popular questions about eSchool to help you get started!

## How to Fix Image Issues
If uploaded images don't show, the link from the admin panel's `public/storage` folder to its storage is missing. The NavigateEd server image creates it every time it starts. On a server you run yourself, open a terminal in the admin panel folder, delete the existing `public/storage` link and run `php artisan storage:link`. (The old `your-admin-url/storage-link` address has been removed.)

## What is the default password?
- **For Students**: The default password is the student's date of birth in the format ddmmyyyy. Note: Student credentials will be sent to their respective parent's email.

- **For Parents**: The default password is the parent's date of birth in the format ddmmyyyy. Note: Parent credentials will be sent to their respective email.

- **For Teachers**: The default password is the teacher's date of birth in the format ddmmyyyy. Note: Teacher credentials will be sent to their respective email.

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
