import Flutter
import UIKit
import FirebaseCore
import url_launcher_ios

@main
@objc class AppDelegate: FlutterAppDelegate, FlutterImplicitEngineDelegate {
  // This identity comes from the signed native bundle, before Dart starts.
  // Unknown and reserved app identities never initialize teacher services.
  private var isTeacherBuild: Bool {
    Bundle.main.bundleIdentifier == "tech.tokensoft.navigateed.teacher"
  }

  private var isFacilitatorBuild: Bool {
    Bundle.main.bundleIdentifier == "tech.tokensoft.navigateed.facilitator"
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
    // The facilitator registers only its required, non-Firebase URL handler.
    // Full generated registration remains exclusive to the teacher identity.
    if isFacilitatorBuild {
      if let registrar = engineBridge.pluginRegistry.registrar(forPlugin: "URLLauncherPlugin") {
        URLLauncherPlugin.register(with: registrar)
      }
      return
    }
    guard isTeacherBuild else { return }
    GeneratedPluginRegistrant.register(with: engineBridge.pluginRegistry)
  }
}
