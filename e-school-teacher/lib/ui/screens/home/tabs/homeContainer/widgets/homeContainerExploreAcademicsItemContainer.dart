import 'package:eschool_core/eschool_core.dart';
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
    final theme = Theme.of(context);
    return Animate(
      effects: customItemFadeAppearanceEffects(),
      child: FilledButton.tonal(
        onPressed: () =>
            Navigator.of(context)
                .pushNamed(item.route, arguments: item.arguments),
        style: FilledButton.styleFrom(
          padding: const EdgeInsets.all(12),
          backgroundColor: theme.colorScheme.surfaceContainerLow,
          foregroundColor: theme.colorScheme.onSurface,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AppTheme.radius),
          ),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              height: 64,
              width: 64,
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: theme.colorScheme.surfaceContainerLowest,
                borderRadius: BorderRadius.circular(AppTheme.radius),
              ),
              child: SvgPicture.asset(
                item.iconPath,
                excludeFromSemantics: true,
              ),
            ),
            const SizedBox(height: 8),
            Text(
              UiUtils.getTranslatedLabel(context, item.titleKey),
              textAlign: TextAlign.center,
              style: theme.textTheme.labelLarge,
            ),
          ],
        ),
      ),
    );
  }
}

/// Content sets row height; large text also reduces the number of columns.
class ExploreAcademicsGrid extends StatelessWidget {
  const ExploreAcademicsGrid({required this.items, super.key});
  final List<MenuContainerDetails> items;

  @override
  Widget build(BuildContext context) => LayoutBuilder(
    builder: (context, constraints) {
      final labelSize = MediaQuery.textScalerOf(context).scale(14);
      final minimumWidth = 104 + (labelSize - 14).clamp(0, double.infinity) * 4;
      final columns = ((constraints.maxWidth + 8) / (minimumWidth + 8))
          .floor()
          .clamp(1, 4);
      return Column(
        children: [
          for (var start = 0; start < items.length; start += columns) ...[
            if (start > 0) const SizedBox(height: 8),
            IntrinsicHeight(
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  for (var column = 0; column < columns; column++) ...[
                    if (column > 0) const SizedBox(width: 8),
                    Expanded(
                      child: start + column < items.length
                          ? HomeContainerExploreAcademicsItemContainer(
                              item: items[start + column],
                            )
                          : const SizedBox(),
                    ),
                  ],
                ],
              ),
            ),
          ],
        ],
      );
    },
  );
}
