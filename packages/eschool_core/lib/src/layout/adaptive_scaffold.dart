import 'package:eschool_core/src/layout/breakpoints.dart';
import 'package:eschool_core/src/layout/content_column.dart';
import 'package:flutter/material.dart';

/// One place the home screen's navigation can go.
class AdaptiveDestination {
  const AdaptiveDestination({
    required this.icon,
    required this.selectedIcon,
    required this.label,
    this.badgeCount,
  });

  final Widget icon;
  final Widget selectedIcon;
  final String label;

  /// Shown as a badge on the icon when above zero (unread chats).
  final int? badgeCount;
}

/// A home screen with its navigation where it fits the width:
///
/// - phone: [body] with the app's own [bottomNavigationBar] over its bottom
///   edge, exactly as the apps have always drawn it;
/// - tablet: a navigation rail on the start side and [body] in a
///   [ContentColumn];
/// - desktop: the same with the rail extended to show labels.
///
/// [overlays] (the force-update dialog, for example) are drawn over
/// everything at every width.
class AdaptiveScaffold extends StatelessWidget {
  const AdaptiveScaffold({
    required this.body,
    required this.destinations,
    required this.selectedIndex,
    required this.onDestinationSelected,
    this.bottomNavigationBar,
    this.overlays = const [],
    this.maxContentWidth = Breakpoints.contentMaxWidth,
    super.key,
  });

  final Widget body;
  final Widget? bottomNavigationBar;
  final List<AdaptiveDestination> destinations;

  /// The selected destination, or -1 for none.
  final int selectedIndex;
  final ValueChanged<int> onDestinationSelected;
  final List<Widget> overlays;

  /// Wider working screens opt in while existing phone-width content keeps its default.
  final double maxContentWidth;

  @override
  Widget build(BuildContext context) {
    final ScreenSize size = Breakpoints.of(context);
    if (size == ScreenSize.phone) {
      return Stack(
        children: [
          body,
          if (bottomNavigationBar != null)
            Align(
              alignment: Alignment.bottomCenter,
              child: bottomNavigationBar,
            ),
          ...overlays,
        ],
      );
    }
    final ColorScheme colors = Theme.of(context).colorScheme;
    final bool extended = size == ScreenSize.desktop;
    return Stack(
      children: [
        Row(
          children: [
            SafeArea(
              right: false,
              child: NavigationRail(
                extended: extended,
                backgroundColor: colors.surface,
                indicatorColor: colors.primary.withValues(alpha: 0.12),
                selectedIconTheme: IconThemeData(color: colors.primary),
                unselectedIconTheme: IconThemeData(
                  color: colors.onSurfaceVariant,
                ),
                selectedLabelTextStyle: TextStyle(
                  color: colors.primary,
                  fontWeight: FontWeight.w600,
                ),
                unselectedLabelTextStyle: TextStyle(
                  color: colors.onSurfaceVariant,
                ),
                labelType: extended
                    ? NavigationRailLabelType.none
                    : NavigationRailLabelType.all,
                selectedIndex:
                    selectedIndex >= 0 && selectedIndex < destinations.length
                    ? selectedIndex
                    : null,
                onDestinationSelected: onDestinationSelected,
                destinations: [
                  for (final destination in destinations)
                    NavigationRailDestination(
                      icon: _withBadge(destination, destination.icon),
                      selectedIcon: _withBadge(
                        destination,
                        destination.selectedIcon,
                      ),
                      label: Text(destination.label),
                    ),
                ],
              ),
            ),
            VerticalDivider(
              width: 1,
              thickness: 1,
              color: colors.outlineVariant,
            ),
            Expanded(
              child: ContentColumn(maxWidth: maxContentWidth, child: body),
            ),
          ],
        ),
        ...overlays,
      ],
    );
  }

  Widget _withBadge(AdaptiveDestination destination, Widget icon) {
    final int count = destination.badgeCount ?? 0;
    if (count <= 0) {
      return icon;
    }
    return Badge.count(count: count, child: icon);
  }
}
