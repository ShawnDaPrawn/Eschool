import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/src/context/me_context.dart';
import 'package:eschool_session/src/selection/child_selection_cubit.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

/// Wraps a guardian's child screens with the child switcher: a bar along
/// the top on phones and tablets (part of the screen's dark header), a list
/// of the children on the start side on desktops. With fewer than two
/// children there is nothing to switch and [child] is shown as it is.
///
/// Changing child here changes [ChildSelectionCubit], which every child
/// screen reads, so every open screen follows.
class ChildSwitcherFrame extends StatelessWidget {
  const ChildSwitcherFrame({required this.child, super.key});

  final Widget child;

  /// The bar's height below the status bar.
  static const double barHeight = 60;

  /// The width of the list of children on a desktop.
  static const double railWidth = 280;

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<ChildSelectionCubit, ChildSelectionState>(
      builder: (context, state) {
        if (!state.canSwitch || state.selected == null) {
          return child;
        }
        if (Breakpoints.of(context) == ScreenSize.desktop) {
          return Row(
            children: [
              SafeArea(
                right: false,
                child: SizedBox(
                  width: railWidth,
                  child: ChildSwitcherRail(state: state),
                ),
              ),
              VerticalDivider(
                width: 1,
                thickness: 1,
                color: Theme.of(context).colorScheme.outlineVariant,
              ),
              Expanded(child: child),
            ],
          );
        }
        final MediaQueryData media = MediaQuery.of(context);
        final double taken = barHeight + media.padding.top;
        return Column(
          children: [
            ChildSwitcherBar(state: state),
            Expanded(
              // The screen below draws its header as if it were at the top:
              // it is told its real height and that no status bar is above.
              child: MediaQuery(
                data: media.copyWith(
                  size: Size(media.size.width, media.size.height - taken),
                  padding: media.padding.copyWith(top: 0),
                  viewPadding: media.viewPadding.copyWith(top: 0),
                ),
                child: child,
              ),
            ),
          ],
        );
      },
    );
  }
}

/// The phone and tablet switcher: the child on show (picture, name and
/// school) as one button that opens the list of children.
class ChildSwitcherBar extends StatelessWidget {
  const ChildSwitcherBar({required this.state, super.key});

  final ChildSelectionState state;

  @override
  Widget build(BuildContext context) {
    final ColorScheme colors = Theme.of(context).colorScheme;
    final TextTheme text = Theme.of(context).textTheme;
    final MeChild child = state.selected!;
    final String switchLabel = SharedUiUtils.getTranslatedLabel(
      context,
      switchChildKey,
    );
    return Material(
      color: colors.inverseSurface,
      child: SafeArea(
        bottom: false,
        child: SizedBox(
          height: ChildSwitcherFrame.barHeight,
          // One node for screen readers: what the button does and which
          // child is on show.
          child: MergeSemantics(
            child: Semantics(
              label: _describe(switchLabel, child),
              child: TextButton(
                onPressed: () => showChildPicker(context),
                style: TextButton.styleFrom(
                  foregroundColor: colors.onInverseSurface,
                  shape: const RoundedRectangleBorder(),
                  padding: const EdgeInsets.symmetric(
                    horizontal: SharedUiUtils.screenContentHorizontalPadding,
                  ),
                ),
                child: ExcludeSemantics(
                  child: Row(
                    children: [
                      PersonAvatar(
                        name: child.name,
                        imageUrl: child.avatarUrl,
                        radius: 18,
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              child.name,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: text.titleSmall?.copyWith(
                                color: colors.onInverseSurface,
                              ),
                            ),
                            if (child.schoolName.isNotEmpty)
                              Text(
                                child.schoolName,
                                maxLines: 1,
                                overflow: TextOverflow.ellipsis,
                                style: text.bodySmall?.copyWith(
                                  color: colors.onInverseSurface.withValues(
                                    alpha: 0.8,
                                  ),
                                ),
                              ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 8),
                      Text(
                        switchLabel,
                        style: text.labelLarge?.copyWith(
                          color: colors.onInverseSurface,
                        ),
                      ),
                      const SizedBox(width: 4),
                      Icon(Icons.swap_horiz, color: colors.onInverseSurface),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

/// The desktop switcher: every child, the one on show selected.
class ChildSwitcherRail extends StatelessWidget {
  const ChildSwitcherRail({required this.state, super.key});

  final ChildSelectionState state;

  @override
  Widget build(BuildContext context) {
    final ColorScheme colors = Theme.of(context).colorScheme;
    return Material(
      color: colors.surface,
      child: ListView(
        padding: const EdgeInsets.symmetric(vertical: 16),
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(24, 8, 24, 12),
            child: Semantics(
              header: true,
              child: Text(
                SharedUiUtils.getTranslatedLabel(context, yourChildrenKey),
                style: Theme.of(
                  context,
                ).textTheme.titleMedium?.copyWith(color: colors.onSurface),
              ),
            ),
          ),
          for (final MeChild child in state.children)
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 2),
              child: ChildTile(
                learner: child,
                selected: child.id == state.selectedId,
                onTap: () =>
                    context.read<ChildSelectionCubit>().select(child.id),
              ),
            ),
        ],
      ),
    );
  }
}

/// One child in a list of children: picture, name and school, marked when
/// [selected].
class ChildTile extends StatelessWidget {
  const ChildTile({
    required this.learner,
    required this.selected,
    required this.onTap,
    super.key,
  });

  final MeChild learner;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final ColorScheme colors = Theme.of(context).colorScheme;
    final MeChild child = learner;
    return MergeSemantics(
      child: Semantics(
        selected: selected,
        button: true,
        child: ListTile(
          minTileHeight: 56,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AppTheme.radius),
          ),
          selected: selected,
          selectedTileColor: colors.primary.withValues(alpha: 0.12),
          selectedColor: colors.primary,
          leading: PersonAvatar(name: child.name, imageUrl: child.avatarUrl),
          title: Text(child.name, maxLines: 1, overflow: TextOverflow.ellipsis),
          subtitle: child.schoolName.isEmpty
              ? null
              : Text(
                  child.schoolName,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
          trailing: selected ? const Icon(Icons.check) : null,
          onTap: onTap,
        ),
      ),
    );
  }
}

/// Opens the list of children; picking one shows them on every child
/// screen. Answers the child picked, or `null` when the sheet was closed.
Future<MeChild?> showChildPicker(BuildContext context) {
  final ChildSelectionCubit cubit = context.read<ChildSelectionCubit>();
  return showModalBottomSheet<MeChild>(
    context: context,
    showDragHandle: true,
    isScrollControlled: true,
    builder: (sheetContext) {
      final ChildSelectionState state = cubit.state;
      return SafeArea(
        child: ListView(
          shrinkWrap: true,
          padding: const EdgeInsets.fromLTRB(12, 0, 12, 16),
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 0, 12, 12),
              child: Semantics(
                header: true,
                child: Text(
                  SharedUiUtils.getTranslatedLabel(
                    sheetContext,
                    chooseChildKey,
                  ),
                  style: Theme.of(sheetContext).textTheme.titleMedium,
                ),
              ),
            ),
            for (final MeChild child in state.children)
              ChildTile(
                learner: child,
                selected: child.id == state.selectedId,
                onTap: () async {
                  final MeChild? picked = await cubit.select(child.id);
                  if (sheetContext.mounted) {
                    Navigator.of(sheetContext).pop(picked);
                  }
                },
              ),
          ],
        ),
      );
    },
  );
}

String _describe(String action, MeChild child) => [
  action,
  child.name,
  if (child.schoolName.isNotEmpty) child.schoolName,
].join(', ');
