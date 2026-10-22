---
sidebar_position: 5
---

# Integrate with Admin Panel

The app reads your admin panel's address when it is built, so you do not edit any code. Each app has its settings in `config/production.json` (and `config/staging.json` for a test server):

```json
{
  "API_BASE_URL": "https://your-admin-panel.example.com",
  "ENV": "production",
  "FLAVOR": "learner"
}
```

Put your admin panel's address in `API_BASE_URL`, without a `/` at the end (`FLAVOR` is `teacher` in the teacher app). Then run or build the app with that file:

```bash
flutter run --dart-define-from-file=config/production.json
flutter build apk --release --dart-define-from-file=config/production.json
```

For a panel running on your own computer, use `--dart-define=API_BASE_URL=http://10.0.2.2:8000 --dart-define=ENV=local` on the Android emulator. A build without these settings uses the NavigateEd production server.
