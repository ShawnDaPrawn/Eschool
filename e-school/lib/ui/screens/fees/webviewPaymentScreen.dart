import 'package:eschool/ui/widgets/customAppbar.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';

class WebViewPaymentScreen extends StatefulWidget {

  const WebViewPaymentScreen({required this.paymentUrl, super.key});
  final String paymentUrl;

  @override
  State<WebViewPaymentScreen> createState() => _WebViewPaymentScreenState();

  static Route route(RouteSettings routeSettings) {
    final arguments = routeSettings.arguments! as Map<String, dynamic>;
    return CupertinoPageRoute(
      builder: (_) =>
          WebViewPaymentScreen(paymentUrl: arguments['paymentURL'] ?? ''),
    );
  }
}

class _WebViewPaymentScreenState extends State<WebViewPaymentScreen> {
  late final WebViewController _controller;

  DateTime? currentBackPressTime;

  double opacity = 0;

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
          onNavigationRequest: (final request) {
            if (request.url.contains('paystack') ||
                request.url.contains('flutterwave')) {
              final url = request.url;
              if ((request.url.contains('flutterwave') &&
                      url.contains('status=successful')) ||
                  (request.url.contains('paystack') &&
                      url.contains('success'))) {
                Navigator.pop(context, true);
                return NavigationDecision.prevent;
              } else if (url.contains('failure')) {
                Navigator.pop(context, false);
                return NavigationDecision.prevent;
              }
            }
            return NavigationDecision.navigate;
          },
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
