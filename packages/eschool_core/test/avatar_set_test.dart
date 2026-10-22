import 'dart:io';

import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:flutter_test/flutter_test.dart';

String _hex(Color colour) =>
    '#${colour.toARGB32().toRadixString(16).padLeft(8, '0').substring(2)}';

/// Every colour the theme defines: the scheme's roles and the status
/// colours. The avatars may use these and nothing else.
Set<String> _themeColours() {
  final ThemeData theme = AppTheme.light();
  final ColorScheme s = theme.colorScheme;
  final StatusColors status = theme.extension<StatusColors>()!;
  return {
    for (final Color colour in [
      s.primary,
      s.onPrimary,
      s.primaryContainer,
      s.onPrimaryContainer,
      s.primaryFixed,
      s.primaryFixedDim,
      s.onPrimaryFixed,
      s.onPrimaryFixedVariant,
      s.inversePrimary,
      s.secondary,
      s.onSecondary,
      s.secondaryContainer,
      s.onSecondaryContainer,
      s.secondaryFixed,
      s.secondaryFixedDim,
      s.onSecondaryFixed,
      s.onSecondaryFixedVariant,
      s.tertiary,
      s.onTertiary,
      s.tertiaryContainer,
      s.onTertiaryContainer,
      s.tertiaryFixed,
      s.tertiaryFixedDim,
      s.onTertiaryFixed,
      s.onTertiaryFixedVariant,
      s.error,
      s.onError,
      s.errorContainer,
      s.onErrorContainer,
      s.surface,
      s.onSurface,
      s.surfaceDim,
      s.surfaceBright,
      s.surfaceContainerLowest,
      s.surfaceContainerLow,
      s.surfaceContainer,
      s.surfaceContainerHigh,
      s.surfaceContainerHighest,
      s.onSurfaceVariant,
      s.outline,
      s.inverseSurface,
      s.onInverseSurface,
      status.success,
      status.warning,
      status.danger,
      status.info,
    ])
      _hex(colour),
  };
}

void main() {
  const String folder = 'assets/avatars';

  test('sixteen avatars, four of each kind of person', () {
    expect(AvatarSet.keys, hasLength(16));
    for (final AvatarGroup group in AvatarGroup.values) {
      expect(AvatarSet.keysOf(group), hasLength(4));
    }
    expect(AvatarSet.keys.toSet(), hasLength(16));
    expect(AvatarSet.keys.first, 'boy_1');
    expect(AvatarSet.groupOf('woman_3'), AvatarGroup.woman);
    expect(AvatarSet.groupOf('dragon_1'), isNull);
    expect(AvatarSet.isKey('girl_2'), isTrue);
    expect(AvatarSet.isKey('girl_5'), isFalse);
    expect(
      AvatarSet.assetPath('girl_2'),
      'packages/eschool_core/assets/avatars/girl_2.svg',
    );
  });

  test('every key has its picture and every picture a key', () {
    final Set<String> files = Directory(
      folder,
    ).listSync().map((file) => file.uri.pathSegments.last).toSet();
    expect(files, {for (final String key in AvatarSet.keys) '$key.svg'});
  });

  test('the pictures use the theme\'s colours only', () {
    final Set<String> allowed = _themeColours();
    final RegExp paint = RegExp(r'(fill|stroke)="([^"]+)"');
    final List<String> offenders = [];
    for (final String key in AvatarSet.keys) {
      final String svg = File('$folder/$key.svg').readAsStringSync();
      expect(svg, contains('viewBox="0 0 128 128"'), reason: key);
      expect(svg, isNot(contains('style=')), reason: key);
      for (final RegExpMatch match in paint.allMatches(svg)) {
        final String value = match.group(2)!.toLowerCase();
        if (value != 'none' && !allowed.contains(value)) {
          offenders.add('$key: $value');
        }
      }
    }
    expect(offenders, isEmpty);
  });

  group('the default avatar', () {
    test('follows the gender on the profile and whether a learner', () {
      expect(
        AvatarSet.groupOf(
          AvatarSet.defaultKey(gender: 'Female', isLearner: true),
        ),
        AvatarGroup.girl,
      );
      expect(
        AvatarSet.groupOf(
          AvatarSet.defaultKey(gender: 'male', isLearner: true),
        ),
        AvatarGroup.boy,
      );
      expect(
        AvatarSet.groupOf(
          AvatarSet.defaultKey(gender: 'female', isLearner: false),
        ),
        AvatarGroup.woman,
      );
      expect(
        AvatarSet.groupOf(AvatarSet.defaultKey(gender: 'M', isLearner: false)),
        AvatarGroup.man,
      );
    });

    test('is stable for a person and varies between people', () {
      final String first = AvatarSet.defaultKey(
        gender: 'female',
        isLearner: true,
        seed: 41,
      );
      expect(
        AvatarSet.defaultKey(gender: 'female', isLearner: true, seed: 41),
        first,
      );
      expect({
        for (int seed = 0; seed < 8; seed++)
          AvatarSet.defaultKey(gender: 'female', isLearner: true, seed: seed),
      }, hasLength(4));
    });

    test('without a gender, picks by the seed instead of failing', () {
      expect(AvatarSet.defaultGroup(isLearner: true, seed: 2), AvatarGroup.boy);
      expect(
        AvatarSet.defaultGroup(gender: '', isLearner: false, seed: 3),
        AvatarGroup.woman,
      );
    });
  });

  group('PersonAvatar', () {
    Future<void> pump(WidgetTester tester, Widget avatar) => tester.pumpWidget(
      MaterialApp(
        theme: AppTheme.light(),
        home: Scaffold(body: Center(child: avatar)),
      ),
    );

    testWidgets('draws a chosen avatar from the set', (tester) async {
      await pump(
        tester,
        const PersonAvatar(name: 'Asha Demo', avatarKey: 'girl_2'),
      );

      final SvgPicture picture = tester.widget(find.byType(SvgPicture));
      expect(
        (picture.bytesLoader as SvgAssetLoader).assetName,
        AvatarSet.assetPath('girl_2'),
      );
      // The package ships the picture under the path apps load it from.
      final String svg = (await tester.runAsync(
        () => rootBundle.loadString(AvatarSet.assetPath('girl_2')),
      ))!;
      expect(svg, startsWith('<svg'));
    });

    testWidgets('a key sent as the picture is drawn as the avatar', (
      tester,
    ) async {
      await pump(
        tester,
        const PersonAvatar(name: 'Asha Demo', imageUrl: 'man_4'),
      );

      expect(find.byType(SvgPicture), findsOneWidget);
    });

    testWidgets('shows initials when there is no picture', (tester) async {
      await pump(tester, const PersonAvatar(name: 'asha van der merwe'));

      expect(find.text('AM'), findsOneWidget);
      expect(PersonAvatar.initialsOf(''), '');
      expect(PersonAvatar.initialsOf('Thandi'), 'T');
    });

    testWidgets('is read out only when it is given a label', (tester) async {
      final SemanticsHandle semantics = tester.ensureSemantics();
      await pump(tester, const PersonAvatar(name: 'Asha Demo'));
      expect(find.bySemanticsLabel('AD'), findsNothing);

      await pump(
        tester,
        const PersonAvatar(name: 'Asha Demo', semanticsLabel: 'Asha Demo'),
      );
      expect(find.bySemanticsLabel('Asha Demo'), findsOneWidget);
      semantics.dispose();
    });
  });
}
