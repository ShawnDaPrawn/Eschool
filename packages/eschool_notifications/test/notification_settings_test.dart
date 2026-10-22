import 'dart:async';
import 'dart:convert';
import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_notifications/eschool_notifications.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'support/harness.dart';

/// What GET /api/v2/me/notification-preferences answers (Phase 1 contract).
const List<Map<String, Object?>> preferencesJson = [
  {
    'type': 'comms.notice',
    'label': 'New notice',
    'module': null,
    'channels': {
      'push': {'enabled': true, 'locked': false, 'reason': null},
      'email': {'enabled': true, 'locked': false, 'reason': null},
      'in_app': {'enabled': true, 'locked': false, 'reason': null},
    },
  },
  {
    'type': 'approval.requested',
    'label': 'Approval needed',
    'module': null,
    'channels': {
      'push': {
        'enabled': true,
        'locked': true,
        'reason': 'Your school needs your approval to give access.',
      },
      'email': {'enabled': true, 'locked': true, 'reason': null},
    },
  },
];

class FakeSettings implements NotificationSettingsRepository {
  List<NotificationTypePreference>? answer = [
    for (final Map<String, Object?> json in preferencesJson)
      NotificationTypePreference.fromJson(json),
  ];
  Object? loadError;
  Object? saveError;
  Completer<void>? saving;
  final List<(String, String, bool)> saved = [];

  @override
  Future<List<NotificationTypePreference>?> fetch() async {
    if (loadError != null) {
      throw loadError!;
    }
    return answer;
  }

  @override
  Future<void> update({
    required String type,
    required String channel,
    required bool enabled,
  }) async {
    await saving?.future;
    if (saveError != null) {
      throw saveError!;
    }
    saved.add((type, channel, enabled));
  }
}

class _Adapter implements HttpClientAdapter {
  _Adapter(this.status, this.body);

  final int status;
  final Object body;
  final List<RequestOptions> requests = [];

  @override
  Future<ResponseBody> fetch(
    RequestOptions options,
    Stream<Uint8List>? requestStream,
    Future<void>? cancelFuture,
  ) async {
    requests.add(options);
    return ResponseBody.fromString(
      jsonEncode(body),
      status,
      headers: {
        Headers.contentTypeHeader: [Headers.jsonContentType],
      },
    );
  }

  @override
  void close({bool force = false}) {}
}

ApiClient _client(_Adapter adapter) => ApiClient(
  authToken: () => 'token',
  onUnauthorized: () {},
  createDio: () => Dio()..httpClientAdapter = adapter,
);

void main() {
  group('ApiNotificationSettingsRepository', () {
    test('reads every type with its channels', () async {
      final adapter = _Adapter(200, {
        'error': false,
        'message': '',
        'data': preferencesJson,
        'code': 200,
      });

      final preferences = await ApiNotificationSettingsRepository(
        _client(adapter),
      ).fetch();

      expect(
        adapter.requests.single.uri.path,
        '/api/v2/me/notification-preferences',
      );
      expect(preferences, hasLength(2));
      expect(preferences!.first.channels.keys, ['push', 'email', 'in_app']);
      expect(preferences.last.hasLock, isTrue);
      expect(
        preferences.last.lockReason,
        'Your school needs your approval to give access.',
      );
      // A channel the school does not offer is left out.
      expect(preferences.last.channels.containsKey('in_app'), isFalse);
    });

    test('answers null before the endpoint is deployed', () async {
      final adapter = _Adapter(404, {'message': 'Not Found'});
      final repository = ApiNotificationSettingsRepository(_client(adapter));

      expect(await repository.fetch(), isNull);
      expect(await notificationSettingsDeployed(repository), isFalse);
    });

    test('saves one switch with PUT', () async {
      final adapter = _Adapter(200, {'error': false, 'data': null});

      await ApiNotificationSettingsRepository(
        _client(adapter),
      ).update(type: 'comms.notice', channel: 'email', enabled: false);

      final request = adapter.requests.single;
      expect(request.method, 'PUT');
      expect(request.data, {
        'type': 'comms.notice',
        'channel': 'email',
        'enabled': false,
      });
    });
  });

  group('NotificationSettingsCubit', () {
    late FakeSettings repository;
    late NotificationSettingsCubit cubit;

    setUp(() {
      repository = FakeSettings();
      cubit = NotificationSettingsCubit(repository);
    });

    tearDown(() => cubit.close());

    test('loads the settings', () async {
      await cubit.load();

      expect(cubit.state.status, NotificationSettingsStatus.loaded);
      expect(cubit.state.preferences, hasLength(2));
    });

    test('says when the server has none yet', () async {
      repository.answer = null;

      await cubit.load();

      expect(cubit.state.status, NotificationSettingsStatus.unavailable);
    });

    test('a failed load can be tried again', () async {
      repository.loadError = ApiException('301');
      await cubit.load();
      expect(cubit.state.status, NotificationSettingsStatus.failure);
      expect(cubit.state.error!.errorMessage, '301');

      repository.loadError = null;
      await cubit.load();
      expect(cubit.state.status, NotificationSettingsStatus.loaded);
    });

    test('a switch shows at once and is saved', () async {
      await cubit.load();
      repository.saving = Completer<void>();

      final Future<void> saving = cubit.setEnabled(
        'comms.notice',
        'email',
        false,
      );
      expect(cubit.state.preferences.first.channels['email']!.enabled, isFalse);
      expect(cubit.state.saving, {'comms.notice/email'});
      repository.saving!.complete();
      await saving;

      expect(repository.saved, [('comms.notice', 'email', false)]);
      expect(cubit.state.saving, isEmpty);
    });

    test('a refused switch goes back and says why', () async {
      await cubit.load();
      repository.saveError = ApiException(
        'Your school sends this to everyone.',
        isApiMessage: true,
      );

      await cubit.setEnabled('comms.notice', 'push', false);

      expect(cubit.state.preferences.first.channels['push']!.enabled, isTrue);
      expect(
        cubit.state.saveError!.errorMessage,
        'Your school sends this to everyone.',
      );
    });

    test('a locked channel is never changed', () async {
      await cubit.load();

      await cubit.setEnabled('approval.requested', 'push', false);

      expect(repository.saved, isEmpty);
      expect(cubit.state.preferences.last.channels['push']!.enabled, isTrue);
    });
  });

  group('NotificationSettingsScreen', () {
    late FakeSettings repository;

    setUp(() => repository = FakeSettings());

    for (final Size size in [phone, desktop]) {
      testWidgets('lists each type with a switch per channel '
          '(${size.width.toInt()} px)', (tester) async {
        await pumpScreen(
          tester,
          NotificationSettingsScreen.withRepository(repository),
          size: size,
        );

        expect(find.text('Notification settings'), findsOneWidget);
        expect(find.text('New notice'), findsOneWidget);
        expect(find.text('Approval needed'), findsOneWidget);
        expect(find.byType(Switch), findsNWidgets(5));
        expect(tester.takeException(), isNull);
      });
    }

    testWidgets('turning off optional email takes one tap', (tester) async {
      await pumpScreen(
        tester,
        NotificationSettingsScreen.withRepository(repository),
      );

      await tester.tap(find.bySemanticsLabel('New notice, Email'));
      await tester.pumpAndSettle();

      expect(repository.saved, [('comms.notice', 'email', false)]);
    });

    testWidgets('a locked notice shows the lock and the reason, and stays on', (
      tester,
    ) async {
      await pumpScreen(
        tester,
        NotificationSettingsScreen.withRepository(repository),
      );

      expect(find.byIcon(Icons.lock), findsOneWidget);
      expect(
        find.text('Your school needs your approval to give access.'),
        findsOneWidget,
      );
      final Finder lockedPush = find.bySemanticsLabel(
        'Approval needed, Push, Your school needs your approval to give access.',
      );
      await tester.tap(lockedPush);
      await tester.pumpAndSettle();
      expect(repository.saved, isEmpty);
    });

    testWidgets('a refused change says why', (tester) async {
      repository.saveError = ApiException(
        'Your school sends this to everyone.',
        isApiMessage: true,
      );
      await pumpScreen(
        tester,
        NotificationSettingsScreen.withRepository(repository),
      );

      await tester.tap(find.bySemanticsLabel('New notice, Push'));
      await tester.pumpAndSettle();

      expect(find.text('Your school sends this to everyone.'), findsOneWidget);
    });

    testWidgets('a designed empty state', (tester) async {
      repository.answer = [];
      await pumpScreen(
        tester,
        NotificationSettingsScreen.withRepository(repository),
      );
      expect(find.text('Nothing to set up yet.'), findsOneWidget);
    });

    testWidgets('a designed state before the server has settings', (
      tester,
    ) async {
      repository.answer = null;
      await pumpScreen(
        tester,
        NotificationSettingsScreen.withRepository(repository),
      );
      expect(find.text('This is not available yet.'), findsOneWidget);
    });

    testWidgets('a designed error state with a retry', (tester) async {
      repository.loadError = ApiException('302');
      await pumpScreen(
        tester,
        NotificationSettingsScreen.withRepository(repository),
      );
      expect(find.text('Something went wrong'), findsOneWidget);

      repository.loadError = null;
      await tester.tap(find.text('Try again'));
      await tester.pumpAndSettle();
      expect(find.text('New notice'), findsOneWidget);
    });

    testWidgets('every switch has a name and a large target', (tester) async {
      final SemanticsHandle semantics = tester.ensureSemantics();
      await pumpScreen(
        tester,
        NotificationSettingsScreen.withRepository(repository),
      );

      expect(
        tester.getSemantics(find.bySemanticsLabel('New notice, In the app')),
        isSemantics(
          label: 'New notice, In the app',
          hasToggledState: true,
          isToggled: true,
          hasEnabledState: true,
          isEnabled: true,
          hasTapAction: true,
        ),
      );
      expect(
        tester.getSemantics(
          find.bySemanticsLabel(
            'Approval needed, Email, Your school needs your approval to give access.',
          ),
        ),
        isSemantics(hasEnabledState: true, isEnabled: false, isToggled: true),
      );
      await expectLater(tester, meetsGuideline(androidTapTargetGuideline));
      await expectLater(tester, meetsGuideline(labeledTapTargetGuideline));
      await expectLater(tester, meetsGuideline(textContrastGuideline));
      semantics.dispose();
    });
  });
}
