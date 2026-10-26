import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:eschool/ui/screens/parentHomeScreen.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/eschool_people.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

Map<String, Object?> profile({
  bool learner = false,
  String route = 'Old route',
}) => {
  'person_id': learner ? 11 : 21,
  'profile_type': learner ? 'learner' : 'parent',
  'available_profiles': [learner ? 'learner' : 'parent'],
  'categories': [
    {
      'id': 1,
      'key': 'transport',
      'label': 'Transport',
      'fields': [
        {
          'id': 1,
          'key': 'first_name',
          'label': 'First name',
          'type': 'text',
          'validation': {},
          'options': [],
          'required': true,
          'locked': true,
          'editable': false,
          'sensitive': false,
          'value': {'text': learner ? 'Thandi' : 'Lerato'},
        },
        {
          'id': 2,
          'key': 'route',
          'label': 'Transport route',
          'type': 'text',
          'validation': {},
          'options': [],
          'required': false,
          'locked': false,
          'editable': true,
          'sensitive': false,
          'value': {'text': route},
        },
      ],
    },
  ],
};

class _ProfileFilesApi implements HttpClientAdapter {
  final requests = <RequestOptions>[];
  bool uploaded = false;
  String route = 'Old route';
  bool pdf = false;
  String get extension => pdf ? 'pdf' : 'png';
  String get mime => pdf ? 'application/pdf' : 'image/png';
  Uint8List get bytes => pdf ? pdfBytes : pngBytes;
  final pdfBytes = base64Decode(
    'JVBERi0xLjQKMSAwIG9iago8PCAvVHlwZSAvQ2F0YWxvZyAvUGFnZXMgMiAwIFIgPj4KZW5kb2JqCjIgMCBvYmoKPDwgL1R5cGUgL1BhZ2VzIC9LaWRzIFszIDAgUl0gL0NvdW50IDEgPj4KZW5kb2JqCjMgMCBvYmoKPDwgL1R5cGUgL1BhZ2UgL1BhcmVudCAyIDAgUiAvTWVkaWFCb3ggWzAgMCAyMDAgMjAwXSAvUmVzb3VyY2VzIDw8IC9Gb250IDw8IC9GMSA1IDAgUiA+PiA+PiAvQ29udGVudHMgNCAwIFIgPj4KZW5kb2JqCjQgMCBvYmoKPDwgL0xlbmd0aCA0NiA+PgpzdHJlYW0KQlQgL0YxIDEyIFRmIDMwIDEwMCBUZCAoUHJpdmF0ZSBmaXh0dXJlKSBUaiBFVAplbmRzdHJlYW0KZW5kb2JqCjUgMCBvYmoKPDwgL1R5cGUgL0ZvbnQgL1N1YnR5cGUgL1R5cGUxIC9CYXNlRm9udCAvSGVsdmV0aWNhID4+CmVuZG9iagp4cmVmCjAgNgowMDAwMDAwMDAwIDY1NTM1IGYgCjAwMDAwMDAwMDkgMDAwMDAgbiAKMDAwMDAwMDA1OCAwMDAwMCBuIAowMDAwMDAwMTE1IDAwMDAwIG4gCjAwMDAwMDAyNDEgMDAwMDAgbiAKMDAwMDAwMDMzNyAwMDAwMCBuIAp0cmFpbGVyCjw8IC9TaXplIDYgL1Jvb3QgMSAwIFIgPj4Kc3RhcnR4cmVmCjQwNwolJUVPRgo=',
  );
  final pngBytes = base64Decode(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR4nGP4z8DwHwAFAAH/iZk9HQAAAABJRU5ErkJggg==',
  );
  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? requestStream,
    Future<void>? cancelFuture,
  ) async {
    requests.add(options);
    if (options.uri.path.contains('/protected/profile-files/')) {
      return ResponseBody.fromBytes(
        bytes,
        200,
        headers: {
          Headers.contentTypeHeader: [mime],
        },
      );
    }
    if (options.method == 'POST') uploaded = true;
    if (options.method == 'PUT') {
      route = (options.data as Map)['values']['route']['text'] as String;
    }
    // Decode a wire-shaped tree so the added field can carry a null value.
    final data = jsonDecode(
      jsonEncode(profile(learner: true, route: route)),
    ) as Map<String, dynamic>;
    ((data['categories'] as List).single['fields'] as List).add({
      'id': 3,
      'key': 'letter',
      'label': 'Support document',
      'type': 'file',
      'validation': <String, Object?>{},
      'options': <String>[],
      'required': false,
      'locked': false,
      'editable': true,
      'sensitive': false,
      'value': uploaded
          ? {
              'file': {
                'id': '12345678-1234-1234-1234-123456789abc.$extension',
                'name': 'letter.$extension',
                'mime': mime,
                'size': bytes.length,
                'url': 'https://untrusted.example/private',
              },
            }
          : null,
    });
    return ResponseBody.fromString(
      jsonEncode(ok(options.method == 'GET' ? data : null)),
      200,
      headers: {
        Headers.contentTypeHeader: [Headers.jsonContentType],
      },
    );
  }

  @override
  void close({bool force = false}) {}
}

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  testWidgets(
    'private file upload preserves profile drafts and previews authenticated bytes',
    (tester) async {
      await prepareSmokeApp();
      final api = _ProfileFilesApi();
      final client = ApiClient(
        authToken: () => 'smoke-token',
        onUnauthorized: () {},
        createDio: () => Dio()..httpClientAdapter = api,
      );
      await tester.pumpWidget(
        MaterialApp(
          theme: AppTheme.light(),
          locale: const Locale('en'),
          supportedLocales: const [Locale('en')],
          localizationsDelegates: const [
            AppLocalizationDelegate([
              AppLanguage(languageCode: 'en', languageName: 'English'),
            ]),
          ],
          builder: (context, navigator) => AppShell(
            errorMessageKeyFromCode: (_) => 'defaultErrorMessage',
            openStudyMaterial: (_, _) {},
            studyMaterialButton: (_, _) => const SizedBox(),
            bottomNavigationHeightPercentage: 0.08,
            child: navigator!,
          ),
          home: ProfileScreen.withRepository(
            ApiProfileRepository(client),
            subjects: const [
              ProfileSubject(personId: 11, schoolId: 2, profileType: 'learner'),
            ],
            pickFile: () async =>
                ProfileUpload('letter.${api.extension}', api.bytes),
          ),
        ),
      );
      await pumpUntilFound(tester, find.byType(ProfileScreen));
      final editor = tester
          .element(find.byType(ProfileScreen))
          .read<ProfileCubit>();
      final loadEnd = DateTime.now().add(const Duration(seconds: 15));
      while (editor.state.status == ProfileStatus.loading &&
          DateTime.now().isBefore(loadEnd)) {
        await tester.pump(const Duration(milliseconds: 100));
      }
      expect(
        editor.state.status,
        ProfileStatus.loaded,
        reason:
            'Profile state: ${editor.state.error}; requests: ${api.requests.map((request) => request.uri.path).toList()}',
      );
      await pumpUntilFound(
        tester,
        find.byKey(const ValueKey('profile-field-route')),
      );
      final routeField = find.byKey(const ValueKey('profile-field-route'));
      await tester.showKeyboard(routeField);
      await tester.pump(const Duration(milliseconds: 600));
      await tester.enterText(routeField, 'Unsaved bus');
      await tester.pump();
      await tapWhenReady(tester, find.text('Upload or replace file'));
      await pumpUntilFound(tester, find.text('letter.png'));
      expect(find.text('Unsaved bus'), findsOneWidget);
      final upload = api.requests.singleWhere(
        (request) => request.method == 'POST',
      );
      expect(upload.headers['X-School-Id'], '2');
      expect(
        (upload.data as FormData).files.single.value.filename,
        'letter.png',
      );
      final cachedImages = PaintingBinding.instance.imageCache.currentSize;
      await tapWhenReady(tester, find.text('View file'));
      final image = find.byWidgetPredicate(
        (widget) => widget is RawImage && widget.image != null,
      );
      await pumpUntilFound(tester, image);
      final decoded = tester.widget<RawImage>(image).image!;
      expect(decoded.width, 1);
      expect(decoded.height, 1);
      expect(PaintingBinding.instance.imageCache.currentSize, cachedImages);
      final read = api.requests.singleWhere(
        (request) => request.uri.path.contains('/protected/profile-files/'),
      );
      expect(read.uri.host, Uri.parse(AppConfig.apiBaseUrl).host);
      expect(read.headers['X-School-Id'], '2');
      expect(read.headers['Authorization'], 'Bearer smoke-token');
      await tapWhenReady(tester, find.byType(BackButton));
      await pumpUntilFound(tester, find.text('Unsaved bus'));
      expect(decoded.debugDisposed, isTrue);
      await tapWhenReady(tester, find.text('Save profile'));
      await pumpUntilFound(tester, find.text('Profile saved'));
      final write = api.requests.singleWhere(
        (request) => request.method == 'PUT',
      );
      expect((write.data as Map)['values'], {
        'route': {'text': 'Unsaved bus'},
      });
      api.pdf = true;
      await tapWhenReady(tester, find.text('Upload or replace file'));
      await pumpUntilFound(tester, find.text('letter.pdf'));
      await tapWhenReady(tester, find.text('View file'));
      await pumpUntilFound(
        tester,
        find.byKey(const ValueKey('profile-file-ready')),
      );
      expect(find.byType(PdfDocumentView), findsOneWidget);
      final pdfRead = api.requests.lastWhere(
        (request) => request.uri.path.contains('/protected/profile-files/'),
      );
      expect(pdfRead.uri.path, endsWith('.pdf'));
      expect(pdfRead.headers['X-School-Id'], '2');
      expect(tester.takeException(), isNull);
    },
  );
  testWidgets(
    'guardian edits a child profile using its own school and a partial write',
    (tester) async {
      var route = 'Old route';
      final childSchool = {
        'id': 2,
        'name': 'Second School',
        'short_name': null,
        'crest_url': null,
      };
      final api = await startSmokeApp(tester, {
        ...parentOfTwoRoutes(),
        'GET v2/me/context': ok({
          'schools': [
            {
              'school': {
                'id': 1,
                'name': 'First School',
                'short_name': null,
                'crest_url': null,
              },
              'roles': ['Parent'],
            },
          ],
          'children': [
            {
              'id': 11,
              'user_id': 11,
              'first_name': 'Thandi',
              'last_name': 'Mokoena',
              'name': 'Thandi Mokoena',
              'image': null,
              'school': childSchool,
            },
          ],
        }),
        'GET v2/me/profile': ok(profile()),
        'GET v2/people/11/profile': (RequestOptions _) =>
            ok(profile(learner: true, route: route)),
        'PUT v2/people/11/profile': (RequestOptions request) {
          route =
              ((request.data as Map)['values'] as Map)['route']['text']
                  as String;
          return ok(null);
        },
      });
      await tapWhenReady(tester, find.text('Login as Parent'));
      await pumpUntilFound(tester, find.text('Email or handle'));
      await enterField(tester, 0, parentEmail);
      await enterField(tester, 1, password);
      await tapWhenReady(
        tester,
        find.widgetWithText(CustomRoundedButton, 'Sign in'),
      );
      await pumpUntilFound(tester, find.byType(ParentHomeScreen));
      await tapWhenReady(tester, find.byIcon(Icons.settings));
      await tapWhenReady(tester, find.text('Profile details'));
      await pumpUntilFound(tester, find.byType(ProfileScreen));
      await tapWhenReady(tester, find.textContaining('My details'));
      await tapWhenReady(
        tester,
        find.text('Thandi Mokoena — Second School').last,
      );
      await pumpUntilFound(tester, find.text('Thandi'));
      final routeField = find.byKey(const ValueKey('profile-field-route'));
      // Opening the native keyboard can echo its initial editing value. Finish
      // that platform transition before injecting the user's new text.
      await tester.showKeyboard(routeField);
      await tester.pump(const Duration(milliseconds: 600));
      await tester.enterText(routeField, 'Blue bus');
      // Let the edit reach the rebuilt form and native text-input connection
      // before hiding the keyboard; an immediate unfocus can replay old IME text.
      await tester.pump();
      final editor = tester
          .element(find.byKey(const ValueKey('profile-field-route')))
          .read<ProfileCubit>();
      expect(editor.state.draft['route']?.text, 'Blue bus');
      FocusManager.instance.primaryFocus?.unfocus();
      await tester.pump(const Duration(milliseconds: 300));
      expect(editor.state.draft['route']?.text, 'Blue bus');
      await tapWhenReady(tester, find.text('Save profile'));
      await pumpUntilFound(tester, find.text('Profile saved'));
      final write = api.requests
          .where(
            (r) =>
                r.method == 'PUT' && r.uri.path.endsWith('people/11/profile'),
          )
          .single;
      expect(write.headers['X-School-Id'], '2');
      expect(write.data, {
        'profile_type': 'learner',
        'values': {
          'route': {'text': 'Blue bus'},
        },
      });
      expect(route, 'Blue bus');
    },
  );
}
