import 'dart:async';
import 'dart:convert';
import 'dart:typed_data';

import 'package:eschool_people/eschool_people.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:flutter_test/flutter_test.dart';

import 'profile_test.dart' show fileForm;
import 'support/harness.dart';

class _Context implements MeContextRepository {
  int account = 7, school = 1;
  String role = 'Parent';
  int child = 12;
  int fetches = 0;
  Completer<MeContext?>? pending;
  MeContext value() => MeContext.fromJson({
    'user': {
      'id': account,
      'first_name': 'Synthetic',
      'last_name': 'Actor',
      'name': 'Synthetic Actor',
      'email': 'synthetic@example.test',
      'image': null,
      'roles': [role],
    },
    'current_school': {
      'id': school,
      'name': 'Synthetic school',
      'short_name': null,
      'crest_url': null,
      'tagline': null,
      'timezone': 'Africa/Johannesburg',
      'branding': {'theme_color': null, 'secondary_color': null},
      'session_year': null,
    },
    'feature_flags': {'module_switches': true},
    'modules': [],
    'children': [
      {
        'id': child,
        'user_id': child,
        'name': 'Synthetic Learner',
        'first_name': 'Synthetic',
        'last_name': 'Learner',
        'image': null,
        'school': {
          'id': school,
          'name': 'Synthetic school',
          'short_name': null,
          'crest_url': null,
        },
      },
    ],
  });
  @override
  Future<MeContext?> fetchContext() async {
    fetches++;
    return pending?.future ?? value();
  }
}

class _Files implements ProfileRepository, ProfileFileRepository {
  Completer<Uint8List>? pending;
  Uint8List? delivered;
  int reads = 0;
  @override
  Future<ProfileForm?> fetch(
    ProfileSubject subject, {
    String? profileType,
  }) async {
    final json = fileForm(existing: true).toJson();
    final fields =
        ((json['categories'] as List).single as Map)['fields'] as List;
    final attachment = (fields.last as Map)['value']['file'] as Map;
    attachment['mime'] = 'image/png';
    attachment['name'] = 'synthetic.png';
    return ProfileForm.fromJson(json);
  }

  @override
  Future<void> save(
    ProfileSubject subject,
    String profileType,
    Map<String, ProfileValue> values,
  ) async {}
  @override
  Future<void> upload(
    ProfileSubject subject,
    String profileType,
    String field,
    ProfileUpload file,
  ) async {}
  @override
  Future<Uint8List> readFile(
    ProfileSubject subject,
    ProfileAttachment file,
  ) async {
    reads++;
    delivered = pending == null ? png() : await pending!.future;
    return delivered!;
  }

  Uint8List png() => base64Decode(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGP4z8DwHwAFAAH/iZk9HQAAAABJRU5ErkJggg==',
  );
}

Future<MeContextCubit> _open(
  WidgetTester tester,
  _Context context,
  _Files files, {
  bool preview = true,
  bool refreshOnSaved = false,
}) async {
  final session = MeContextCubit(
    repository: context,
    cache: InMemoryMeContextCache(),
  );
  addTearDown(session.close);
  await session.load();
  expect(session.context?.user?.id, 7);
  expect(session.context?.children?.single.id, 12);
  await pumpScreen(
    tester,
    BlocProvider<MeContextCubit>.value(
      value: session,
      child: ProfileScreen.withRepository(
        files,
        onSaved: refreshOnSaved ? () => session.load() : null,
        pickFile: () async => ProfileUpload('synthetic.png', files.png()),
      ),
    ),
  );
  if (!preview) return session;
  final button = find.widgetWithText(
    TextButton,
    testLabels['profileFileView'] ?? 'View file',
  );
  // Use the existing translated, labelled field action rather than a new reader.
  final view = button.evaluate().isNotEmpty
      ? button
      : find.byIcon(Icons.visibility_outlined);
  await tester.ensureVisible(view);
  await tester.tap(view);
  await tester.pump();
  await tester.runAsync(() async {
    await Future<void>.delayed(const Duration(milliseconds: 20));
  });
  await tester.pump(const Duration(milliseconds: 400));
  if (files.pending == null) await tester.pumpAndSettle();
  return session;
}

void main() {
  testWidgets(
    'default profile attachment refresh preserves drafts but full field save retires context',
    (tester) async {
      final context = _Context(), files = _Files();
      await _open(tester, context, files, preview: false, refreshOnSaved: true);
      final draft = find.byKey(const ValueKey('profile-field-route'));
      await tester.enterText(draft, 'Synthetic private draft');
      final upload = find.widgetWithText(
        OutlinedButton,
        testLabels['profileFileUpload']!,
      );
      await tester.ensureVisible(upload);
      await tester.tap(upload);
      await tester.pumpAndSettle();
      expect(
        context.fetches,
        1,
        reason:
            'The file endpoint already reauthorizes/reloads its profile; it changes no context fields.',
      );
      expect(find.text('Synthetic private draft'), findsOneWidget);
      final save = find.text(testLabels['profileSave']!);
      await tester.ensureVisible(save);
      await tester.tap(save);
      await tester.pumpAndSettle();
      expect(context.fetches, 2);
      expect(find.text('Synthetic private draft'), findsNothing);
      expect(find.byKey(const ValueKey('profile-field-route')), findsNothing);
      expect(tester.takeException(), isNull);
    },
  );
  for (final change in [
    'account',
    'school',
    'role',
    'guardian-source',
    'equivalent-payload',
  ]) {
    testWidgets(
      'existing pushed profile preview retires on $change context refresh',
      (tester) async {
        final context = _Context(), files = _Files();
        final session = await _open(tester, context, files);
        await tester.runAsync(() async {
          await Future<void>.delayed(const Duration(milliseconds: 20));
        });
        await tester.pumpAndSettle();
        final reader = find.byType(RawImage);
        expect(reader, findsOneWidget);
        final image = tester.widget<RawImage>(reader).image!;
        expect(image.width, 1);
        if (change == 'account') context.account = 8;
        if (change == 'school') context.school = 2;
        if (change == 'role') context.role = 'Facilitator';
        if (change == 'guardian-source') context.child = 13;
        await session.load();
        await tester.pumpAndSettle();
        expect(find.byType(RawImage), findsNothing);
        expect(image.debugDisposed, isTrue);
        expect(files.delivered!.every((b) => b == 0), isTrue);
        expect(tester.takeException(), isNull);
      },
    );
  }
  testWidgets(
    'old default profile preview discards bytes arriving after context retirement',
    (tester) async {
      final context = _Context(),
          files = _Files()..pending = Completer<Uint8List>();
      final session = await _open(tester, context, files);
      expect(files.reads, 1);
      context.role = 'Facilitator';
      await session.load();
      await tester.pumpAndSettle();
      final bytes = files.png();
      files.pending!.complete(bytes);
      await tester.runAsync(() async {
        await Future<void>.delayed(const Duration(milliseconds: 20));
      });
      await tester.pumpAndSettle();
      expect(find.byType(RawImage), findsNothing);
      expect(bytes.every((b) => b == 0), isTrue);
      expect(tester.takeException(), isNull);
    },
  );
}
