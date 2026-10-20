import 'package:eschool_teacher/ui/widgets/customRoundedButton.dart';
import 'package:eschool_teacher/utils/animationConfiguration.dart';
import 'package:eschool_teacher/utils/assets.dart';
import 'package:eschool_teacher/utils/labelKeys.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_svg/flutter_svg.dart';

class NoDataContainer extends StatelessWidget {
  const NoDataContainer({
    required this.titleKey, super.key,
    this.textColor,
    this.onTapRetry,
    this.retryButtonBackgroundColor,
    this.retryButtonTextColor,
    this.showRetryButton,
    this.subtitleKey,
    this.imageSize,
  });
  final Color? textColor;
  final String titleKey;
  final String? subtitleKey;
  final Function? onTapRetry;
  final Color? retryButtonBackgroundColor;
  final Color? retryButtonTextColor;
  final bool? showRetryButton;
  final double? imageSize;

  @override
  Widget build(BuildContext context) {
    return Animate(
      effects: customItemBounceScaleAppearanceEffects(),
      child: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (imageSize == null)
              SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
            SizedBox(
              height: imageSize ?? MediaQuery.sizeOf(context).height * 0.35,
              child: SvgPicture.asset(Assets.fileNotFoundImage),
            ),
            SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 15),
              child: Text(
                UiUtils.getTranslatedLabel(context, titleKey),
                textAlign: TextAlign.center,
                style: TextStyle(
                  color: textColor ?? Theme.of(context).colorScheme.secondary,
                  fontWeight: FontWeight.w600,
                  fontSize: 16,
                ),
              ),
            ),
            if (subtitleKey != null)
              Padding(
                padding: const EdgeInsets.symmetric(
                  horizontal: 15,
                  vertical: 10,
                ),
                child: Text(
                  UiUtils.getTranslatedLabel(context, subtitleKey!),
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    color:
                        textColor ??
                        Theme.of(
                          context,
                        ).colorScheme.secondary.withValues(alpha: 0.7),
                    fontSize: 14,
                  ),
                ),
              ),
            if (showRetryButton ?? false) const SizedBox(height: 15),
            if (showRetryButton ?? false)
              CustomRoundedButton(
                height: 40,
                widthPercentage: 0.3,
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
            else
              const SizedBox(),
          ],
        ),
      ),
    );
  }
}
