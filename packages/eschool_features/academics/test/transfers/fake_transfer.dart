import 'dart:async';
import 'dart:convert';
import 'dart:typed_data';
import 'package:eschool_academics/eschool_academics.dart';
import 'package:eschool_academics/testing.dart';
import 'package:eschool_core/eschool_core.dart';

class FakeTransfer implements TransferRepository {
  @override
  int schoolId = 1, accountId = 21;
  @override
  bool isActive = true;
  bool deny = false, writable = true, failWrite = false, conflict = false;
  int revision = 1;
  String state = 'awaiting_consent';
  final reads = <String>[], writes = <String>[];
  Completer<TransferDetail>? delayed;
  Completer<Uint8List>? delayedArchive;
  bool denyArchive = false;
  bool photoChoice = false;
  void guard() {
    if (deny) throw ApiException('Access withdrawn', statusCode: 403);
  }

  @override
  Future<TransferAccess> access() async {
    reads.add('access');
    guard();
    return TransferAccess.fromJson(transferAccessFixture(writable: writable));
  }

  @override
  Future<TransferPage> consents({int page = 1}) async {
    reads.add('consents:$page');
    guard();
    return TransferPage.fromJson(
      transferPageFixture(state: state, revision: revision, writable: writable),
    );
  }

  @override
  Future<TransferDetail> detail(int id) async {
    reads.add('detail:$id');
    guard();
    return delayed?.future ??
        TransferDetail.fromJson({
          ...transferDetailFixture(
            state: state,
            revision: revision,
            writable: writable,
          ),
          if (photoChoice)
            'gallery_choice': {
              'fingerprint': 'synthetic-photo-consent-fingerprint',
              'photos': 1,
              'can_keep': true,
            },
        });
  }

  @override
  Future<TransferWriteReceipt> write(
    int id,
    String action,
    Map<String, dynamic> payload,
  ) async {
    guard();
    writes.add('$action:${jsonEncode(payload)}');
    if (failWrite) {
      failWrite = false;
      throw ApiException('Lost response', isTransportFailure: true);
    }
    if (conflict) {
      conflict = false;
      revision++;
      throw ApiException('Changed', statusCode: 409);
    }
    revision++;
    state = action == 'consent' ? 'consented' : 'cancelled';
    return TransferWriteReceipt.fromJson({
      'id': id,
      'revision': revision,
      'kind': 'package',
    });
  }

  @override
  Future<Uint8List> archive(int id) async {
    reads.add('archive:$id');
    guard();
    if (denyArchive) throw ApiException('Access withdrawn', statusCode: 403);
    if (delayedArchive != null) return delayedArchive!.future;
    return Uint8List.fromList([0x50, 0x4b, 3, 4]);
  }
}
