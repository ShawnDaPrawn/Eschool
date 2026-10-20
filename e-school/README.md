# Student app

Flutter app for students. The Dart package name is `eschool`. Leave that name as it is. Changing it means updating every import in `lib/`.

## Run

```shell
flutter pub get
flutter run
```

## Android release build

```shell
flutter build apk --split-per-abi
```

APKs are written to `build/app/outputs/flutter-apk/`.

## iOS pods

```shell
cd ios
pod install
cd ..
```

## When the toolchain gets stuck

```shell
flutter clean
flutter pub get
```

If iOS pods are stale:

```shell
flutter clean
rm -rf ios/Pods ios/.symlinks ios/Flutter/Flutter.framework ios/Flutter/Flutter.podspec ios/Podfile.lock
flutter pub get
cd ios
pod install
cd ..
```
