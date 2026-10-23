import 'dart:async';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/eschool_people.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'support/harness.dart';

Map<String, dynamic> profileJson({
  String name = 'Asha',
  String type = 'learner',
}) => {
  'person_id': 12,
  'profile_type': type,
  'available_profiles': ['learner', 'teacher'],
  'categories': [
    {
      'id': 1,
      'key': 'transport',
      'label': 'Transport',
      'fields': [
        fieldJson('first_name', 'Name', editable: false, value: {'text': name}),
        fieldJson('route', 'Transport route', value: {'text': 'Old route'}),
        fieldJson('missing', 'Required later', required: true),
      ],
    },
  ],
};
Map<String, dynamic> fieldJson(
  String key,
  String label, {
  bool editable = true,
  bool required = false,
  String type = 'text',
  Map<String, Object?>? value,
  Map<String, Object?> validation = const {},
}) => {
  'id': key.hashCode.abs(),
  'key': key,
  'label': label,
  'type': type,
  'validation': validation,
  'options': <String>[],
  'required': required,
  'locked': !editable,
  'editable': editable,
  'sensitive': false,
  'value': value,
};

class _Profiles implements ProfileRepository {
  ProfileForm? form = ProfileForm.fromJson(profileJson());
  Object? fetchError;
  Object? saveError;
  final fetched = <(ProfileSubject, String?)>[];
  final saved = <(ProfileSubject, String, Map<String, ProfileValue>)>[];
  Completer<ProfileForm?>? pending;
  @override
  Future<ProfileForm?> fetch(
    ProfileSubject subject, {
    String? profileType,
  }) async {
    fetched.add((subject, profileType));
    if (fetchError != null) throw fetchError!;
    return pending == null ? form : pending!.future;
  }

  @override
  Future<void> save(
    ProfileSubject subject,
    String type,
    Map<String, ProfileValue> values,
  ) async {
    if (saveError != null) throw saveError!;
    saved.add((subject, type, Map.of(values)));
  }
}

void main() {
  test(
    'repository scopes reads and partial writes without changing auth or value types',
    () async {
      final adapter = RecordingAdapter(200, ok(profileJson()));
      final repository = ApiProfileRepository(clientFor(adapter));
      const subject = ProfileSubject(
        personId: 12,
        schoolId: 2,
        profileType: 'learner',
      );
      expect((await repository.fetch(subject))!.personId, 12);
      expect(adapter.requests.last.uri.path, '/api/v2/people/12/profile');
      expect(adapter.requests.last.queryParameters, {
        'profile_type': 'learner',
      });
      await repository.save(subject, 'learner', {
        'number': ProfileValue(text: '000123'),
        'permission': ProfileValue(flag: false),
        'route': ProfileValue(clear: true),
      });
      expect(adapter.requests.last.headers['Authorization'], 'Bearer token');
      expect(adapter.requests.last.headers['X-School-Id'], '2');
      expect(adapter.requests.last.data, {
        'profile_type': 'learner',
        'values': {
          'number': {'text': '000123'},
          'permission': {'flag': false},
          'route': {'clear': true},
        },
      });
    },
  );
  test('old server remains unavailable', () async {
    expect(
      await profilesDeployed(
        ApiProfileRepository(
          clientFor(RecordingAdapter(404, {'message': 'Not found'})),
        ),
      ),
      isFalse,
    );
  });
  test(
    'switching subjects clears private values and ignores an older response',
    () async {
      final repository = _Profiles();
      final cubit = ProfileCubit(repository);
      addTearDown(cubit.close);
      await cubit.load(const ProfileSubject(schoolId: 1));
      cubit.change(
        cubit.state.form!.categories.single.fields[1],
        ProfileValue(text: 'Private draft'),
      );
      repository.pending = Completer<ProfileForm?>();
      final oldRequest = cubit.load(
        const ProfileSubject(personId: 12, schoolId: 1),
      );
      expect(cubit.state.form, isNull);
      expect(cubit.state.draft, isEmpty);
      final older = repository.pending!;
      repository.pending = null;
      repository.form = ProfileForm.fromJson(profileJson(name: 'Teacher'));
      await cubit.load(const ProfileSubject(schoolId: 2));
      older.complete(
        ProfileForm.fromJson(profileJson(name: 'Old private value')),
      );
      await oldRequest;
      expect(cubit.state.subject.schoolId, 2);
      expect(
        cubit.state.form!.categories.single.fields.first.value!.text,
        'Teacher',
      );
      cubit.change(
        cubit.state.form!.categories.single.fields.first,
        ProfileValue(text: 'Forbidden'),
      );
      expect(cubit.state.draft, isEmpty);
    },
  );
  test(
    'retry keeps the selected profile type and unknown errors hide private details',
    () async {
      final repository = _Profiles()
        ..fetchError = StateError('private medical details');
      final cubit = ProfileCubit(repository);
      addTearDown(cubit.close);
      await cubit.load(const ProfileSubject(), profileType: 'teacher');
      expect(cubit.state.error!.toString(), isNot(contains('private medical')));
      repository.fetchError = null;
      await cubit.reload();
      expect(repository.fetched.last.$2, 'teacher');
    },
  );
  for (final size in [phone, desktop]) {
    testWidgets(
      'partial save on $size preserves unrelated missing fields and locks identity',
      (tester) async {
        final repository = _Profiles();
        await pumpScreen(
          tester,
          ProfileScreen.withRepository(repository),
          size: size,
        );
        expect(find.text('Asha'), findsOneWidget);
        expect(
          find.byKey(const ValueKey('profile-field-first_name')),
          findsNothing,
        );
        await tester.enterText(
          find.byKey(const ValueKey('profile-field-route')),
          'Blue bus',
        );
        FocusManager.instance.primaryFocus?.unfocus();
        await tester.pump();
        await tester.ensureVisible(find.text(testLabels[profileSaveKey]!));
        await tester.tap(find.text(testLabels[profileSaveKey]!));
        await tester.pumpAndSettle();
        expect(repository.saved.single.$3.keys, ['route']);
        expect(repository.saved.single.$3['route']!.text, 'Blue bus');
        expect(find.text(testLabels[profileSavedKey]!), findsOneWidget);
        expect(tester.takeException(), isNull);
      },
    );
  }
  testWidgets('failed save retains entered values and supports correction', (
    tester,
  ) async {
    final repository = _Profiles()
      ..saveError = ApiException(
        'Please choose another route.',
        isApiMessage: true,
      );
    await pumpScreen(tester, ProfileScreen.withRepository(repository));
    await tester.enterText(
      find.byKey(const ValueKey('profile-field-route')),
      'Blue bus',
    );
    await tester.pump();
    await tester.ensureVisible(find.text(testLabels[profileSaveKey]!));
    await tester.tap(find.text(testLabels[profileSaveKey]!));
    await tester.pumpAndSettle();
    expect(find.text('Please choose another route.'), findsOneWidget);
    expect(find.text('Blue bus'), findsOneWidget);
    repository.saveError = null;
    await tester.tap(find.text(testLabels[profileSaveKey]!));
    await tester.pumpAndSettle();
    expect(repository.saved.single.$3['route']!.text, 'Blue bus');
  });
  testWidgets('unavailable profile retains a working person selector', (
    tester,
  ) async {
    final repository = _Profiles()..form = null;
    await pumpScreen(
      tester,
      ProfileScreen.withRepository(
        repository,
        subjects: const [
          ProfileSubject(schoolId: 1, label: 'Own details'),
          ProfileSubject(personId: 12, schoolId: 2, label: 'Asha'),
        ],
      ),
    );
    await tester.tap(find.text('Own details'));
    await tester.pumpAndSettle();
    repository.form = ProfileForm.fromJson(profileJson());
    await tester.tap(find.text('Asha').last);
    await tester.pumpAndSettle();
    expect(find.text('Transport route'), findsOneWidget);
    expect(repository.fetched.last.$1.schoolId, 2);
  });
  testWidgets('cancelling discard keeps the selected person and draft', (
    tester,
  ) async {
    final repository = _Profiles();
    await pumpScreen(
      tester,
      ProfileScreen.withRepository(
        repository,
        subjects: const [
          ProfileSubject(schoolId: 1, label: 'Own details'),
          ProfileSubject(personId: 12, schoolId: 2, label: 'Child details'),
        ],
      ),
    );
    await tester.enterText(
      find.byKey(const ValueKey('profile-field-route')),
      'Blue bus',
    );
    await tester.ensureVisible(find.text('Own details'));
    await tester.tap(find.text('Own details'));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Child details').last);
    await tester.pumpAndSettle();
    await tester.tap(find.text(testLabels[cancelKey]!));
    await tester.pumpAndSettle();
    expect(find.text('Own details'), findsOneWidget);
    expect(find.text('Blue bus'), findsOneWidget);
    expect(repository.fetched, hasLength(1));
  });
}
