import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';

class FakeMessageRepository implements SchoolMessageRepository {
  @override
  int schoolId = 1;
  @override
  int readerId = 12;
  @override
  bool isActive = true;
  bool staff = true, author = true, reviewer = false;
  int revision = 1, reads = 0;
  String state = 'draft',
      title = 'Private weekly update',
      body = '# Family information\n\nPrivate school text.';
  Object? error, writeError, readError, ackError;
  Future<CommunicationMessageEditor> Function()? fetchEditor;
  Future<CommunicationMessageReader> Function()? fetchReader;
  Future<CommunicationMessageReceipt> Function(CommunicationMessageWrite)?
  write;
  final writes = <CommunicationMessageWrite>[];
  final submits = <CommunicationSubmitWrite>[];
  final decisions = <CommunicationDecisionWrite>[];
  final withdrawals = <CommunicationWithdrawWrite>[];
  void check() {
    if (error != null) throw error!;
  }

  @override
  Future<CommunicationConfiguration> configuration({int? year}) async {
    check();
    return CommunicationConfiguration.fromJson(
      messageConfigurationFixture(
        staff: staff,
        school: schoolId,
        account: readerId,
      ),
    );
  }

  @override
  Future<CommunicationMessageList> messages({
    bool drafts = false,
    int page = 1,
  }) async {
    check();
    return CommunicationMessageList.fromJson(
      messageListFixture(prepared: drafts, page: page),
    );
  }

  @override
  Future<CommunicationMessageReader> reader(int id) async {
    check();
    if (readError != null) throw readError!;
    return fetchReader != null
        ? fetchReader!()
        : CommunicationMessageReader.fromJson(
            messageReaderFixture(
              school: schoolId,
              account: readerId,
              body: body,
            ),
          );
  }

  @override
  Future<CommunicationReadReceipt> read(int id) async {
    if (ackError != null) throw ackError!;
    reads++;
    return CommunicationReadReceipt(
      id: id,
      readAt: DateTime.utc(2026, 10, 24, 9, 2),
    );
  }

  @override
  Future<CommunicationMessageEditor> editor(int id) async {
    check();
    return fetchEditor != null
        ? fetchEditor!()
        : CommunicationMessageEditor.fromJson(
            messageEditorFixture(
              state: state,
              revision: revision,
              title: title,
              body: body,
              author: author,
              reviewer: reviewer,
            ),
          );
  }

  CommunicationMessageReceipt receipt() => CommunicationMessageReceipt.fromJson(
    {'id': 11, 'revision': revision, 'state': state},
  );
  @override
  Future<CommunicationMessageReceipt> save(
    int? id,
    CommunicationMessageWrite input,
  ) async {
    writes.add(input);
    if (writeError != null) throw writeError!;
    if (write != null) return write!(input);
    title = input.title;
    body = input.markdown;
    revision++;
    state = 'draft';
    return receipt();
  }

  @override
  Future<CommunicationMessagePreview> preview(int id) async {
    check();
    return CommunicationMessagePreview.fromJson(
      messagePreviewFixture(revision: revision),
    );
  }

  @override
  Future<CommunicationMessageReceipt> submit(
    int id,
    CommunicationSubmitWrite input,
  ) async {
    submits.add(input);
    check();
    revision++;
    state = 'pending';
    return receipt();
  }

  @override
  Future<CommunicationMessageReceipt> decide(
    int id,
    CommunicationDecisionWrite input,
  ) async {
    decisions.add(input);
    check();
    revision++;
    state = input.decision.value == 'approve' ? 'approved' : 'rejected';
    return receipt();
  }

  @override
  Future<CommunicationMessageReceipt> withdraw(
    int id,
    CommunicationWithdrawWrite input,
  ) async {
    withdrawals.add(input);
    check();
    revision++;
    state = 'withdrawn';
    return receipt();
  }

  @override
  Future<CommunicationContactList> contacts(int year, {int page = 1}) async =>
      CommunicationContactList.fromJson({
        'page': page,
        'last_page': page,
        'items': [],
      });
  @override
  Future<CommunicationAudienceReceipt> audience(
    int? id,
    CommunicationAudienceWrite input,
  ) async => throw UnimplementedError();
  @override
  Future<CommunicationRuleList> rules() async =>
      CommunicationRuleList.fromJson({'items': []});
  @override
  Future<CommunicationRoleRule> rule(
    int id,
    CommunicationRuleWrite input,
  ) async => throw UnimplementedError();
}
