import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:eschool/ui/screens/home/homeScreen.dart';
import 'package:eschool/ui/screens/parentHomeScreen.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_core/testing.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';

import 'support/fixtures.dart';
import 'support/smoke.dart';

const _session = '10000000-0000-4000-8000-000000000001';
const _photo = '10000000-0000-4000-8000-000000000003';
const _pixels =
    'iVBORw0KGgoAAAANSUhEUgAAABQAAAAUCAYAAACNiR0NAAAAHklEQVR4nGNQSFjwn5qYYdTAUQNHDRw1cNTAkWogAOkyUJ4SO0XgAAAAAElFTkSuQmCC';

final class _Photo extends PlatformFile {
  @override
  String get name => 'synthetic-device-name.png';
  @override
  Uri get uri => Uri.parse('blob:synthetic-working');
  @override
  Never get xFile => throw StateError('Only bounded streaming');
  @override
  int? lengthSync() => base64Decode(_pixels).length;
  @override
  Future<int?> length() async => lengthSync();
  @override
  Future<Uint8List> readAsBytes() => throw StateError('Only bounded streaming');
  @override
  Stream<Uint8List> readAsByteStream() async* {
    yield base64Decode(_pixels);
  }
}

class _Picker extends FilePickerPlatform {
  @override
  Future<PlatformFile?> pickFile({
    String? dialogTitle,
    String? initialDirectory,
    FileType type = FileType.any,
    List<String>? allowedExtensions,
    Function(FilePickerStatus)? onFileLoading,
    int compressionQuality = 0,
    AndroidOptions androidOptions = const AndroidOptions(),
    DarwinOptions darwinOptions = const DarwinOptions(),
    WindowsOptions windowsOptions = const WindowsOptions(),
    LinuxOptions linuxOptions = const LinuxOptions(),
    WebOptions webOptions = const WebOptions(),
  }) async => _Photo();
  @override
  Future<void> clearTemporaryFiles() async {}
}

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();
  for (final parent in [false, true]) {
    testWidgets(
      '${parent ? 'Parent' : 'Student'} Tutor entry, exact private recovery and revoked access',
      (tester) async {
        var permitted = true, posted = false, uploaded = false, sends = 0;
        String? operation;
        Map<String, dynamic>? turn;
        final account = parent ? 21 : 11;
        Map<String, dynamic> scope(Map<String, dynamic> value) => {
          'school_id': 1,
          'reader_id': account,
          ...value,
        };
        Map<String, dynamic> receipt() => scope({
          'id': _photo,
          'operation_id': operation,
          'sha256': 'ee3daca4ec36aa6135bd21a554b48f5e2d1c79f338499e7f6e4bee7d30a9c6f6',
          'mime': 'image/png',
        });
        Map<String, dynamic> session() => scope({
          'id': _session,
          'learner_id': 11,
          'subject_id': 4,
          'group_id': 3,
          'session_year_id': 2,
          'state': 'open',
        });
        Object guard(Object value) => permitted
            ? ok(value)
            : const FakeResponse({'message': 'Access changed.'}, status: 403);
        final originalPicker = FilePickerPlatform.instance;
        FilePickerPlatform.instance = _Picker();
        addTearDown(() => FilePickerPlatform.instance = originalPicker);
        final api = await startSmokeApp(tester, {
          ...(parent ? parentOfTwoRoutes() : learnerRoutes()),
          'GET v2/me/context': ok({
            ...academicContextFixture(role: parent ? 'Parent' : 'Student'),
            'user': {
              'id': account,
              'first_name': 'Synthetic',
              'last_name': 'Learner',
              'name': 'Synthetic Learner',
              'email': parent ? parentEmail : 'learner@example.test',
              'image': null,
              'roles': [parent ? 'Parent' : 'Student'],
            },
            'approval': {'status': 'approved', 'approved_by_count': 1},
            if (parent)
              'children': [
                for (final (id, name) in [(11, 'Thandi'), (12, 'Sipho')])
                  {
                    'id': id,
                    'user_id': id,
                    'first_name': name,
                    'last_name': 'Mokoena',
                    'name': '$name Mokoena',
                    'image': null,
                    'school': {
                      'id': 1,
                      'name': 'Smoke Test School',
                      'short_name': null,
                      'crest_url': null,
                    },
                  },
              ],
            'feature_flags': {'module_switches': true},
            'modules': [
              for (final module in [
                'school',
                'people',
                'academics',
                'tutor',
                'web_learner',
                'web_parent',
              ])
                {'key': module, 'read_only': false},
            ],
          }),
          'GET v2/tutor/configuration': (RequestOptions _) => guard(
            scope({
              'learner_id': 11,
              'session_year_id': 2,
              'groups': [
                {
                  'id': 3,
                  'name': '7A maths',
                  'subject_id': 4,
                  'subject_name': 'Mathematics',
                  'sources': [
                    {'id': 5, 'title': 'Fractions'},
                  ],
                },
              ],
              'inference_enabled': true,
              'photo_requires_approved_crop': true,
              'feedback_is_provisional': true,
            }),
          ),
          'GET v2/tutor/sessions': (RequestOptions _) => guard(
            scope({
              'learner_id': 11,
              'page': 1,
              'last_page': 1,
              'sessions': posted
                  ? [
                      {'id': _session, 'subject_id': 4, 'state': 'open'},
                    ]
                  : [],
            }),
          ),
          'POST v2/tutor/sessions': (RequestOptions request) {
            posted = true;
            return guard(session());
          },
          'GET v2/tutor/sessions/$_session': (RequestOptions _) => guard({
            ...session(),
            'page': 1,
            'last_page': 1,
            'turns': [if (turn != null) turn],
            'pending_photos': [if (uploaded && turn == null) receipt()],
          }),
          'POST v2/tutor/sessions/$_session/photos': (RequestOptions request) {
            final form = request.data as FormData;
            final fields = Map.fromEntries(form.fields);
            expect(fields['approved'], 'true');
            expect(int.parse(fields['crop_width']!), greaterThan(1));
            expect(form.files.single.value.filename, 'working.png');
            operation = fields['operation_id'];
            uploaded = true;
            return guard(receipt());
          },
          'GET v2/tutor/sessions/$_session/photos/$_photo':
              (RequestOptions _) => FakeBinaryResponse(
                base64Decode(_pixels),
                contentType: 'image/png',
              ),
          'POST v2/tutor/sessions/$_session/messages': (RequestOptions request) {
            sends++;
            final body = request.data is String
                ? jsonDecode(request.data as String) as Map
                : request.data as Map;
            expect(body['operation_id'], operation);
            expect(body['photo_ids'], [_photo]);
            turn = {
              'id': '10000000-0000-4000-8000-000000000004',
              'operation_id': operation,
              'state': 'completed',
              'question': body['question'],
              'working': body['working'],
              'failure_code': null,
              'support_message': null,
              'provisional': true,
              'response': {
                'subject_token': 'synthetic-subject-token',
                'kind': 'hint',
                'text': 'Compare the equal parts.',
                'question': 'Which part did you shade?',
              },
            };
            // The service persisted the receipt, but this response was lost.
            return const FakeResponse({
              'message': 'Synthetic connection lost.',
            }, status: 503);
          },
          'GET v2/academics/content/learners/11': (RequestOptions _) => guard({
            'learner_id': 11,
            'page': 1,
            'last_page': 1,
            'items': [
              {
                'id': 1,
                'version_id': 5,
                'title': 'Fractions',
                'summary': 'Published school content',
                'subject_id': 4,
                'subject_name': 'Mathematics',
                'published_at': '2026-10-25T00:00:00Z',
              },
            ],
          }),
          'GET v2/tutor/parent/learners/11/subjects/4/summary':
              (RequestOptions _) => guard(
                scope({
                  'learner_id': 11,
                  'subject_id': 4,
                  'subject_name': 'Mathematics',
                  'learning_sessions': 2,
                  'learning_steps': 3,
                  'suggestion_key':
                      'Ask your child to explain one worked example.',
                  'contains_raw_chat': false,
                }),
              ),
        });
        await tapWhenReady(
          tester,
          find.text(parent ? 'Login as Parent' : 'Login as Student'),
        );
        await pumpUntilFound(
          tester,
          find.text(
            parent ? 'Email or handle' : 'Student number, GR number or handle',
          ),
        );
        await enterField(tester, 0, parent ? parentEmail : grNumber);
        await enterField(tester, 1, password);
        await tapWhenReady(
          tester,
          find.widgetWithText(CustomRoundedButton, 'Sign in'),
        );
        await pumpUntilFound(
          tester,
          find.byType(parent ? ParentHomeScreen : HomeScreen),
        );
        await tapWhenReady(
          tester,
          find.text(parent ? 'Tutor learning summary' : 'Ask your tutor'),
        );
        if (parent) {
          await pumpUntilFound(tester, find.text('Mathematics'));
          await tapWhenReady(tester, find.text('Mathematics'));
          await pumpUntilFound(tester, find.text('Completed learning steps'));
          expect(find.text('3'), findsOneWidget);
          expect(find.byType(TextField), findsNothing);
          expect(
            api.requestedPaths.where(
              (path) =>
                  path.contains('tutor/sessions') ||
                  path.contains('tutor/configuration'),
            ),
            isEmpty,
          );
        } else {
          await pumpUntilFound(
            tester,
            find.text('Start a learning conversation'),
          );
          await tapWhenReady(
            tester,
            find.text('Start a learning conversation'),
          );
          await pumpUntilFound(tester, find.text('Choose a working photo'));
          await tapWhenReady(tester, find.text('Choose a working photo'));
          try {
            await pumpUntilFound(
              tester,
              find.byKey(const Key('tutor-crop-area')),
            );
          } catch (error) {
            throw TestFailure(
              '$error\nSynthetic picker lifecycle: ${tester.binding.lifecycleState}\nSynthetic paths: ${api.requestedPaths}\nSynthetic visible labels: ${find.byType(Text).evaluate().map((element) => (element.widget as Text).data).toList()}',
            );
          }
          final crop = find.byKey(const Key('tutor-crop-area'));
          await tester.ensureVisible(crop);
          await tester.pump(const Duration(milliseconds: 300));
          final rectangle = tester.getRect(crop);
          await tester.dragFrom(
            rectangle.topLeft + const Offset(10, 10),
            Offset(rectangle.width * .7, rectangle.height * .7),
          );
          await tapWhenReady(
            tester,
            find.text('I checked this selection contains only my working.'),
          );
          await tapWhenReady(tester, find.text('Use this working crop'));
          await pumpUntilFound(
            tester,
            find.text('Your approved working crop is ready for this turn.'),
          );
          final question = find.byWidgetPredicate(
            (widget) =>
                widget is TextField &&
                widget.decoration?.labelText == 'Your question',
          );
          final working = find.byWidgetPredicate(
            (widget) =>
                widget is TextField &&
                widget.decoration?.labelText == 'Show what you tried',
          );
          await tester.ensureVisible(question);
          await tester.enterText(question, 'How do fractions work?');
          await tester.ensureVisible(working);
          await tester.enterText(working, 'I drew two equal parts.');
          await tester.scrollUntilVisible(
            find.text('Ask for a hint'),
            150,
            scrollable: find.byType(Scrollable).first,
          );
          await tapWhenReady(tester, find.text('Ask for a hint'));
          await pumpUntilFound(tester, find.text('Check reply'));
          api.routes['GET v2/tutor/sessions/$_session/turns/$operation'] = (
            RequestOptions _,
          ) => guard(scope(turn!));
          await tapWhenReady(tester, find.text('Check reply'));
          await pumpUntilFound(tester, find.text('Compare the equal parts.'));
          expect(find.text('Which part did you shade?'), findsOneWidget);
          expect(sends, 1);
        }
        for (final request in api.requests.where(
          (request) => request.path.contains('/tutor/'),
        )) {
          expect(request.headers['X-School-Id'], '1');
          expect(request.headers['X-Account-Id'], '$account');
        }
        tester.binding.handleAppLifecycleStateChanged(
          AppLifecycleState.inactive,
        );
        await tester.pump();
        expect(find.text('Compare the equal parts.'), findsNothing);
        expect(find.text('3'), findsNothing);
        permitted = false;
        tester.binding.handleAppLifecycleStateChanged(
          AppLifecycleState.resumed,
        );
        await pumpUntilFound(
          tester,
          find.text(
            parent
                ? 'Reopen the tutor summary to check your access.'
                : 'Your tutor access changed. Reopen it after refreshing.',
          ),
        );
        expect(find.text('Compare the equal parts.'), findsNothing);
        expect(find.byType(TextField), findsNothing);
        expect(tester.takeException(), isNull);
      },
    );
  }
}
