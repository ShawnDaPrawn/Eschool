---
sidebar_position: 16
---

# Web Version

Both apps also run in a web browser, on a laptop, desktop or tablet. Learners and parents use the student app at **https://app.navigateed.co.za**; teachers use the teacher app at **https://teach.navigateed.co.za**. They sign in with the same details as in the phone apps.

## Open the app in a browser

1. Open Chrome, Edge, Firefox or Safari and go to the address above for your app.
2. Sign in: learners with their GR number and password, parents and teachers with their email and password.
3. Use the menu on the left of the screen (on a narrow window, the bar at the bottom) to move around, as in the phone app.

To keep the app one click away, use your browser's **Install app** or **Add to Home Screen** option.

## What works differently in a browser

- **Downloads:** ID cards, fee receipts, results and the academic calendar are saved by the browser to your Downloads folder. Study material files open in a new tab.
- **Attaching files and paying fees:** adding files (assignments, chat, leave requests, announcements, study materials) and paying fees show "This works in the NavigateEd app on your phone for now." Use the phone app for these.
- **Notifications:** the browser does not show push notifications. Open **Notifications** in the app to see them. Signing in on the web does not stop notifications on your phone.

## Build and publish the web version

The web builds come from the same code as the phone apps. From the app's folder (`e-school` or `e-school-teacher`):

```bash
flutter pub get
flutter build web --release --dart-define-from-file=config/production.json
```

The site is in `build/web`. It is published to Firebase Hosting with `firebase deploy --only hosting`, after building both apps (the `app` and `teach` targets in `firebase.json`). The server's address comes from `config/production.json` (or `config/staging.json`), and the server must allow the web addresses (CORS).
