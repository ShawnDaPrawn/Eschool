import 'package:flutter/foundation.dart';
import 'package:package_info_plus/package_info_plus.dart';

/// The force-update check both apps run on their home screen when the
/// server's app settings have `force_app_update` (or
/// `teacher_force_app_update`) set to `1`. The rules are the apps' own,
/// unchanged; read them before setting a version in the admin panel.
// ignore: avoid_classes_with_only_static_members
abstract final class ForceUpdate {
  /// Whether the installed build is older than [requiredVersion], the store
  /// version the server asks for (`x.y.z` or `x.y.z+build`).
  ///
  /// - An empty [requiredVersion] never asks for an update.
  /// - The `x.y.z` parts are compared one position at a time, and any
  ///   position where the server's number is larger asks for an update, so
  ///   a server value of `3.4.1` asks a `3.5.0` install to update. Set the
  ///   server value to exactly the released version.
  /// - When both sides have a `+build`, a larger server build also asks for
  ///   an update, whatever the version part says.
  /// - Every part must be a whole number; anything else throws, and the
  ///   apps then show no dialog.
  static bool isRequired({
    required String installedVersion,
    required String requiredVersion,
  }) {
    if (requiredVersion.isEmpty) {
      return false;
    }

    final bool updateBasedOnVersion = _shouldUpdateBasedOnVersion(
      installedVersion.split('+').first,
      requiredVersion.split('+').first,
    );

    if (requiredVersion.split('+').length == 1 ||
        installedVersion.split('+').length == 1) {
      return updateBasedOnVersion;
    }

    final bool updateBasedOnBuildNumber = _shouldUpdateBasedOnBuildNumber(
      installedVersion.split('+').last,
      requiredVersion.split('+').last,
    );

    return updateBasedOnVersion || updateBasedOnBuildNumber;
  }

  /// [isRequired] against the installed build (`version+buildNumber` from
  /// the platform). A web build is always the latest one served, so it never
  /// asks for an update.
  static Future<bool> check(String requiredVersion) async {
    if (kIsWeb || requiredVersion.isEmpty) {
      return false;
    }
    return isRequired(
      installedVersion: await installedVersion(),
      requiredVersion: requiredVersion,
    );
  }

  static Future<String> installedVersion() async {
    final PackageInfo packageInfo = await PackageInfo.fromPlatform();
    return '${packageInfo.version}+${packageInfo.buildNumber}';
  }

  /// The server value meant for this platform: the iOS one on iOS, the
  /// Android one elsewhere, nothing on the web (where there is no store).
  static String forPlatform({required String android, required String ios}) {
    if (kIsWeb) {
      return '';
    }
    return defaultTargetPlatform == TargetPlatform.iOS ? ios : android;
  }

  static bool _shouldUpdateBasedOnVersion(
    String currentVersion,
    String updatedVersion,
  ) {
    final List<int> currentVersionList = currentVersion
        .split('.')
        .map((e) => int.parse(e))
        .toList();
    final List<int> updatedVersionList = updatedVersion
        .split('.')
        .map((e) => int.parse(e))
        .toList();

    if (updatedVersionList[0] > currentVersionList[0]) {
      return true;
    }
    if (updatedVersionList[1] > currentVersionList[1]) {
      return true;
    }
    if (updatedVersionList[2] > currentVersionList[2]) {
      return true;
    }

    return false;
  }

  static bool _shouldUpdateBasedOnBuildNumber(
    String currentBuildNumber,
    String updatedBuildNumber,
  ) {
    return int.parse(updatedBuildNumber) > int.parse(currentBuildNumber);
  }
}
