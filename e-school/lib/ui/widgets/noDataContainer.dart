import 'package:eschool/utils/animationConfiguration.dart';
import 'package:eschool/utils/assets.dart';
import 'package:eschool/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_svg/flutter_svg.dart';

class NoDataContainer extends StatelessWidget {
  const NoDataContainer({
    required this.titleKey,
    super.key,
    this.textColor,
    this.animate = true,
  });
  final Color? textColor;
  final String titleKey;
  final bool animate;

  @override
  Widget build(BuildContext context) {
    return Animate(
      effects: animate ? customItemBounceScaleAppearanceEffects() : null,
      child: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            SizedBox(height: MediaQuery.sizeOf(context).height * 0.025),
            SizedBox(
              height: MediaQuery.sizeOf(context).height * 0.35,
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
                  fontSize: 16,
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
