import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/ui/widgets/customAppbar.dart';
import 'package:eschool/utils/constants.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';

class WebViewPaymentScreen extends StatefulWidget {

  const WebViewPaymentScreen({required this.paymentUrl, super.key});
  final String paymentUrl;

  @override
  State<WebViewPaymentScreen> createState() => _WebViewPaymentScreenState();

  static Route route(RouteSettings routeSettings) {
    return AdaptivePageRoute(
      builder: (_) =>
          WebViewPaymentScreen(
            paymentUrl: routeSettings.arguments as String? ?? '',
          ),
    );
  }
}

class _WebViewPaymentScreenState extends State<WebViewPaymentScreen> {
  //Pages of the school's own server that the payment gateways send the payer
  //back to when the payment is done (see routes/web.php of the admin panel)
  static const String _paystackCallbackPath = '/response/paystack/success';
  static const String _flutterwaveCallbackPath =
      '/response/flutterwave/success';

  static final String _apiHost = Uri.parse(baseUrl).host.toLowerCase();

  late final WebViewController _controller;

  DateTime? currentBackPressTime;

  double opacity = 0;

  bool _hasFinishedPayment = false;

  //true: paid, false: failed or cancelled,
  //null: unknown, leave it to the gateway's webhook
  void _finishPayment(bool? isPaymentSuccessful) {
    if (_hasFinishedPayment || !mounted) {
      return;
    }
    _hasFinishedPayment = true;
    Navigator.pop(context, isPaymentSuccessful);
  }

  NavigationDecision _onNavigationRequest(NavigationRequest request) {
    //Frames inside the payment page (e.g. the bank's card verification page)
    //are left to the gateway, only the page itself is checked
    if (!request.isMainFrame) {
      return NavigationDecision.navigate;
    }

    final Uri? uri = Uri.tryParse(request.url);

    //Do not let the payment page open other apps, local files or scripts
    if (uri == null || !(uri.isScheme('https') || uri.isScheme('http'))) {
      return NavigationDecision.prevent;
    }

    //Only the callback pages on the school's own server end the payment
    if (uri.host.toLowerCase() != _apiHost) {
      return NavigationDecision.navigate;
    }

    if (uri.path.endsWith(_paystackCallbackPath)) {
      //Paystack sends the payer to its callback once the payment is complete
      _finishPayment(true);
      return NavigationDecision.prevent;
    }

    if (uri.path.endsWith(_flutterwaveCallbackPath)) {
      final String status = (uri.queryParameters['status'] ?? '')
          .toLowerCase();
      if (status == 'successful') {
        _finishPayment(true);
      } else if (status == 'cancelled' || status == 'failed') {
        _finishPayment(false);
      } else {
        _finishPayment(null);
      }
      return NavigationDecision.prevent;
    }

    return NavigationDecision.navigate;
  }

  @override
  void initState() {
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setNavigationDelegate(
        NavigationDelegate(
          onPageFinished: (_) {
            setState(() {
              opacity = 1.0;
            });
          },
          onProgress: (progress) {
            if (progress == 100 && opacity != 1) {
              setState(() {
                opacity = 1.0;
              });
            } else {
              setState(() {
                setState(() {
                  opacity = progress / 100;
                });
              });
            }
          },
          onNavigationRequest: _onNavigationRequest,
        ),
      )
      ..loadRequest(Uri.parse(widget.paymentUrl));
    super.initState();
  }

  Widget _buildAppBar() {
    return Align(
      alignment: Alignment.topCenter,
      child: CustomAppBar(
        title: UiUtils.getTranslatedLabel(context, feePaymentKey),
        onPressBackButton: () {
          final DateTime now = DateTime.now();
          if (currentBackPressTime == null ||
              now.difference(currentBackPressTime!) >
                  const Duration(seconds: 2)) {
            currentBackPressTime = now;
            UiUtils.showCustomSnackBar(
              context: context,
              errorMessage: UiUtils.getTranslatedLabel(
                context,
                doNotPressBackWhilePaymentAndDoubleTapBackButtonToExitKey,
              ),
              backgroundColor: UiUtils.getColorScheme(context).error,
            );
            return Future.value(false);
          }
          Navigator.pop(context, false);
          return Future.value(true);
        },
      ),
    );
  }

  @override
  Widget build(final BuildContext context) => PopScope(
        canPop: false,
        onPopInvokedWithResult: (didPop, dynamic result) {
          if (didPop) {
            return;
          } else {
            final now = DateTime.now();
            if (currentBackPressTime == null ||
                now.difference(currentBackPressTime!) >
                    const Duration(seconds: 2)) {
              currentBackPressTime = now;
              UiUtils.showCustomSnackBar(
                context: context,
                errorMessage: UiUtils.getTranslatedLabel(
                  context,
                  doNotPressBackWhilePaymentAndDoubleTapBackButtonToExitKey,
                ),
                backgroundColor: UiUtils.getColorScheme(context).error,
              );
              return;
            }
            Navigator.pop(context, false);
          }
        },
        child: Scaffold(
          body: Stack(
            children: [
              Padding(
                padding: EdgeInsets.only(
                  top: UiUtils.getScrollViewTopPadding(
                    context: context,
                    appBarHeightPercentage:
                        UiUtils.appBarSmallerHeightPercentage,
                    keepExtraSpace: false,
                  ),
                ),
                child: Stack(
                  children: [
                    AnimatedOpacity(
                      duration: const Duration(seconds: 1),
                      opacity: opacity,
                      child: WebViewWidget(
                        controller: _controller,
                      ),
                    ),
                    if (opacity != 1)
                      const Center(
                        child: CircularProgressIndicator(),
                      ),
                  ],
                ),
              ),
              _buildAppBar(),
            ],
          ),
        ),
      );
}
