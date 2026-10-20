import 'package:eschool/ui/widgets/customRoundedButton.dart';
import 'package:eschool/utils/animationConfiguration.dart';
import 'package:eschool/utils/api.dart';
import 'package:eschool/utils/assets.dart';
import 'package:eschool/utils/errorMessageKeysAndCodes.dart';
import 'package:eschool/utils/labelKeys.dart';
import 'package:eschool/utils/uiUtils.dart';
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
                errorMessageCode == ErrorMessageKeysAndCode.noInternetCode
                    ? Assets.noInternetImage
                    : Assets.somethingWentWrongImage,
              ),
            ),
          ],
          SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 8),
            child: Text(
              errorMessageText ??
                  UiUtils.getErrorMessageFromErrorCode(
                    context,
                    ApiException(errorMessageCode ?? ''),
                  ),
              textAlign: TextAlign.center,
              style: TextStyle(
                color:
                    errorMessageColor ??
                    Theme.of(context).colorScheme.secondary,
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
                      Theme.of(context).scaffoldBackgroundColor,
                  buttonTitle: UiUtils.getTranslatedLabel(context, retryKey),
                  showBorder: false,
                )
              : const SizedBox(),
        ],
      ),
    );
  }
}
