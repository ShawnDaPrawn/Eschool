import Flutter
import UIKit
import FirebaseCore
import url_launcher_ios
import CoreNFC
import mobile_scanner
import shared_preferences_foundation

@main
@objc class AppDelegate: FlutterAppDelegate, FlutterImplicitEngineDelegate {
  private var vendorPaymentNfc: VendorPaymentNfc?

  private var isVendorBuild: Bool {
    Bundle.main.bundleIdentifier == "tech.tokensoft.navigateed.vendor"
  }

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
    if isVendorBuild {
      // The Vendor identity has its own camera, opaque recovery storage and NFC
      // bridge. It never initializes teacher services or Firebase.
      if let registrar = engineBridge.pluginRegistry.registrar(forPlugin: "MobileScannerPlugin") {
        MobileScannerPlugin.register(with: registrar)
      }
      if let registrar = engineBridge.pluginRegistry.registrar(forPlugin: "SharedPreferencesPlugin") {
        SharedPreferencesPlugin.register(with: registrar)
      }
      if let registrar = engineBridge.pluginRegistry.registrar(forPlugin: "VendorPaymentNfc") {
        let reader = VendorPaymentNfc()
        vendorPaymentNfc = reader
        FlutterMethodChannel(name: "navigateed/vendor-payment-nfc", binaryMessenger: registrar.messenger())
          .setMethodCallHandler { [weak reader] call, result in reader?.handle(call, result: result) }
      }
      return
    }
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

// Reads the encoded NDEF URI once. SUN, scope, limits and financial authority
// remain server-side; a local tag read never authorizes a debit.
private final class VendorPaymentNfc: NSObject, NFCNDEFReaderSessionDelegate {
  private var session: NFCNDEFReaderSession?
  private var reply: FlutterResult?
  private var timeout: DispatchWorkItem?

  func handle(_ call: FlutterMethodCall, result: @escaping FlutterResult) {
    if call.method == "cancel" {
      finish(nil)
      result(nil)
      return
    }
    guard call.method == "read" else { result(FlutterMethodNotImplemented); return }
    guard reply == nil, NFCNDEFReaderSession.readingAvailable else {
      result(FlutterError(code: "unavailable", message: "Card tap is unavailable. Use the wallet QR or check this device.", details: nil))
      return
    }
    reply = result
    let reader = NFCNDEFReaderSession(delegate: self, queue: nil, invalidateAfterFirstRead: false)
    session = reader
    reader.alertMessage = "Hold the school identity card near this device. Review the amount and photo before confirming."
    let deadline = DispatchWorkItem { [weak self, weak reader] in
      guard let self = self, let reader = reader, self.session === reader else { return }
      self.finish(nil)
    }
    timeout = deadline
    DispatchQueue.main.asyncAfter(deadline: .now() + 20, execute: deadline)
    reader.begin()
  }

  func readerSession(_ session: NFCNDEFReaderSession, didDetectNDEFs messages: [NFCNDEFMessage]) {
    let values = messages.flatMap { $0.records }.compactMap { $0.wellKnownTypeURIPayload()?.absoluteString }
    DispatchQueue.main.async { [weak self] in
      guard let self = self, self.session === session else { return }
      self.finish(values.count == 1 ? values[0] : nil)
    }
  }

  func readerSession(_ session: NFCNDEFReaderSession, didInvalidateWithError error: Error) {
    DispatchQueue.main.async { [weak self] in
      guard let self = self, self.session === session else { return }
      self.finish(nil)
    }
  }

  private func finish(_ value: String?) {
    timeout?.cancel()
    timeout = nil
    let reader = session
    session = nil
    let result = reply
    reply = nil
    reader?.invalidate()
    result?(value)
  }
}
