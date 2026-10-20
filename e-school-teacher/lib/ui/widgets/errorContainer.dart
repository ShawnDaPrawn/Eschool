import 'package:eschool_teacher/ui/widgets/customRoundedButton.dart';
import 'package:eschool_teacher/utils/animationConfiguration.dart';
import 'package:eschool_teacher/utils/api.dart';
import 'package:eschool_teacher/utils/assets.dart';
import 'package:eschool_teacher/utils/errorMessageKeysAndCodes.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
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

  @override
  Widget build(BuildContext context) {
    return Animate(
      effects: customItemBounceScaleAppearanceEffects(),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
          SizedBox(
            height: MediaQuery.sizeOf(context).height * 0.35,
            child: SvgPicture.asset(
              errorMessageCode == ErrorMessageKeysAndCode.noInternetCode
                  ? Assets.noInternetImage
                  : Assets.somethingWentWrongImage,
            ),
          ),
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
          if (showRetryButton ?? true)
            CustomRoundedButton(
              height: 40,
              widthPercentage: 0.35,
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
              textAlign: TextAlign.center,
            )
          else
            const SizedBox(),
        ],
      ),
    );
  }
}
