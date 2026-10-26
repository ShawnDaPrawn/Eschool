import 'package:dio/dio.dart';
import 'package:eschool_academics/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:hive/hive.dart';

import 'facilitator_notice_destinations_test.dart' show frame, tap;
import 'facilitator_test_fixture.dart';

void main() {
  for (final width in [390.0, 1280.0]) {
    testWidgets(
      'scoped group notice opens the actual reader and clears withdrawn access at $width',
      (tester) async {
        final api = staffApi(),
            chat = GroupChatClientFixture(persona: 'staff', account: 12);
        bool groups = true, marked = false;
        Map<String, dynamic> profile() => {
          ...staffProfileFixture(),
          'features': {'groups': groups, 'private_notices': true},
        };
        api.routes.addAll(chat.routes());
        api.routes.addAll({
          'POST v2/facilitator/sign-in': (RequestOptions _) => {
            'error': false,
            'data': {
              'authenticated': true,
              'token': '12|synthetic-facilitator-token',
              'profile': profile(),
              'password_change_required': false,
              'documents': [],
            },
          },
          'GET v2/facilitator/session': (RequestOptions _) => {
            'error': false,
            'data': profile(),
          },
          'GET v2/staff/private-notices': (RequestOptions _) => {
            'error': false,
            'data': {
              'school_id': 1,
              'reader_id': 12,
              'writable': true,
              'page': 1,
              'last_page': 1,
              'unread_count': marked ? 0 : 1,
              'items': [
                {
                  'id': 73,
                  'feature': 'group_chat',
                  'title': 'Group conversation updated',
                  'message':
                      'Open Activity groups to read an available message.',
                  'writable': true,
                  'read_at': marked ? '2026-02-10T09:00:00Z' : null,
                },
              ],
            },
          },
          'POST v2/staff/private-notices/73/read': (RequestOptions _) {
            marked = true;
            return {
              'error': false,
              'data': {
                'id': 73,
                'school_id': 1,
                'reader_id': 12,
                'read_at': '2026-02-10T09:00:00Z',
              },
            };
          },
        });
        final repository = await frame(tester, api, width);
        await tap(tester, find.text('Group conversation updated'));
        expect(marked, isTrue);
        await tap(tester, find.text('Hockey'));
        await tap(tester, find.text('Group conversations'));
        await tap(tester, find.text('Fixture change'));
        expect(find.text('Synthetic private coach question'), findsOneWidget);
        expect(
          api.requests
              .where(
                (r) =>
                    r.path.contains('/activities/') ||
                    r.path.contains('/staff/private-notices'),
              )
              .every(
                (r) =>
                    r.headers['X-School-Id'] == '1' &&
                    r.headers['X-Account-Id'] == '12',
              ),
          isTrue,
        );
        groups = false;
        await tester.runAsync(repository.refresh);
        await tester.pumpAndSettle();
        expect(find.text('Synthetic private coach question'), findsNothing);
        expect(Hive.isBoxOpen('authBox'), isFalse);
        expect(tester.takeException(), isNull);
        await tester.pumpWidget(const SizedBox.shrink());
      },
    );
  }
}
