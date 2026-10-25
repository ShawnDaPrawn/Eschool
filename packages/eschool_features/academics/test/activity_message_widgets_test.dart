import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'message_test_repository.dart';
import 'message_widgets_test.dart' show messageFrame, messageTap, messageField;

class _ActivityMessages extends FakeMessageRepository {
  @override
  Future<CommunicationConfiguration> configuration({int? year}) async {
    final input = messageConfigurationFixture();
    input['scopes'] = [
      {'kind': 'activity_group', 'target_id': 7, 'name': 'Hockey'},
      {'kind': 'activity_group', 'target_id': 8, 'name': 'Junior hockey'},
    ];
    return CommunicationConfiguration.fromJson(input);
  }

  @override
  Future<CommunicationMessageEditor> editor(int id) async {
    final input = messageEditorFixture();
    input['audience'] = {
      'kind': 'activity_group',
      'session_year_id': 2,
      'target_id': 7,
      'parents': true,
    };
    return CommunicationMessageEditor.fromJson(input);
  }
}

void main() {
  for (final width in [390.0, 1280.0]) {
    testWidgets(
      'activity audience renders and saves exact group and guardian toggle at ${width.toInt()}px',
      (tester) async {
        final repo = _ActivityMessages();
        await messageFrame(
          tester,
          SchoolMessageEditorScreen(repository: repo, id: 11),
          width,
        );
        expect(find.text('activity_group · Hockey'), findsOneWidget);
        final dropdown = find.byWidgetPredicate(
          (w) =>
              w is DropdownButtonFormField<String> &&
              w.decoration.labelText == 'Audience scope',
        );
        await messageTap(tester, dropdown);
        await messageTap(
          tester,
          find.text('activity_group · Junior hockey').last,
        );
        await tester.enterText(
          messageField('Message subject'),
          'Hockey notice',
        );
        await messageTap(tester, find.text('Save private message'));
        expect(repo.writes.single.audience.toJson(), {
          'kind': 'activity_group',
          'session_year_id': 2,
          'target_id': 8,
          'parents': true,
        });
        expect(tester.takeException(), isNull);
      },
    );
  }
}
