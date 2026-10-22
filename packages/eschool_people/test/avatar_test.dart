import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/eschool_people.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'support/harness.dart';

class FakeAvatars implements AvatarRepository {
  AvatarChoice? answer = const AvatarChoice(
    kind: AvatarChoice.set,
    key: 'woman_1',
    status: AvatarChoice.approved,
  );
  Object? error;
  final List<String> chosen = [];
  final List<PickedPhoto> uploaded = [];

  @override
  Future<AvatarChoice?> current() async => answer;

  @override
  Future<AvatarChoice> choose(String key) async {
    if (error != null) {
      throw error!;
    }
    chosen.add(key);
    return AvatarChoice(kind: AvatarChoice.set, key: key, status: 'approved');
  }

  @override
  Future<AvatarChoice> upload(PickedPhoto photo) async {
    uploaded.add(photo);
    return const AvatarChoice(
      kind: AvatarChoice.photo,
      url: 'https://school.test/pending.jpg',
      status: AvatarChoice.pending,
    );
  }
}

final PickedPhoto photo = PickedPhoto(
  bytes: Uint8List.fromList([1, 2, 3]),
  fileName: 'me.jpg',
);

void main() {
  group('ApiAvatarRepository', () {
    test('chooses an avatar of the set by its key', () async {
      final adapter = RecordingAdapter(
        200,
        ok({'kind': 'set', 'key': 'girl_2', 'url': null, 'status': 'approved'}),
      );

      final choice = await ApiAvatarRepository(
        clientFor(adapter),
      ).choose('girl_2');

      final request = adapter.requests.single;
      expect(request.method, 'PUT');
      expect(request.uri.path, '/api/v2/me/avatar');
      expect(request.data, {'avatar_key': 'girl_2'});
      expect(choice.key, 'girl_2');
      expect(choice.isPending, isFalse);
    });

    test('uploads a photo as multipart, routed as the PUT', () async {
      final adapter = RecordingAdapter(
        200,
        ok({
          'kind': 'photo',
          'key': null,
          'url': 'https://school.test/p.jpg',
          'status': 'pending',
        }),
      );

      final choice = await ApiAvatarRepository(
        clientFor(adapter),
      ).upload(photo);

      final request = adapter.requests.single;
      expect(request.method, 'POST');
      final FormData form = request.data as FormData;
      expect(form.fields.single.key, '_method');
      expect(form.fields.single.value, 'PUT');
      expect(form.files.single.key, 'photo');
      expect(form.files.single.value.filename, 'me.jpg');
      expect(choice.isPending, isTrue);
    });

    test(
      'reads the current choice, or null before the endpoint exists',
      () async {
        expect(
          (await ApiAvatarRepository(
            clientFor(
              RecordingAdapter(
                200,
                ok({'kind': 'set', 'key': 'boy_1', 'status': 'approved'}),
              ),
            ),
          ).current())!.key,
          'boy_1',
        );
        expect(
          await avatarsDeployed(
            ApiAvatarRepository(
              clientFor(RecordingAdapter(404, {'message': 'Not Found'})),
            ),
          ),
          isFalse,
        );
      },
    );
  });

  group('AvatarCubit', () {
    test('choosing the avatar already chosen sends nothing', () async {
      final repository = FakeAvatars();
      final cubit = AvatarCubit(repository);
      await cubit.load();

      await cubit.choose('woman_1');
      await cubit.choose('woman_2');

      expect(repository.chosen, ['woman_2']);
      expect(cubit.state.saved, isTrue);
      await cubit.close();
    });

    test('a photo waits for the school', () async {
      final cubit = AvatarCubit(FakeAvatars());
      await cubit.load();

      await cubit.upload(photo);

      expect(cubit.state.choice!.isPending, isTrue);
      await cubit.close();
    });

    test('a refused change keeps the old picture and says why', () async {
      final cubit = AvatarCubit(
        FakeAvatars()..error = ApiException('Not allowed', isApiMessage: true),
      );
      await cubit.load();

      await cubit.choose('man_2');

      expect(cubit.state.choice!.key, 'woman_1');
      expect(cubit.state.error!.errorMessage, 'Not allowed');
      await cubit.close();
    });
  });

  group('AvatarScreen', () {
    late FakeAvatars repository;
    var saved = 0;

    Widget screen({bool isLearner = false, String? gender = 'female'}) =>
        AvatarScreen.withRepository(
          repository,
          name: 'Lerato Mokoena',
          isLearner: isLearner,
          gender: gender,
          seed: 7,
          pickPhoto: () async => photo,
          onSaved: () => saved++,
        );

    setUp(() {
      repository = FakeAvatars();
      saved = 0;
    });

    for (final Size size in [phone, desktop]) {
      testWidgets('offers the sixteen avatars, the person\'s kind first '
          '(${size.width.toInt()} px)', (tester) async {
        await pumpScreen(tester, screen(), size: size);

        expect(find.text('Choose an avatar'), findsOneWidget);
        final double women = tester.getTopLeft(find.text('Women')).dy;
        await tester.scrollUntilVisible(find.text('Girls'), 100);
        expect(tester.getTopLeft(find.text('Girls')).dy, greaterThan(women));
        expect(tester.takeException(), isNull);
      });
    }

    testWidgets('a learner sees boys and girls first', (tester) async {
      await pumpScreen(tester, screen(isLearner: true, gender: 'male'));

      expect(find.text('Boys'), findsOneWidget);
      expect(find.byTooltip('Boys 1'), findsOneWidget);
    });

    testWidgets('one tap chooses and saves an avatar', (tester) async {
      await pumpScreen(tester, screen());

      await tester.tap(find.byTooltip('Women 3'));
      await tester.pumpAndSettle();

      expect(repository.chosen, ['woman_3']);
      expect(saved, 1);
      expect(find.text('Your picture is saved.'), findsOneWidget);
    });

    testWidgets('an uploaded photo shows it waits for the school', (
      tester,
    ) async {
      await pumpScreen(tester, screen());

      await tester.tap(find.text('Upload a photo'));
      await tester.pumpAndSettle();

      expect(repository.uploaded.single.fileName, 'me.jpg');
      expect(
        find.text('Your photo is waiting for the school to approve it.'),
        findsOneWidget,
      );
    });

    testWidgets('every avatar is a named button, the chosen one selected', (
      tester,
    ) async {
      final SemanticsHandle semantics = tester.ensureSemantics();
      await pumpScreen(tester, screen());

      expect(
        tester.getSemantics(find.byTooltip('Women 1')),
        isSemantics(isSelected: true, isButton: true, hasTapAction: true),
      );
      expect(
        tester.getSemantics(find.byTooltip('Women 2')),
        isSemantics(isSelected: false, isButton: true),
      );
      await expectLater(tester, meetsGuideline(androidTapTargetGuideline));
      await expectLater(tester, meetsGuideline(labeledTapTargetGuideline));
      semantics.dispose();
    });
  });
}
