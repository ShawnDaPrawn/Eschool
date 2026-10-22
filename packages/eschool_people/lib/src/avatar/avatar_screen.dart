import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/src/avatar/avatar_cubit.dart';
import 'package:eschool_people/src/avatar/avatar_repository.dart';
import 'package:eschool_people/src/common/people_widgets.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:image_picker/image_picker.dart';

/// Picks a photo on the device; `null` when the user picked none.
typedef PhotoPicker = Future<PickedPhoto?> Function();

Future<PickedPhoto?> _pickFromGallery() async {
  final XFile? file = await ImagePicker().pickImage(
    source: ImageSource.gallery,
    maxWidth: 1024,
    maxHeight: 1024,
    imageQuality: 85,
  );
  if (file == null) {
    return null;
  }
  return PickedPhoto(bytes: await file.readAsBytes(), fileName: file.name);
}

/// The profile picture: one of the avatar set (the person's own kind
/// first), or a photo of their own, which shows "waiting for the school"
/// until the school approves it.
class AvatarScreen extends StatelessWidget {
  const AvatarScreen({
    required this.name,
    required this.isLearner,
    this.gender,
    this.seed = 0,
    this.pickPhoto,
    this.onSaved,
    super.key,
  });

  /// The person's name, for their initials.
  final String name;
  final bool isLearner;

  /// The gender on the profile, which picks the default avatar.
  final String? gender;

  /// The user's id: keeps their default avatar stable.
  final int seed;
  final PhotoPicker? pickPhoto;

  /// Called once a new picture is saved (the app refreshes the profile).
  final VoidCallback? onSaved;

  static Widget withRepository(
    AvatarRepository repository, {
    required String name,
    required bool isLearner,
    String? gender,
    int seed = 0,
    PhotoPicker? pickPhoto,
    VoidCallback? onSaved,
  }) => BlocProvider<AvatarCubit>(
    create: (_) => AvatarCubit(repository)..load(),
    child: AvatarScreen(
      name: name,
      isLearner: isLearner,
      gender: gender,
      seed: seed,
      pickPhoto: pickPhoto,
      onSaved: onSaved,
    ),
  );

  static String groupLabelKey(AvatarGroup group) => switch (group) {
    AvatarGroup.boy => avatarBoysKey,
    AvatarGroup.girl => avatarGirlsKey,
    AvatarGroup.man => avatarMenKey,
    AvatarGroup.woman => avatarWomenKey,
  };

  @override
  Widget build(BuildContext context) {
    return ScreenScaffold(
      title: SharedUiUtils.getTranslatedLabel(context, profilePictureKey),
      body: BlocConsumer<AvatarCubit, AvatarState>(
        listener: (context, state) {
          if (state.saved) {
            onSaved?.call();
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(
                  SharedUiUtils.getTranslatedLabel(
                    context,
                    profilePictureSavedKey,
                  ),
                ),
              ),
            );
          } else if (state.error != null &&
              state.status == AvatarStatus.ready) {
            showErrorSnackBar(context, state.error!);
          }
        },
        builder: (context, state) => switch (state.status) {
          AvatarStatus.loading => const Center(
            child: CircularProgressIndicator(),
          ),
          AvatarStatus.unavailable => const NotAvailableYet(),
          AvatarStatus.failure => LoadFailure(
            error: state.error!,
            onRetry: () => context.read<AvatarCubit>().load(),
          ),
          AvatarStatus.ready => _AvatarPicker(screen: this, state: state),
        },
      ),
    );
  }
}

class _AvatarPicker extends StatelessWidget {
  const _AvatarPicker({required this.screen, required this.state});

  final AvatarScreen screen;
  final AvatarState state;

  @override
  Widget build(BuildContext context) {
    final ColorScheme colors = Theme.of(context).colorScheme;
    final StatusColors status = StatusColors.of(context);
    String label(String key) => SharedUiUtils.getTranslatedLabel(context, key);
    final AvatarChoice? choice = state.choice;
    final String defaultKey = AvatarSet.defaultKey(
      gender: screen.gender,
      isLearner: screen.isLearner,
      seed: screen.seed,
    );
    final String? chosenKey = choice == null
        ? defaultKey
        : (choice.kind == AvatarChoice.set ? choice.key : null);
    final AvatarGroup first = AvatarSet.defaultGroup(
      gender: screen.gender,
      isLearner: screen.isLearner,
      seed: screen.seed,
    );
    final List<AvatarGroup> groups = [
      first,
      ...AvatarGroup.values.where((group) => group != first),
    ];
    return ListView(
      padding: const EdgeInsets.fromLTRB(16, 24, 16, 32),
      children: [
        Center(
          child: PersonAvatar(
            name: screen.name,
            avatarKey: chosenKey,
            imageUrl: chosenKey == null ? choice?.url : null,
            radius: 48,
          ),
        ),
        if (choice?.isPending ?? false) ...[
          const SizedBox(height: 16),
          Card(
            elevation: 0,
            color: status.warning.withValues(alpha: 0.16),
            child: ListTile(
              leading: Icon(Icons.hourglass_top, color: colors.onSurface),
              title: Text(label(photoWaitingForSchoolKey)),
            ),
          ),
        ],
        const SizedBox(height: 16),
        Center(
          child: OutlinedButton.icon(
            onPressed: state.saving
                ? null
                : () async {
                    final AvatarCubit cubit = context.read<AvatarCubit>();
                    final PickedPhoto? photo =
                        await (screen.pickPhoto ?? _pickFromGallery)();
                    if (photo != null) {
                      await cubit.upload(photo);
                    }
                  },
            icon: const Icon(Icons.add_a_photo_outlined),
            label: Text(label(uploadPhotoKey)),
          ),
        ),
        const SizedBox(height: 16),
        SectionHeading(label(chooseAvatarKey)),
        for (final AvatarGroup group in groups) ...[
          Padding(
            padding: const EdgeInsets.fromLTRB(4, 8, 4, 4),
            child: Text(
              label(AvatarScreen.groupLabelKey(group)),
              style: Theme.of(
                context,
              ).textTheme.labelLarge?.copyWith(color: colors.onSurfaceVariant),
            ),
          ),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: [
              for (final (int index, String key) in AvatarSet.keysOf(
                group,
              ).indexed)
                _AvatarOption(
                  avatarKey: key,
                  label:
                      '${label(AvatarScreen.groupLabelKey(group))} ${index + 1}',
                  selected: key == chosenKey && choice != null,
                  enabled: !state.saving,
                ),
            ],
          ),
        ],
      ],
    );
  }
}

class _AvatarOption extends StatelessWidget {
  const _AvatarOption({
    required this.avatarKey,
    required this.label,
    required this.selected,
    required this.enabled,
  });

  final String avatarKey;
  final String label;
  final bool selected;
  final bool enabled;

  @override
  Widget build(BuildContext context) {
    final ColorScheme colors = Theme.of(context).colorScheme;
    return Semantics(
      selected: selected,
      child: IconButton(
        tooltip: label,
        isSelected: selected,
        onPressed: enabled
            ? () => context.read<AvatarCubit>().choose(avatarKey)
            : null,
        style: IconButton.styleFrom(
          padding: const EdgeInsets.all(4),
          side: BorderSide(
            color: selected ? colors.primary : Colors.transparent,
            width: 3,
          ),
        ),
        icon: PersonAvatar(name: label, avatarKey: avatarKey, radius: 30),
      ),
    );
  }
}
