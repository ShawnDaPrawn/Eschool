import 'dart:async';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_test/flutter_test.dart';
import 'message_test_repository.dart';

void main() {
  CommunicationAudienceSelection scope() =>
      CommunicationAudienceSelection.fromJson({
        'kind': 'group',
        'session_year_id': 2,
        'target_id': 9,
        'parents': true,
      });
  test(
    'late reader response cannot restore material after a lifecycle clear',
    () async {
      final repo = FakeMessageRepository(),
          response = Completer<CommunicationMessageReader>();
      repo.fetchReader = () => response.future;
      final cubit = MessageReaderCubit(repo, 11);
      addTearDown(cubit.close);
      final loading = cubit.load();
      cubit.clear();
      response.complete(
        CommunicationMessageReader.fromJson(messageReaderFixture()),
      );
      await loading;
      expect(cubit.state.reader, isNull);
      expect(repo.reads, 0);
    },
  );
  test(
    'wrong-school or account reader data clears before acknowledgement',
    () async {
      for (final data in [
        messageReaderFixture(school: 2),
        messageReaderFixture(account: 99),
      ]) {
        final repo = FakeMessageRepository()
          ..fetchReader = () async => CommunicationMessageReader.fromJson(data);
        final cubit = MessageReaderCubit(repo, 11);
        await cubit.load();
        expect(cubit.state.reader, isNull);
        expect(repo.reads, 0);
        await cubit.close();
      }
    },
  );
  test(
    'a refused acknowledgement removes the previously loaded private body',
    () async {
      final repo = FakeMessageRepository()
        ..ackError = ApiException('Revoked', statusCode: 403);
      final cubit = MessageReaderCubit(repo, 11);
      addTearDown(cubit.close);
      await cubit.load();
      expect(cubit.state.reader, isNull);
      expect(cubit.state.error, isNotNull);
    },
  );
  test(
    'transport acknowledgement failure keeps an authorized body and supports retry',
    () async {
      final repo = FakeMessageRepository()
        ..ackError = ApiException('Offline', isTransportFailure: true);
      final cubit = MessageReaderCubit(repo, 11);
      addTearDown(cubit.close);
      await cubit.load();
      expect(cubit.state.reader, isNotNull);
      expect(cubit.state.read, isFalse);
      repo.ackError = null;
      await cubit.acknowledge();
      expect(cubit.state.read, isTrue);
      expect(repo.reads, 1);
    },
  );
  test(
    'uncertain draft retries retain exact body, audience, channel set and UUID',
    () async {
      final repo = FakeMessageRepository()
        ..writeError = ApiException('No reply', isTransportFailure: true);
      final cubit = MessageEditorCubit(repo);
      addTearDown(cubit.close);
      await cubit.load();
      final channels = {'in_app'};
      await cubit.save(
        title: 'Private text',
        markdown: '# Original body',
        audience: scope(),
        channels: channels,
      );
      channels.add('email');
      expect(cubit.state.uncertain, isTrue);
      repo.writeError = null;
      await cubit.retryPending();
      expect(repo.writes, hasLength(2));
      expect(repo.writes[1].toJson(), repo.writes[0].toJson());
      expect(repo.writes[1].channels, {'in_app'});
      expect(cubit.state.uncertain, isFalse);
    },
  );
  test(
    'conflict fetches current revision and requires comparison before another write',
    () async {
      final repo = FakeMessageRepository();
      final cubit = MessageEditorCubit(repo, messageId: 11);
      addTearDown(cubit.close);
      await cubit.load();
      repo.revision = 3;
      repo.body = 'Server text';
      repo.writeError = ApiException('Changed', statusCode: 409);
      await cubit.save(
        title: 'Local subject',
        markdown: 'Local text',
        audience: scope(),
        channels: {'in_app'},
      );
      expect(cubit.state.conflict, isTrue);
      expect(cubit.state.editor!.revision, 3);
      repo.writeError = null;
      await cubit.save(
        title: 'Local subject',
        markdown: 'Local text',
        audience: scope(),
        channels: {'in_app'},
      );
      expect(repo.writes, hasLength(1));
      cubit.acceptComparison();
      await cubit.save(
        title: 'Local subject',
        markdown: 'Local text',
        audience: scope(),
        channels: {'in_app'},
      );
      expect(repo.writes.last.revision, 3);
      expect(
        repo.writes.last.operationId,
        isNot(repo.writes.first.operationId),
      );
    },
  );
  test(
    'dirty edits invalidate preview and cannot submit or withdraw the saved body',
    () async {
      final repo = FakeMessageRepository();
      final cubit = MessageEditorCubit(repo, messageId: 11);
      addTearDown(cubit.close);
      await cubit.load();
      await cubit.preview();
      expect(cubit.state.preview, isNotNull);
      cubit.changed();
      await cubit.submitPreview();
      await cubit.withdraw('Withdraw old text');
      expect(cubit.state.preview, isNull);
      expect(repo.submits, isEmpty);
      expect(repo.withdrawals, isEmpty);
    },
  );
  test('scope loss clears draft authority and unknown retries', () async {
    final repo = FakeMessageRepository();
    final cubit = MessageEditorCubit(repo, messageId: 11);
    addTearDown(cubit.close);
    await cubit.load();
    repo.writeError = ApiException('Forbidden', statusCode: 403);
    await cubit.save(
      title: 'Local subject',
      markdown: 'Local text',
      audience: scope(),
      channels: {'in_app'},
    );
    expect(cubit.state.editor, isNull);
    expect(cubit.state.validated, isFalse);
    expect(cubit.state.uncertain, isFalse);
  });
  test(
    'failed private reload retains local-edit flags until fresh comparison',
    () async {
      final repo = FakeMessageRepository();
      final cubit = MessageEditorCubit(repo, messageId: 11);
      addTearDown(cubit.close);
      await cubit.load();
      final version = cubit.state.formVersion;
      cubit.changed();
      repo.error = ApiException('No connection', isTransportFailure: true);
      await cubit.load(keepLocal: true);
      expect(cubit.state.dirty, isTrue);
      expect(cubit.state.conflict, isTrue);
      expect(cubit.state.validated, isFalse);
      expect(cubit.state.editor, isNull);
      expect(cubit.state.formVersion, version);
      repo.error = null;
      repo.revision = 2;
      await cubit.load(keepLocal: cubit.state.dirty || cubit.state.conflict);
      expect(cubit.state.formVersion, version);
      expect(cubit.state.editor!.revision, 2);
      expect(cubit.state.conflict, isTrue);
    },
  );
  test(
    'a preview from a different revision requires reloading and comparison',
    () async {
      final repo = FakeMessageRepository();
      final cubit = MessageEditorCubit(repo, messageId: 11);
      addTearDown(cubit.close);
      await cubit.load();
      repo.revision = 2;
      await cubit.preview();
      expect(cubit.state.preview, isNull);
      expect(cubit.state.conflict, isTrue);
      expect(cubit.state.editor!.revision, 2);
    },
  );
  test(
    'message links accept canonical and reviewer identifiers and reject ambiguity',
    () {
      expect(messageLinkArguments({}, {}), isNull);
      expect(messageLinkArguments({}, {'message': '11'})!.id, 11);
      expect(messageLinkArguments({}, {'message_id': '12'})!.id, 12);
      for (final query in [
        {'message': '0'},
        {'message': '../2'},
        {'message': '11', 'message_id': '12'},
      ]) {
        expect(() => messageLinkArguments({}, query), throwsFormatException);
      }
    },
  );
}
