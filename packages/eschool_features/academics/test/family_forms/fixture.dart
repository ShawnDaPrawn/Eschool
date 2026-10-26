import 'dart:async';
import 'dart:convert';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';

const consentId = '8aef50e7-2429-4d2c-a523-668ae436c4d4';
const fixtureFileId = '8aef50e7-2429-4d2c-a523-668ae436c4d4.png';
const privateWording = 'Synthetic complete private consent wording.';
Map<String, dynamic> configJson({
  int school = 1,
  int reader = 7,
  bool writable = true,
}) => {
  'school_id': school,
  'reader_id': reader,
  'children': [
    {'id': 11, 'person_id': 12, 'name': 'Synthetic child'},
  ],
  'groups': [
    {'id': 3, 'name': 'Synthetic activity', 'session_year_id': 1},
  ],
  'writable': writable,
  'signature_name': 'Synthetic Guardian',
  'timezone': 'Africa/Johannesburg',
  'session_year_id': 1,
};
Map<String, dynamic> summaryJson() => {
  'id': consentId,
  'title': 'Synthetic trip',
  'activity_group_id': 3,
  'session_year_id': 1,
  'status': 'open',
  'event_starts_at': '2099-02-20T08:00:00Z',
  'event_ends_at': '2099-02-20T10:00:00Z',
  'respond_by': '2099-02-19T10:00:00Z',
};
Map<String, dynamic> detailJson({String? decision, int revision = 0}) => {
  ...(summaryJson()
    ..remove('activity_group_id')
    ..remove('session_year_id')),
  'learner_id': 11,
  'wording': privateWording,
  'fingerprint': 'a' * 64,
  'revision': revision,
  'decision': decision,
  'signed_at': decision == null ? null : '2026-10-26T10:00:00Z',
};
Map<String, dynamic> healthJson({int person = 12, bool file = false}) => {
  'person_id': person,
  'profile_type': 'learner',
  'available_profiles': ['learner'],
  'categories': [
    {
      'id': 1,
      'key': 'health',
      'label': 'Health',
      'fields': [
        {
          'id': 1,
          'key': 'allergies',
          'label': 'Synthetic allergy',
          'type': 'textarea',
          'validation': <String, dynamic>{},
          'options': <String>[],
          'required': false,
          'locked': false,
          'editable': true,
          'sensitive': true,
          'value': {'text': 'Synthetic private allergy'},
        },
        if (file)
          {
            'id': 2,
            'key': 'health_document',
            'label': 'Synthetic document',
            'type': 'file',
            'validation': <String, dynamic>{},
            'options': <String>[],
            'required': false,
            'locked': false,
            'editable': true,
            'sensitive': true,
            'value': {
              'file': {
                'id': fixtureFileId,
                'name': 'Synthetic medical image',
                'mime': 'image/png',
                'size': 67,
                'url': 'https://untrusted.example/private',
              },
            },
          },
      ],
    },
  ],
};

class Forms implements FamilyFormRepository {
  @override
  final int schoolId = 1, accountId = 7;
  @override
  bool isActive = true;
  bool writable = true, revoked = false, unknown = false, includeFile = false;
  int revision = 0;
  String? decision;
  final signs = <FamilyConsentSign>[];
  final receipts = <String, FamilyConsentSigned>{};
  final listings = <(int?, int?)>[];
  final healthSaves = <Map<String, ProfileValue>>[];
  final uploads = <(int, int, String, String)>[];
  Completer<FamilyConsentDetail>? pendingRead;
  Completer<ProfileForm>? pendingHealth;
  Completer<Uint8List>? pendingFile;
  void guard() {
    if (revoked) throw ApiException('revoked', statusCode: 403);
  }

  @override
  Future<FamilyFormConfiguration> configuration() async {
    guard();
    return FamilyFormConfiguration.fromJson(configJson(writable: writable));
  }

  @override
  Future<FamilyConsentPage> listing({
    int? learnerId,
    int? groupId,
    int page = 1,
  }) async {
    guard();
    listings.add((learnerId, groupId));
    return FamilyConsentPage(
      items: [FamilyConsentSummary.fromJson(summaryJson())],
      nextPage: null,
    );
  }

  @override
  Future<FamilyConsentDetail> read(String id, int learnerId) async {
    guard();
    return pendingRead?.future ??
        FamilyConsentDetail.fromJson(
          detailJson(decision: decision, revision: revision),
        );
  }

  @override
  Future<FamilyConsentManaged> managed(
    String id,
    int groupId, {
    int page = 1,
  }) async {
    guard();
    return FamilyConsentManaged.fromJson({
      ...summaryJson(),
      'wording': privateWording,
      'fingerprint': 'a' * 64,
      'revision': 1,
      'next_page': null,
      'recipients': [
        {
          'learner_id': 11,
          'name': 'Synthetic child',
          'current': true,
          'revision': 1,
          'decision': 'yes',
          'signed_at': '2026-10-26T10:00:00Z',
          'effective': false,
        },
      ],
    });
  }

  @override
  Future<FamilyConsentSigned> sign(
    String id,
    int learnerId,
    FamilyConsentSign intent,
  ) async {
    guard();
    signs.add(intent);
    final result = receipts.putIfAbsent(intent.operationId, () {
      decision = intent.decision;
      revision++;
      return FamilyConsentSigned(
        id: consentId,
        requestId: id,
        learnerId: learnerId,
        decision: decision!,
        revision: revision,
        signedAt: DateTime.utc(2026, 10, 26),
      );
    });
    if (unknown) {
      unknown = false;
      throw ApiException('offline', isTransportFailure: true);
    }
    return result;
  }

  @override
  Future<FamilyConsentCancelled> cancel(
    String id,
    String operation,
    int revision,
  ) async {
    guard();
    return FamilyConsentCancelled(
      id: id,
      status: 'cancelled',
      revision: revision + 1,
    );
  }

  @override
  Future<ProfileForm> health(int learnerId, int personId) async {
    guard();
    return pendingHealth?.future ??
        ProfileForm.fromJson(healthJson(file: includeFile));
  }

  @override
  Future<ProfileForm> saveHealth(
    int learnerId,
    int personId,
    Map<String, ProfileValue> values,
  ) async {
    guard();
    healthSaves.add(values);
    return ProfileForm.fromJson(healthJson());
  }

  @override
  Future<void> uploadHealth(
    int learnerId,
    int personId,
    String field,
    String name,
    Uint8List bytes,
  ) async {
    guard();
    uploads.add((learnerId, personId, field, name));
  }

  @override
  Future<Uint8List> healthFile(
    int learnerId,
    int personId,
    ProfileAttachment attachment,
  ) async {
    guard();
    return pendingFile?.future ??
        base64Decode(
          'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
        );
  }
}

Future<void> mount(
  WidgetTester tester,
  Widget body, {
  double width = 390,
}) async {
  tester.view.physicalSize = Size(width, 1000);
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.reset);
  rootBundle.evict('assets/languages/en.json');
  final messenger = tester.binding.defaultBinaryMessenger;
  messenger.setMockMessageHandler('flutter/assets', (message) async {
    final asset = utf8.decode(message!.buffer.asUint8List());
    if (asset.endsWith('.svg')) {
      return ByteData.sublistView(
        utf8.encode(
          '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"><path d="M1 10h18"/></svg>',
        ),
      );
    }
    if (asset == 'assets/languages/en.json') {
      return ByteData.sublistView(
        utf8.encode(
          jsonEncode({
            for (final key in sharedLabelKeys) key: key,
            'Health forms': 'Health forms',
            profileFileViewKey: 'View file',
            profileFileUploadKey: 'Upload file',
            profileSaveKey: 'Save',
            'defaultErrorMessage': 'Please try again.',
          }),
        ),
      );
    }
    return messenger.delegate.send('flutter/assets', message);
  });
  addTearDown(() => messenger.setMockMessageHandler('flutter/assets', null));
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
      builder: (_, child) => AppShell(
        errorMessageKeyFromCode: (_) => 'defaultErrorMessage',
        openStudyMaterial: (_, _) {},
        studyMaterialButton: (_, _) => const SizedBox(),
        bottomNavigationHeightPercentage: .08,
        child: child!,
      ),
      home: body,
    ),
  );
  await tester.pumpAndSettle();
}

Future<void> tap(WidgetTester tester, String text) async {
  FocusManager.instance.primaryFocus?.unfocus();
  final target = find.text(text).first;
  await tester.ensureVisible(target);
  await tester.pumpAndSettle();
  await tester.tap(target);
  await tester.pumpAndSettle();
}
