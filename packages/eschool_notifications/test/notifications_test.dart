import 'dart:convert';

import 'package:eschool_notifications/eschool_notifications.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// A stand-in for an app's ChatUser.
class TestSender implements ChatSender {
  TestSender(this.userId, this.userName, this.profileUrl);

  factory TestSender.fromApi(Map<dynamic, dynamic> json) => TestSender(
    json['user_id'] as int,
    '${json['first_name']} ${json['last_name']}',
    json['image'] as String,
  );

  factory TestSender.fromMap(Map<String, dynamic> map) =>
      TestSender(map['userId'] as int, map['name'] as String, map['image']);

  @override
  final int userId;
  @override
  final String userName;
  @override
  final String profileUrl;

  @override
  Map<String, dynamic> toMap() => {
    'userId': userId,
    'name': userName,
    'image': profileUrl,
  };
}

final List<(String, Map<String, dynamic>)> taps = [];

Future<void> backgroundHandler(RemoteMessage message) async {}

final NotificationConfig config = NotificationConfig(
  basicChannelDescription: 'General notification channel',
  typesNotCounted: ['noticeboard', 'chat'],
  includeDataInPayload: true,
  onTap: (type, data) => taps.add((type, data)),
  backgroundHandler: backgroundHandler,
  chatSenderFromApi: TestSender.fromApi,
  chatSenderFromMap: TestSender.fromMap,
);

RemoteMessage chatPush() => RemoteMessage(
  data: {
    'type': 'chat',
    'title': 'New message',
    'body': 'Hello',
    'sender_info': jsonEncode({
      'user_id': 9,
      'first_name': 'Tara',
      'last_name': 'Naidoo',
      'image': 'https://school.test/tara.jpg',
      'last_message': {'id': 3, 'body': 'Hello', 'sender_id': 9},
    }),
  },
);

void main() {
  setUp(() {
    SharedPreferences.setMockInitialValues({});
    taps.clear();
    // iOS shows pushes itself, so no local notification is created here.
    debugDefaultTargetPlatformOverride = TargetPlatform.iOS;
    NotificationUtility.configure(config, session: InMemorySessionRepository());
    NotificationUtility.unreadCount.value = 0;
  });

  tearDown(() => debugDefaultTargetPlatformOverride = null);

  group('topics', () {
    test('each audience subscribes to its own topic and the one for all', () {
      debugDefaultTargetPlatformOverride = TargetPlatform.android;
      expect(NotificationAudience.parents.topics, [
        'parentsAndroid',
        'allAndroid',
      ]);
      expect(NotificationAudience.students.topics, [
        'studentsAndroid',
        'allAndroid',
      ]);
      debugDefaultTargetPlatformOverride = TargetPlatform.iOS;
      expect(NotificationAudience.teachers.topics, ['teachersIOS', 'allIOS']);
    });

    test('there are none where push is not supported', () {
      debugDefaultTargetPlatformOverride = TargetPlatform.macOS;
      expect(NotificationAudience.teachers.topics, isEmpty);
      expect(NotificationPlatform.isSupported, isFalse);
    });
  });

  group('ChatNotificationData', () {
    test('reads the sender and message from a push', () {
      final data = ChatNotificationData.fromRemoteMessage(
        remoteMessage: chatPush(),
        config: config,
      );

      expect(data.fromUser.userName, 'Tara Naidoo');
      expect(data.fromUser, isA<TestSender>());
      expect(data.receivedMessage.message, 'Hello');
      expect(data.receivedMessage.senderId, 9);
    });

    test('keeps the stored format through the app\'s own map', () {
      final data = ChatNotificationData.fromRemoteMessage(
        remoteMessage: chatPush(),
        config: config,
      );

      final copy = ChatNotificationData.fromJson(data.toJson(), config: config);

      expect(copy.fromUser.userId, 9);
      expect(copy.toMap(), data.toMap());
      expect(jsonDecode(data.toJson()), contains('fromUser'));
    });
  });

  group('the unread badge', () {
    test('counts pushes of counted types, not notices or chat', () async {
      await NotificationUtility.foregroundMessageListener(
        const RemoteMessage(data: {'type': 'custom'}),
      );
      await NotificationUtility.foregroundMessageListener(
        const RemoteMessage(data: {'type': 'noticeboard'}),
      );

      expect(NotificationUtility.unreadCount.value, 1);
      expect(await const NotificationPreferences().getNotificationCount(), 1);
    });

    test('is kept under the key the apps always used', () async {
      await const NotificationPreferences().setNotificationCount(4);

      final prefs = await SharedPreferences.getInstance();
      expect(prefs.getInt('notificationCount'), 4);
    });
  });

  test('a chat push in the foreground goes to the open chat screens', () async {
    ChatNotificationsUtils.initialize();
    final received = <ChatNotificationData>[];
    final subscription = ChatNotificationsUtils
        .notificationStreamController
        .stream
        .listen(received.add);

    await NotificationUtility.foregroundMessageListener(chatPush());
    await Future<void>.delayed(Duration.zero);

    expect(received.single.fromUser.userId, 9);
    expect(NotificationUtility.unreadCount.value, 0);
    await subscription.cancel();
  });

  test('a chat push in the background is kept for the next launch', () async {
    await NotificationUtility.handleBackgroundMessage(chatPush(), config);
    // The handler writes without waiting, as it always did.
    await Future<void>.delayed(Duration.zero);

    final kept = await const NotificationPreferences()
        .getBackgroundChatNotificationData(config: config);
    expect(kept.single.fromUser.userName, 'Tara Naidoo');
  });

  group('a context.changed push', () {
    tearDown(() => NotificationUtility.onContextChanged = null);

    test(
      'in the foreground asks the app to fetch /me/context, silently',
      () async {
        var asked = 0;
        NotificationUtility.onContextChanged = () => asked++;

        await NotificationUtility.foregroundMessageListener(
          const RemoteMessage(data: {'type': 'context.changed'}),
        );

        expect(asked, 1);
        expect(NotificationUtility.unreadCount.value, 0);
        expect(await const NotificationPreferences().getNotificationCount(), 0);
      },
    );

    test('in the background is noted for when the app comes back', () async {
      const preferences = NotificationPreferences();
      expect(await preferences.takeContextChanged(), isFalse);

      await NotificationUtility.handleBackgroundMessage(
        const RemoteMessage(data: {'type': 'context.changed'}),
        config,
      );

      expect(await preferences.getNotificationCount(), 0);
      expect(await preferences.takeContextChanged(), isTrue);
      // Taken once.
      expect(await preferences.takeContextChanged(), isFalse);
    });
  });

  testWidgets('a tap opens what the app maps the type to', (tester) async {
    late BuildContext context;
    await tester.pumpWidget(
      Builder(
        builder: (built) {
          context = built;
          return const SizedBox();
        },
      ),
    );

    NotificationUtility.onMessageOpenedAppListener(
      const RemoteMessage(data: {'type': 'fees-due', 'child_id': '7'}),
      context,
    );

    expect(taps.single.$1, 'fees-due');
    expect(taps.single.$2['child_id'], '7');
    // Widget tests check that debug overrides are reset by the end.
    debugDefaultTargetPlatformOverride = null;
  });

  group('a local notification\'s payload', () {
    const Map<String, dynamic> data = {
      'type': 'Custom',
      'title': 'A notice',
      'child_id': '7',
      'link': '/my-school',
      'school_id': '2',
    };

    test('keeps the link and the school when the app keeps only the type', () {
      expect(NotificationUtility.localPayload(data, includeData: false), {
        'type': 'Custom',
        'link': '/my-school',
        'school_id': '2',
      });
    });

    test('keeps everything when the app asks for the whole payload', () {
      expect(NotificationUtility.localPayload(data, includeData: true), data);
    });

    test('a push without a link keeps only its type', () {
      expect(
        NotificationUtility.localPayload(const {
          'type': 'chat',
        }, includeData: false),
        {'type': 'chat'},
      );
    });
  });

  group('per-school topics', () {
    late List<String> calls;
    late SchoolTopics topics;

    setUp(() {
      SharedPreferences.setMockInitialValues({});
      calls = [];
      topics = SchoolTopics(
        subscribe: (topic) async => calls.add('+$topic'),
        unsubscribe: (topic) async => calls.add('-$topic'),
      );
      debugDefaultTargetPlatformOverride = TargetPlatform.android;
    });

    test('names a topic for each school and audience', () {
      expect(NotificationAudience.parents.schoolTopics([2, 5, 2]), [
        'school_2_parents_android',
        'school_2_all_android',
        'school_5_parents_android',
        'school_5_all_android',
      ]);
      debugDefaultTargetPlatformOverride = TargetPlatform.iOS;
      expect(NotificationAudience.teachers.schoolTopics([1]), [
        'school_1_teachers_ios',
        'school_1_all_ios',
      ]);
    });

    test('joins the schools\' topics and leaves the old names once', () async {
      await topics.sync(NotificationAudience.parents, [2]);

      expect(calls, [
        '-parentsAndroid',
        '-allAndroid',
        '+school_2_parents_android',
        '+school_2_all_android',
      ]);

      calls.clear();
      await topics.sync(NotificationAudience.parents, [2]);
      expect(calls, isEmpty, reason: 'nothing changed');
    });

    test('follows the user to a new school and out of an old one', () async {
      await topics.sync(NotificationAudience.parents, [1, 2]);
      calls.clear();

      await topics.sync(NotificationAudience.parents, [2, 3]);

      expect(
        calls,
        unorderedEquals([
          '+school_3_parents_android',
          '+school_3_all_android',
          '-school_1_parents_android',
          '-school_1_all_android',
        ]),
      );
    });

    test('a topic FCM fails to join is tried again', () async {
      bool fail = true;
      topics = SchoolTopics(
        subscribe: (topic) async {
          if (fail) {
            throw Exception('offline');
          }
          calls.add('+$topic');
        },
        unsubscribe: (topic) async => calls.add('-$topic'),
      );
      await topics.sync(NotificationAudience.students, [4]);
      fail = false;
      calls.clear();

      await topics.sync(NotificationAudience.students, [4]);

      expect(calls, ['+school_4_students_android', '+school_4_all_android']);
    });

    test('signing out leaves every topic joined', () async {
      await topics.sync(NotificationAudience.teachers, [1]);
      calls.clear();

      await topics.signedOut(NotificationAudience.teachers);

      expect(calls, [
        '-school_1_all_android',
        '-school_1_teachers_android',
        '-teachersAndroid',
        '-allAndroid',
      ]);
      calls.clear();
      await topics.sync(NotificationAudience.teachers, []);
      expect(calls, isEmpty);
    });
  });
}
