import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_svg/svg.dart';

class ErrorContainer extends StatelessWidget {
  const ErrorContainer({
    super.key,
    this.errorMessageCode,
    this.errorMessageText,
    this.errorMessageColor,
    this.errorMessageFontSize,
    this.onTapRetry,
    this.showErrorImage,
    this.retryButtonBackgroundColor,
    this.retryButtonTextColor,
    this.showRetryButton,
    this.animate = true,
  });
  final String? errorMessageCode;
  final String? errorMessageText; //use when not using code
  final bool? showRetryButton;
  final bool? showErrorImage;
  final Color? errorMessageColor;
  final double? errorMessageFontSize;
  final Function? onTapRetry;
  final Color? retryButtonBackgroundColor;
  final Color? retryButtonTextColor;
  final bool animate;

  @override
  Widget build(BuildContext context) {
    return Animate(
      effects: animate ? customItemBounceScaleAppearanceEffects() : null,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (showErrorImage ?? true) ...[
            SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
            SizedBox(
              height: MediaQuery.sizeOf(context).height * 0.35,
              child: SvgPicture.asset(
                errorMessageCode == ApiErrorCodes.noInternet
                    ? SharedAssets.noInternetImage
                    : SharedAssets.somethingWentWrongImage,
              ),
            ),
          ],
          SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 8),
            child: Text(
              errorMessageText ??
                  SharedUiUtils.getErrorMessageFromErrorCode(
                    context,
                    ApiException(errorMessageCode ?? ''),
                  ),
              textAlign: TextAlign.center,
              style: TextStyle(
                color:
                    errorMessageColor ??
                    Theme.of(context).colorScheme.onSurface,
                fontSize: errorMessageFontSize ?? 16,
              ),
            ),
          ),
          const SizedBox(height: 15),
          (showRetryButton ?? true)
              ? CustomRoundedButton(
                  height: 40,
                  widthPercentage: 0.35,
                  textAlign: TextAlign.center,
                  backgroundColor:
                      retryButtonBackgroundColor ??
                      Theme.of(context).colorScheme.primary,
                  onTap: () {
                    onTapRetry?.call();
                  },
                  titleColor:
                      retryButtonTextColor ??
                      Theme.of(context).colorScheme.onPrimary,
                  buttonTitle: SharedUiUtils.getTranslatedLabel(
                    context,
                    retryKey,
                  ),
                  showBorder: false,
                )
              : const SizedBox(),
        ],
      ),
    );
  }
}
