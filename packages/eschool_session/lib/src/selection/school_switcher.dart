import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/src/context/me_context_cubit.dart';
import 'package:eschool_session/src/selection/school_selection_cubit.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

/// The school switcher in the home header of a person at several schools:
/// the current school's crest as a button that lists their schools. Takes
/// no room for a person at one school.
class SchoolSwitcherButton extends StatelessWidget {
  const SchoolSwitcherButton({required this.onSwitched, super.key});

  /// What the app does once the school changed (`/me/context` is already
  /// being fetched again): go back to its home screen.
  final VoidCallback onSwitched;

  @override
  Widget build(BuildContext context) {
    return BlocBuilder<SchoolSelectionCubit, SchoolSelectionState>(
      builder: (context, state) {
        if (!state.canSwitch) {
          return const SizedBox.shrink();
        }
        final ColorScheme colors = Theme.of(context).colorScheme;
        final SchoolSummary? current = state.selected?.school;
        final String label = [
          SharedUiUtils.getTranslatedLabel(context, switchSchoolKey),
          ?current?.name,
        ].join(', ');
        return IconButton(
          tooltip: label,
          onPressed: () => showSchoolPicker(context, onSwitched: onSwitched),
          icon: DecoratedBox(
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              border: Border.all(color: colors.onInverseSurface, width: 1.5),
            ),
            child: SchoolCrest(school: current, radius: 14),
          ),
        );
      },
    );
  }
}

/// A school's crest in a circle, or its initials when it has none.
class SchoolCrest extends StatelessWidget {
  const SchoolCrest({required this.school, this.radius = 20, super.key});

  final SchoolSummary? school;
  final double radius;

  @override
  Widget build(BuildContext context) => PersonAvatar(
    name: school?.shortName ?? school?.name ?? '',
    imageUrl: school?.crestUrl,
    radius: radius,
  );
}

/// Lists the user's schools; picking another one makes it the school every
/// call is for, fetches `/me/context` again and calls [onSwitched]. Answers
/// whether the school changed.
Future<bool> showSchoolPicker(
  BuildContext context, {
  required VoidCallback onSwitched,
}) async {
  final SchoolSelectionCubit cubit = context.read<SchoolSelectionCubit>();
  final MeContextCubit meContext = context.read<MeContextCubit>();
  final int? picked = await showModalBottomSheet<int>(
    context: context,
    showDragHandle: true,
    isScrollControlled: true,
    builder: (sheetContext) {
      final SchoolSelectionState state = cubit.state;
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
                    chooseSchoolKey,
                  ),
                  style: Theme.of(sheetContext).textTheme.titleMedium,
                ),
              ),
            ),
            for (final SchoolMembership membership in state.schools)
              SchoolTile(
                membership: membership,
                selected: membership.school.id == state.selectedId,
                onTap: () =>
                    Navigator.of(sheetContext).pop(membership.school.id),
              ),
          ],
        ),
      );
    },
  );
  if (picked == null || !await cubit.select(picked)) {
    return false;
  }
  meContext.load();
  onSwitched();
  return true;
}

/// One school in the school picker: crest, name and the user's roles there.
class SchoolTile extends StatelessWidget {
  const SchoolTile({
    required this.membership,
    required this.selected,
    required this.onTap,
    super.key,
  });

  final SchoolMembership membership;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final ColorScheme colors = Theme.of(context).colorScheme;
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
          leading: SchoolCrest(school: membership.school),
          title: Text(
            membership.school.name,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
          subtitle: membership.roles.isEmpty
              ? null
              : Text(membership.roles.join(', ')),
          trailing: selected ? const Icon(Icons.check) : null,
          onTap: onTap,
        ),
      ),
    );
  }
}
