/// The four kinds of people the avatar set draws.
enum AvatarGroup { boy, girl, man, woman }

/// The avatars a person can choose instead of a photo: sixteen flat
/// pictures in the apps' own palette (every colour is a value of the
/// Material 3 theme), four each of boys, girls, men and women with a range
/// of skin tones and hair, in `assets/avatars/` of this package.
///
/// A choice is stored on the server as its key (`girl_2`), so the key, not
/// the file, is the contract: keys are never renamed or reused.
// ignore: avoid_classes_with_only_static_members
abstract final class AvatarSet {
  /// Where the pictures are, as an app (or this package's tests) loads them.
  static const String assetDirectory = 'packages/eschool_core/assets/avatars';

  static const int perGroup = 4;

  /// Every key, grouped boys, girls, men, women.
  static final List<String> keys = [
    for (final AvatarGroup group in AvatarGroup.values) ...keysOf(group),
  ];

  static List<String> keysOf(AvatarGroup group) => [
    for (int i = 1; i <= perGroup; i++) '${group.name}_$i',
  ];

  static bool isKey(String? value) => value != null && keys.contains(value);

  /// The picture of [key]: `packages/eschool_core/assets/avatars/girl_2.svg`.
  static String assetPath(String key) => '$assetDirectory/$key.svg';

  /// The group a key belongs to, or `null` for a value that is not a key.
  static AvatarGroup? groupOf(String key) {
    if (!isKey(key)) {
      return null;
    }
    final String name = key.substring(0, key.lastIndexOf('_'));
    return AvatarGroup.values.byName(name);
  }

  /// The group to start a person off with: boys and girls for learners, men
  /// and women for everyone else, by the gender on their profile. A gender
  /// the profile does not give picks by [seed] (the user's id), so the
  /// default is stable but not always the same group.
  static AvatarGroup defaultGroup({
    String? gender,
    required bool isLearner,
    int seed = 0,
  }) {
    final String value = (gender ?? '').trim().toLowerCase();
    final bool? female = switch (value) {
      'female' || 'f' || 'girl' || 'woman' => true,
      'male' || 'm' || 'boy' || 'man' => false,
      _ => null,
    };
    final bool asFemale = female ?? seed.isOdd;
    if (isLearner) {
      return asFemale ? AvatarGroup.girl : AvatarGroup.boy;
    }
    return asFemale ? AvatarGroup.woman : AvatarGroup.man;
  }

  /// The avatar a person has until they choose one: one of their
  /// [defaultGroup], picked by [seed] so brothers and sisters do not all
  /// look alike.
  static String defaultKey({
    String? gender,
    required bool isLearner,
    int seed = 0,
  }) {
    final AvatarGroup group = defaultGroup(
      gender: gender,
      isLearner: isLearner,
      seed: seed,
    );
    return keysOf(group)[seed.abs() % perGroup];
  }
}
