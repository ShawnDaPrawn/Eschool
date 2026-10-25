import Flutter
import UIKit
import FirebaseCore

@main
@objc class AppDelegate: FlutterAppDelegate, FlutterImplicitEngineDelegate {
  // This identity comes from the signed native bundle, before Dart starts.
  // Unknown and reserved app identities never initialize teacher services.
  private var isTeacherBuild: Bool {
    Bundle.main.bundleIdentifier == "tech.tokensoft.navigateed.teacher"
  }

  override func application(
    _ application: UIApplication,
    didFinishLaunchingWithOptions launchOptions: [UIApplication.LaunchOptionsKey: Any]?
  ) -> Bool {
    if isTeacherBuild {
      if FirebaseApp.app() == nil {
        FirebaseApp.configure()
      }
      if #available(iOS 10.0, *) {
        UNUserNotificationCenter.current().delegate = self
      }
    }
    return super.application(application, didFinishLaunchingWithOptions: launchOptions)
  }

  func didInitializeImplicitFlutterEngine(_ engineBridge: FlutterImplicitEngineBridge) {
    // Reserved shells need only the Flutter engine to render their unavailable page.
    // Plugin registration can itself initialize native services before Dart runs.
    guard isTeacherBuild else { return }
    GeneratedPluginRegistrant.register(with: engineBridge.pluginRegistry)
  }
}
