package tech.tokensoft.navigateed.teacher

import android.nfc.NfcAdapter
import android.nfc.tech.Ndef
import android.os.Handler
import android.os.Looper
import io.flutter.embedding.android.FlutterActivity
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.plugin.common.MethodChannel

class MainActivity : FlutterActivity() {
    private val handler = Handler(Looper.getMainLooper())
    private var tapGeneration = 0
    private var pendingTap: MethodChannel.Result? = null
    private var paymentChannel: MethodChannel? = null
    private var timeout: Runnable? = null

    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)
        if (packageName != "tech.tokensoft.navigateed.vendor") return
        paymentChannel = MethodChannel(flutterEngine.dartExecutor.binaryMessenger, "navigateed/vendor-payment-nfc")
        paymentChannel?.setMethodCallHandler { call, result ->
            when (call.method) {
                "cancel" -> { finishTap(null); result.success(null) }
                "read" -> {
                    val adapter = NfcAdapter.getDefaultAdapter(this)
                    if (pendingTap != null || adapter == null || !adapter.isEnabled) {
                        result.error("unavailable", "Card tap is unavailable. Check this device or use the wallet QR.", null)
                    } else {
                        pendingTap = result
                        val generation = ++tapGeneration
                        timeout = Runnable { if (generation == tapGeneration) finishTap(null) }.also { handler.postDelayed(it, 20000) }
                        try {
                            adapter.enableReaderMode(this, { tag ->
                                var value: String? = null
                                val ndef = Ndef.get(tag)
                                try {
                                    ndef?.connect()
                                    value = ndef?.ndefMessage?.records?.mapNotNull { it.toUri()?.toString() }?.singleOrNull()
                                } catch (_: Exception) {
                                    // A partial hardware read is not financial authority.
                                } finally {
                                    try { ndef?.close() } catch (_: Exception) { }
                                }
                                runOnUiThread { if (generation == tapGeneration) finishTap(value) }
                            }, NfcAdapter.FLAG_READER_NFC_A or NfcAdapter.FLAG_READER_NFC_B, null)
                        } catch (_: Exception) { finishTap(null) }
                    }
                }
                else -> result.notImplemented()
            }
        }
    }

    private fun finishTap(value: String?) {
        tapGeneration++
        timeout?.let { handler.removeCallbacks(it) }
        timeout = null
        val reply = pendingTap
        pendingTap = null
        try { NfcAdapter.getDefaultAdapter(this)?.disableReaderMode(this) } catch (_: Exception) { }
        reply?.success(value)
    }

    override fun onPause() {
        finishTap(null)
        super.onPause()
    }

    override fun onDestroy() {
        finishTap(null)
        paymentChannel?.setMethodCallHandler(null)
        super.onDestroy()
    }
}
