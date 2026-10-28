import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

import 'wallet_repository.dart';

abstract interface class TicketRepository {
  int get schoolId;
  int get accountId;
  bool get isActive;
  Future<AdmissionTicketPage> tickets({int page = 1});
  Future<AdmissionReceiveCode> receiveCode();
  Future<AdmissionTransferPreview> preview(int ticket, String receiveCode);
  Future<AdmissionTransferAck> transfer(
    AdmissionTransferPreview preview,
    String receiveCode,
    String operation,
  );
  Future<WalletOperationResolution> resolve(String operation);
}

class ApiTicketRepository implements TicketRepository {
  ApiTicketRepository(this.client, {required this.wallets});
  final ApiClient client;
  final WalletRepository wallets;
  final Set<int> _owners = {}, _learners = {}, _tickets = {};
  String? _currency;
  @override
  int get schoolId => wallets.schoolId;
  @override
  int get accountId => wallets.accountId;
  @override
  bool get isActive => wallets.isActive;
  Never _invalid() => throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
  void _guard() {
    if (!isActive) {
      throw ApiException(
        'Your ticket access changed. Reopen Wallets.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
  }

  void _integers(Object? value) {
    if (value is Map) {
      for (final entry in value.entries) {
        final key = entry.key.toString();
        if ((key == 'id' ||
                key.endsWith('_cents') ||
                (key.endsWith('_id') && key != 'operation_id') ||
                ['revision', 'page', 'last_page'].contains(key)) &&
            entry.value != null &&
            entry.value is! int) {
          _invalid();
        }
        _integers(entry.value);
      }
    } else if (value is List) {
      for (final item in value) {
        _integers(item);
      }
    }
  }

  Future<Map<String, dynamic>> _send(
    String method,
    String path, {
    Map<String, dynamic>? data,
    Map<String, dynamic>? query,
    String? operation,
  }) async {
    _guard();
    final reply = await client.send(
      method: method,
      url: '${AppConfig.apiV2Url}commerce/tickets$path',
      data: data,
      queryParameters: query,
      schoolId: schoolId,
      accountId: accountId,
      idempotencyKey: operation,
      privateRequest: true,
      requestTimeout: const Duration(seconds: 20),
    );
    _guard();
    if (reply?['data'] is! Map) _invalid();
    final out = Map<String, dynamic>.from(reply!['data'] as Map);
    _integers(out);
    if (out['school_id'] != schoolId || out['actor_user_id'] != accountId) {
      _invalid();
    }
    return out;
  }

  Future<void> _freshOwners() async {
    _guard();
    final fresh = await wallets.index();
    _guard();
    _owners.clear();
    _learners.clear();
    _currency = fresh.currency;
    for (final owner in fresh.owners) {
      _owners.add(owner.ownerUserId);
      if (owner.learnerId != null) _learners.add(owner.learnerId!);
    }
  }

  void _ticket(AdmissionTicket ticket) {
    final named = ticket.kind == AdmissionTicketKindEnum.named;
    final redeemed = ticket.status == AdmissionTicketStatusEnum.redeemed;
    if (ticket.schoolId != schoolId ||
        ticket.actorUserId != accountId ||
        !_owners.contains(ticket.holderUserId) ||
        ticket.id < 1 ||
        ticket.typeId < 1 ||
        ticket.event.id < 1 ||
        ticket.revision < 0 ||
        ticket.currency != _currency ||
        ticket.unitPriceCents < 0 ||
        ticket.unitPriceCents > 100000000000 ||
        ticket.kind == AdmissionTicketKindEnum.unknownDefaultOpenApi ||
        ticket.status == AdmissionTicketStatusEnum.unknownDefaultOpenApi ||
        ticket.transferRule ==
            AdmissionTicketTransferRuleEnum.unknownDefaultOpenApi ||
        ticket.event.status == AdmissionEventStatusEnum.unknownDefaultOpenApi ||
        !ticket.event.endsAt.isAfter(ticket.event.startsAt) ||
        (named &&
            (!_learners.contains(ticket.namedLearnerId) ||
                ticket.namedLearnerName?.trim().isNotEmpty != true)) ||
        (!named &&
            (ticket.namedLearnerId != null ||
                ticket.namedLearnerName != null)) ||
        (redeemed !=
            (ticket.redeemedAt != null && ticket.redeemedBy != null)) ||
        (redeemed &&
            (ticket.qr != null ||
                ticket.ticketNumber != null ||
                ticket.canTransfer)) ||
        ((ticket.qr == null) != (ticket.ticketNumber == null)) ||
        (ticket.qr != null &&
            !RegExp(
              r'^navigateed:ticket:[a-f0-9]{64}$',
            ).hasMatch(ticket.qr!)) ||
        (ticket.ticketNumber != null &&
            !RegExp(r'^TK-[A-Z0-9]{20}$').hasMatch(ticket.ticketNumber!))) {
      _invalid();
    }
  }

  @override
  Future<AdmissionTicketPage> tickets({int page = 1}) async {
    await _freshOwners();
    _tickets.clear();
    final out = AdmissionTicketPage.fromJson(
      await _send('GET', '', query: {'page': page}),
    );
    if (page < 1 ||
        page > 1000000 ||
        out.page != page ||
        out.lastPage < 1 ||
        out.items.length > 30 ||
        out.items.map((t) => t.id).toSet().length != out.items.length) {
      _invalid();
    }
    for (final ticket in out.items) {
      _ticket(ticket);
      _tickets.add(ticket.id);
    }
    return out;
  }

  @override
  Future<AdmissionReceiveCode> receiveCode() async {
    final out = AdmissionReceiveCode.fromJson(
      await _send('POST', '/receive-code', data: {}),
    );
    if (out.receiveCode.isEmpty || out.receiveCode.length > 8192) _invalid();
    return out;
  }

  @override
  Future<AdmissionTransferPreview> preview(
    int ticket,
    String receiveCode,
  ) async {
    if (!_tickets.contains(ticket) ||
        receiveCode.isEmpty ||
        receiveCode.length > 8192) {
      throw ArgumentError('Choose a current ticket and receiving code.');
    }
    final out = AdmissionTransferPreview.fromJson(
      await _send(
        'POST',
        '/$ticket/transfer-preview',
        data: {'receive_code': receiveCode},
      ),
    );
    if (out.ticketId != ticket ||
        out.recipientId < 1 ||
        out.revision < 0 ||
        out.recipientName.trim().isEmpty ||
        out.kind == AdmissionTransferPreviewKindEnum.unknownDefaultOpenApi) {
      _invalid();
    }
    return out;
  }

  @override
  Future<AdmissionTransferAck> transfer(
    AdmissionTransferPreview preview,
    String receiveCode,
    String operation,
  ) async {
    if (preview.schoolId != schoolId ||
        preview.actorUserId != accountId ||
        !_tickets.contains(preview.ticketId) ||
        receiveCode.isEmpty ||
        receiveCode.length > 8192 ||
        !RegExp(r'^[0-9a-f-]{36}$').hasMatch(operation)) {
      _invalid();
    }
    final out = AdmissionTransferAck.fromJson(
      await _send(
        'POST',
        '/${preview.ticketId}/transfer',
        operation: operation,
        data: {
          'operation_id': operation,
          'revision': preview.revision,
          'receive_code': receiveCode,
          'recipient_id': preview.recipientId,
          'confirmed': true,
        },
      ),
    );
    if (out.ticketId != preview.ticketId ||
        out.operationId != operation ||
        out.revision != preview.revision + 1 ||
        out.status != AdmissionTransferAckStatusEnum.transferred) {
      _invalid();
    }
    _tickets.remove(preview.ticketId);
    return out;
  }

  @override
  Future<WalletOperationResolution> resolve(String operation) =>
      _resolve(operation);

  Future<WalletOperationResolution> _resolve(String operation) {
    _guard();
    if (wallets is! WalletMoneyRepository) {
      throw StateError('Wallet recovery is unavailable.');
    }
    return (wallets as WalletMoneyRepository).resolve(operation);
  }
}
