---
sidebar_position: 15
---

# Generate Release Version

## Android Release

### Create Key Store File

1. Generate a keystore file using the following command:
   ```bash
   keytool -genkey -v -keystore your-keystore-file.jks -keyalg RSA -keysize 2048 -validity 10000 -alias your-alias
   ```

   - Replace "your-keystore-file.jks" with your preferred filename (keep .jks extension)
   - Replace "your-alias" with your preferred alias
   - Set a password when prompted (characters won't be visible)
   - Press Enter to skip optional fields

2. Create `key.properties` in the `android` folder with:
   ```
   storePassword=[your-password-from-previous-step]
   keyPassword=[your-password-from-previous-step]
   keyAlias=[your-alias-from-previous-step]
   storeFile=[your-keystore-file-location]
   ```

### Generate Release Build

For APK:
```bash
flutter build apk
```

For App Bundle (Play Store):
```bash
flutter build appbundle
```

![Generate Key Store](../static/images/app/generatKeyStorefile.png)

## iOS Release

Follow the official Flutter documentation for iOS deployment:
[Flutter iOS Deployment Guide](https://flutter.dev/docs/deployment/ios)

## Additional Resources

For more detailed information:
- Android deployment: [Flutter Android Deployment](https://flutter.dev/docs/deployment/android)
- iOS deployment: [Flutter iOS Deployment](https://flutter.dev/docs/deployment/ios)