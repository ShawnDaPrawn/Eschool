import 'package:eschool_teacher/utils/animationConfiguration.dart';
import 'package:eschool_teacher/utils/uiUtils.dart';
import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:flutter_svg/svg.dart';

//class to maintain details required by each menu items
class MenuContainerDetails {
  MenuContainerDetails({
    required this.iconPath,
    required this.titleKey,
    required this.route,
    this.arguments,
  });
  String iconPath;
  String titleKey;
  String route;
  Object? arguments;
}

class HomeContainerExploreAcademicsItemContainer extends StatelessWidget {
  const HomeContainerExploreAcademicsItemContainer({
    required this.item,
    super.key,
  });
  final MenuContainerDetails item;

  @override
  Widget build(BuildContext context) {
    return Animate(
      effects: customItemFadeAppearanceEffects(),
      child: InkWell(
        onTap: () {
          Navigator.of(
            context,
          ).pushNamed(item.route, arguments: item.arguments);
        },
        borderRadius: BorderRadius.circular(4),
        child: Container(
          decoration: BoxDecoration(
            color: Theme.of(context).colorScheme.surface,
            borderRadius: BorderRadius.circular(4),
          ),
          padding: const EdgeInsets.only(top: 12, left: 8, right: 8),
          child: LayoutBuilder(
            builder: (context, constraints) {
              return Column(
                children: [
                  Container(
                    height: constraints.maxWidth * 0.6,
                    width: constraints.maxWidth * 0.6,
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Theme.of(context).scaffoldBackgroundColor,
                      borderRadius: BorderRadius.circular(4),
                    ),
                    child: SvgPicture.asset(item.iconPath),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    UiUtils.getTranslatedLabel(context, item.titleKey),
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.secondary,
                      fontWeight: FontWeight.w500,
                      fontSize: 12,
                    ),
                  ),
                  const Spacer(),
                ],
              );
            },
          ),
        ),
      ),
    );
  }
}
