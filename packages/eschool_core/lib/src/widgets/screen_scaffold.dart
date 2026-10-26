import 'package:eschool_core/src/ui/shared_ui_utils.dart';
import 'package:eschool_core/src/widgets/screen_top_background_container.dart';
import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';

/// A screen with the apps' dark header (title, back button, at most one
/// action) and [body] below it: what the screens built once in the shared
/// packages use, so they look like the apps' own screens.
class ScreenScaffold extends StatelessWidget {
  const ScreenScaffold({
    required this.title,
    required this.body,
    this.action,
    this.showBackButton = true,
    this.onBack,
    super.key,
  });

  final String title;
  final Widget body;

  /// An icon button at the end of the header.
  final Widget? action;
  final bool showBackButton;

  /// An enclosing private navigator may own the exit action.
  final VoidCallback? onBack;

  @override
  Widget build(BuildContext context) {
    final ColorScheme colors = Theme.of(context).colorScheme;
    return Scaffold(
      backgroundColor: colors.surface,
      body: Column(
        children: [
          ScreenTopBackgroundContainer(
            padding: EdgeInsets.only(top: MediaQuery.paddingOf(context).top),
            heightPercentage: SharedUiUtils.appBarSmallerHeightPercentage,
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 12),
              child: Row(
                children: [
                  SizedBox(
                    width: 48,
                    child:
                        showBackButton &&
                            (onBack != null || Navigator.of(context).canPop())
                        ? IconButton(
                            tooltip: MaterialLocalizations.of(
                              context,
                            ).backButtonTooltip,
                            onPressed:
                                onBack ??
                                () => Navigator.of(context).maybePop(),
                            icon: SvgPicture.asset(
                              SharedUiUtils.getBackButtonPath(context),
                              width: 23,
                              height: 23,
                              colorFilter: ColorFilter.mode(
                                colors.onInverseSurface,
                                BlendMode.srcIn,
                              ),
                            ),
                          )
                        : null,
                  ),
                  Expanded(
                    child: Semantics(
                      header: true,
                      child: Text(
                        title,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          fontSize: SharedUiUtils.screenTitleFontSize,
                          color: colors.onInverseSurface,
                        ),
                      ),
                    ),
                  ),
                  SizedBox(
                    width: 48,
                    child: action == null
                        ? null
                        : IconTheme(
                            data: IconThemeData(color: colors.onInverseSurface),
                            child: action!,
                          ),
                  ),
                ],
              ),
            ),
          ),
          Expanded(child: body),
        ],
      ),
    );
  }
}

/// A short message in the middle of a screen with nothing to show: an icon
/// in a tonal circle, a title and a line on what to do.
class EmptyStateMessage extends StatelessWidget {
  const EmptyStateMessage({
    required this.icon,
    required this.title,
    this.message,
    this.action,
    super.key,
  });

  final IconData icon;
  final String title;
  final String? message;
  final Widget? action;

  @override
  Widget build(BuildContext context) {
    final ColorScheme colors = Theme.of(context).colorScheme;
    final TextTheme text = Theme.of(context).textTheme;
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(
          SharedUiUtils.screenContentHorizontalPadding,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            CircleAvatar(
              radius: 32,
              backgroundColor: colors.primary.withValues(alpha: 0.12),
              child: Icon(icon, size: 32, color: colors.primary),
            ),
            const SizedBox(height: 16),
            Text(
              title,
              textAlign: TextAlign.center,
              style: text.titleMedium?.copyWith(color: colors.onSurface),
            ),
            if (message != null) ...[
              const SizedBox(height: 8),
              Text(
                message!,
                textAlign: TextAlign.center,
                style: text.bodyMedium?.copyWith(
                  color: colors.onSurfaceVariant,
                ),
              ),
            ],
            if (action != null) ...[const SizedBox(height: 16), action!],
          ],
        ),
      ),
    );
  }
}
