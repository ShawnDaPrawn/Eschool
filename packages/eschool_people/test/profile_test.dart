import 'dart:async';
import 'dart:typed_data';
import 'package:dio/dio.dart';
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

Map<String, Object?> attachmentJson({String name = 'letter.pdf'}) => {
  'id': '12345678-1234-1234-1234-123456789abc.pdf',
  'name': name,
  'mime': 'application/pdf',
  'size': 32,
  'url': 'https://untrusted.example/private',
};
ProfileForm fileForm({bool existing = false}) {
  final data = profileJson();
  ((data['categories'] as List).single['fields'] as List).add(
    fieldJson(
      'letter',
      'Support document',
      type: 'file',
      value: existing ? {'file': attachmentJson()} : null,
    ),
  );
  return ProfileForm.fromJson(data);
}

class _FileProfiles extends _Profiles implements ProfileFileRepository {
  _FileProfiles() {
    form = fileForm();
  }
  final uploads = <(ProfileSubject, String, String, ProfileUpload)>[];
  Completer<void>? uploading;
  Object? uploadError;
  bool failRefresh = false;
  @override
  Future<void> upload(
    ProfileSubject subject,
    String type,
    String field,
    ProfileUpload file,
  ) async {
    uploads.add((subject, type, field, file));
    if (uploadError != null) throw uploadError!;
    if (uploading != null) await uploading!.future;
    form = fileForm(existing: true);
    if (failRefresh) fetchError = ApiException('defaultErrorMessage');
  }

  @override
  Future<Uint8List> readFile(
    ProfileSubject subject,
    ProfileAttachment file,
  ) async => Uint8List(0);
}

void main() {
  test(
    'file uploads send multipart scope and downloads ignore untrusted metadata URLs',
    () async {
      final adapter = RecordingAdapter(200, ok(null));
      final repository = ApiProfileRepository(clientFor(adapter));
      const subject = ProfileSubject(personId: 12, schoolId: 2);
      await repository.upload(
        subject,
        'learner',
        'letter',
        ProfileUpload('letter.pdf', Uint8List.fromList([1, 2])),
      );
      final request = adapter.requests.single;
      expect(request.uri.path, '/api/v2/people/12/profile/fields/letter/file');
      expect(request.headers['X-School-Id'], '2');
      expect(request.headers['Authorization'], 'Bearer token');
      expect((request.data as FormData).fields.single.value, 'learner');
      expect(
        (request.data as FormData).files.single.value.filename,
        'letter.pdf',
      );
      await repository.readFile(
        subject,
        ProfileAttachment.fromJson(attachmentJson()),
      );
      expect(
        adapter.requests.last.uri.host,
        Uri.parse(AppConfig.apiBaseUrl).host,
      );
      expect(
        adapter.requests.last.uri.path,
        '/protected/profile-files/people/12/12345678-1234-1234-1234-123456789abc.pdf',
      );
      expect(adapter.requests.last.headers['X-School-Id'], '2');
      final invalid = attachmentJson()..['id'] = '../foreign';
      expect(
        () => repository.readFile(subject, ProfileAttachment.fromJson(invalid)),
        throwsA(isA<ApiException>()),
      );
      expect(adapter.requests, hasLength(2));
    },
  );
  test(
    'file upload preserves other drafts and ignores a picker from an earlier child',
    () async {
      final repository = _FileProfiles();
      final cubit = ProfileCubit(repository);
      addTearDown(cubit.close);
      const first = ProfileSubject(personId: 12, schoolId: 1);
      const second = ProfileSubject(personId: 13, schoolId: 1);
      await cubit.load(first);
      final oldState = cubit.state;
      final field = oldState.form!.categories.single.fields.last;
      final file = ProfileUpload('letter.pdf', Uint8List(1));
      await cubit.load(second);
      expect(
        await cubit.upload(
          field,
          file,
          subject: first,
          revision: oldState.revision,
        ),
        isFalse,
      );
      expect(repository.uploads, isEmpty);
      cubit.change(
        cubit.state.form!.categories.single.fields[1],
        ProfileValue(text: 'Keep this draft'),
      );
      final revision = cubit.state.revision;
      expect(
        await cubit.upload(field, file, subject: second, revision: revision),
        isTrue,
      );
      expect(cubit.state.draft['route']!.text, 'Keep this draft');
      expect(
        cubit.state.form!.categories.single.fields.last.value!.file!.name,
        'letter.pdf',
      );
      expect(cubit.state.revision, revision);
    },
  );
  test(
    'failed upload retains drafts; an acknowledged replacement hides the invalid old link if refresh fails',
    () async {
      final repository = _FileProfiles()..form = fileForm(existing: true);
      final cubit = ProfileCubit(repository);
      addTearDown(cubit.close);
      await cubit.load(const ProfileSubject());
      final field = cubit.state.form!.categories.single.fields.last;
      cubit.change(field, ProfileValue(clear: true));
      cubit.change(
        cubit.state.form!.categories.single.fields[1],
        ProfileValue(text: 'Keep this draft'),
      );
      repository.uploadError = ApiException('defaultErrorMessage');
      final file = ProfileUpload('letter.pdf', Uint8List(1));
      expect(
        await cubit.upload(
          field,
          file,
          subject: cubit.state.subject,
          revision: cubit.state.revision,
        ),
        isFalse,
      );
      expect(cubit.state.draft['letter']!.clear, true);
      expect(
        cubit.state.form!.categories.single.fields.last.value!.file,
        isNotNull,
      );
      repository.uploadError = null;
      repository.failRefresh = true;
      expect(
        await cubit.upload(
          field,
          file,
          subject: cubit.state.subject,
          revision: cubit.state.revision,
        ),
        isFalse,
      );
      expect(cubit.state.form!.categories.single.fields.last.value, isNull);
      expect(cubit.state.draft.keys, ['route']);
      expect(cubit.state.draft['route']!.text, 'Keep this draft');
      expect(cubit.state.busy, false);
      expect(cubit.state.error, isNotNull);
    },
  );
  for (final size in [phone, desktop]) {
    testWidgets(
      'file upload on $size keeps typed values and optional clear can be undone',
      (tester) async {
        final repository = _FileProfiles();
        await pumpScreen(
          tester,
          ProfileScreen.withRepository(
            repository,
            pickFile: () async => ProfileUpload('letter.pdf', Uint8List(1)),
          ),
          size: size,
        );
        await tester.enterText(
          find.byKey(const ValueKey('profile-field-route')),
          'Keep this draft',
        );
        FocusManager.instance.primaryFocus?.unfocus();
        await tester.pump();
        final upload = find.text(testLabels[profileFileUploadKey]!);
        await tester.ensureVisible(upload);
        await tester.tap(upload);
        await tester.pumpAndSettle();
        expect(repository.uploads, hasLength(1));
        expect(find.text('Keep this draft'), findsOneWidget);
        expect(find.text('letter.pdf'), findsOneWidget);
        final clear = find.widgetWithText(
          CheckboxListTile,
          testLabels[profileFileClearKey]!,
        );
        await tester.ensureVisible(clear);
        await tester.tap(clear);
        await tester.pumpAndSettle();
        expect(find.text(testLabels[profileFileViewKey]!), findsNothing);
        await tester.tap(clear);
        await tester.pumpAndSettle();
        expect(find.text(testLabels[profileFileViewKey]!), findsOneWidget);
        final save = find.text(testLabels[profileSaveKey]!);
        await tester.ensureVisible(save);
        await tester.tap(save);
        await tester.pumpAndSettle();
        expect(repository.saved.single.$3.keys, ['route']);
        expect(repository.saved.single.$3['route']!.text, 'Keep this draft');
        expect(tester.takeException(), isNull);
      },
    );
  }

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
