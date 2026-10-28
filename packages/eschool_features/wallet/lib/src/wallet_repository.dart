import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

abstract interface class WalletRepository {
  int get schoolId;
  int get accountId;
  bool get isActive;
  Future<PersonalWalletIndex> index();
  Future<WalletStatement> statement(
    int wallet, {
    int page = 1,
    int perPage = 30,
  });
  Future<WalletDeskReceiptPage> deskReceipts(
    int wallet, {
    int page = 1,
    int perPage = 30,
  });
  Future<WalletStatement> open(int owner, String operation);
  Future<WalletStatement> controls(
    int wallet, {
    required int revision,
    required int dailyCents,
    required bool allCategories,
    required List<String> categories,
    required String operation,
  });
}

abstract interface class WalletMoneyRepository {
  Future<WalletTransferPage> transfers({int page = 1, int perPage = 30});
  Future<WalletTransferReceipt> transfer(
    int from,
    int to,
    int amountCents,
    String operation,
  );
  Future<WalletOperationResolution> resolve(String operation);
}

abstract interface class WalletDepositRepository {
  Future<WalletDepositPage> deposits({int page = 1, int perPage = 30});
  Future<WalletDepositReceipt> requestDeposit({
    required int amountCents,
    required String method,
    required String reference,
    required String paidOn,
    required String evidence,
    required String operation,
  });
  Future<WalletDepositReceipt> cancelDeposit(int claim, String operation);
}

abstract interface class WalletWithdrawalRepository {
  Future<WalletWithdrawalPage> withdrawals({int page = 1, int perPage = 30});
  Future<WalletWithdrawalReceipt> requestWithdrawal({
    required int amountCents,
    int? slotId,
    String? bankInstructions,
    required String operation,
  });
  Future<WalletWithdrawalReceipt> cancelWithdrawal(
    int withdrawal,
    String operation,
  );
}

class ApiWalletRepository
    implements
        WalletRepository,
        WalletMoneyRepository,
        WalletDepositRepository,
        WalletWithdrawalRepository {
  ApiWalletRepository(
    this.client, {
    required this.schoolId,
    required this.accountId,
    required this.contextActive,
  }) : _token = client.authToken();
  final ApiClient client;
  final bool Function() contextActive;
  final String _token;
  @override
  final int schoolId, accountId;
  final Set<int> _owners = {}, _wallets = {};
  final Map<int, int> _walletOwners = {};
  String? _currency;
  WalletDepositPage? _depositCapabilities;
  final Set<int> _depositClaims = {};
  WalletWithdrawalPage? _withdrawalCapabilities;
  final Set<int> _withdrawalClaims = {};
  @override
  bool get isActive =>
      schoolId > 0 &&
      accountId > 0 &&
      contextActive() &&
      (!client.sendsBearerToken || client.authToken() == _token);
  Never _invalid() => throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
  void _guard() {
    if (!isActive) {
      throw ApiException(
        'Your wallet access changed. Reopen Wallets.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
  }

  void _integers(Object? value) {
    if (value is Map) {
      for (final e in value.entries) {
        if (e.key is String) {
          final k = e.key as String;
          if ((k.endsWith('_cents') ||
                  k == 'id' ||
                  (k.endsWith('_id') && k != 'operation_id') ||
                  [
                    'revision',
                    'policy_revision',
                    'available_places',
                    'current_page',
                    'per_page',
                    'total',
                  ].contains(k)) &&
              e.value != null &&
              e.value is! int) {
            _invalid();
          }
        }
        _integers(e.value);
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
      url: '${AppConfig.apiV2Url}wallets$path',
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
    return out;
  }

  void _transferReceipt(WalletTransferReceipt row) {
    if (row.id < 1 ||
        !_uuid(row.operationId) ||
        row.schoolId != schoolId ||
        row.actorUserId != accountId ||
        row.reference != 'WX-${row.id}' ||
        row.fromWalletId < 1 ||
        row.toWalletId < 1 ||
        row.fromWalletId == row.toWalletId ||
        row.fromOwnerId < 1 ||
        row.toOwnerId < 1 ||
        row.fromOwnerId == row.toOwnerId ||
        (row.fromOwnerId == accountId) == (row.toOwnerId == accountId) ||
        row.amountCents < 1 ||
        row.amountCents > 100000000000 ||
        row.currency != _currency ||
        row.kind == WalletTransferReceiptKindEnum.unknownDefaultOpenApi ||
        (row.kind == WalletTransferReceiptKindEnum.toChild) !=
            (row.fromOwnerId == accountId)) {
      _invalid();
    }
  }

  @override
  Future<WalletTransferPage> transfers({int page = 1, int perPage = 30}) async {
    if (page < 1 || page > 1000000 || perPage < 1 || perPage > 50) {
      throw ArgumentError('Use a supported transfer page.');
    }
    final out = WalletTransferPage.fromJson(
      await _send(
        'GET',
        '/transfers',
        query: {'page': page, 'per_page': perPage},
      ),
    );
    if (out.schoolId != schoolId ||
        out.actorUserId != accountId ||
        out.currentPage != page ||
        out.perPage != perPage ||
        out.total < 0 ||
        out.items.length > perPage ||
        out.items.length > out.total ||
        out.items.map((r) => r.id).toSet().length != out.items.length ||
        out.childReturns ==
            WalletTransferPageChildReturnsEnum.unknownDefaultOpenApi ||
        (out.childReturns == WalletTransferPageChildReturnsEnum.unconfigured) !=
            (out.policyRevision == null) ||
        (out.policyRevision != null && out.policyRevision! < 1)) {
      _invalid();
    }
    for (final row in out.items) {
      _transferReceipt(row);
    }
    return out;
  }

  @override
  Future<WalletTransferReceipt> transfer(
    int from,
    int to,
    int amountCents,
    String operation,
  ) async {
    if (!_wallets.contains(from) ||
        !_wallets.contains(to) ||
        from == to ||
        amountCents < 1 ||
        amountCents > 100000000000) {
      throw ArgumentError('Choose valid wallet transfer details.');
    }
    final amount =
        '${amountCents ~/ 100}.${(amountCents % 100).toString().padLeft(2, '0')}';
    final out = WalletTransferReceipt.fromJson(
      await _send(
        'POST',
        '/transfers',
        data: {
          'operation_id': operation,
          'from_wallet_id': from,
          'to_wallet_id': to,
          'amount': amount,
          'confirmed': true,
        },
        operation: operation,
      ),
    );
    _transferReceipt(out);
    if (out.operationId != operation ||
        out.fromWalletId != from ||
        out.toWalletId != to ||
        out.fromOwnerId != _walletOwners[from] ||
        out.toOwnerId != _walletOwners[to] ||
        out.amountCents != amountCents) {
      _invalid();
    }
    return out;
  }

  @override
  Future<WalletOperationResolution> resolve(String operation) async {
    if (!_uuid(operation)) {
      throw ArgumentError('Use a valid wallet operation.');
    }
    final out = WalletOperationResolution.fromJson(
      await _send('POST', '/operations/$operation/resolve', data: {}),
    );
    if (out.schoolId != schoolId ||
        out.actorUserId != accountId ||
        out.operationId != operation ||
        out.status ==
            WalletOperationResolutionStatusEnum.unknownDefaultOpenApi) {
      _invalid();
    }
    return out;
  }

  bool _uuid(String value) => RegExp(
    r'^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$',
  ).hasMatch(value);

  void _depositReceipt(WalletDepositReceipt row) {
    final verified = row.verifiedReceipt;
    final pending = row.status == WalletDepositReceiptStatusEnum.pending;
    final credited = row.status == WalletDepositReceiptStatusEnum.verified;
    final reason = row.reasonCode;
    if (row.id < 1 ||
        row.reference != 'WD-${row.id}' ||
        row.schoolId != schoolId ||
        row.actorUserId != accountId ||
        !_uuid(row.operationId) ||
        row.walletId < 1 ||
        (_walletOwners[row.walletId] != null &&
            _walletOwners[row.walletId] != accountId) ||
        row.currency != _currency ||
        row.requestedCents < 1 ||
        row.requestedCents > 100000000000 ||
        row.requestedMethod ==
            WalletDepositReceiptRequestedMethodEnum.unknownDefaultOpenApi ||
        !_receiptReference(row.requestedReference) ||
        row.status == WalletDepositReceiptStatusEnum.unknownDefaultOpenApi ||
        reason == WalletDepositReceiptReasonCodeEnum.unknownDefaultOpenApi ||
        pending != (row.processedAt == null) ||
        pending != (reason == null) ||
        credited != (verified != null) ||
        (credited && reason != WalletDepositReceiptReasonCodeEnum.verified) ||
        (row.status == WalletDepositReceiptStatusEnum.cancelled &&
            reason != WalletDepositReceiptReasonCodeEnum.withdrawn) ||
        (row.status == WalletDepositReceiptStatusEnum.rejected &&
            ![
              WalletDepositReceiptReasonCodeEnum.notVerified,
              WalletDepositReceiptReasonCodeEnum.duplicate,
              WalletDepositReceiptReasonCodeEnum.requestIncorrect,
            ].contains(reason))) {
      _invalid();
    }
    if (verified != null) {
      if (!RegExp(r'^WP-[1-9][0-9]*$').hasMatch(verified.reference) ||
          verified.currency != row.currency ||
          verified.amountCents < 1 ||
          verified.amountCents > 100000000000 ||
          verified.feeCents != 0 ||
          verified.netCents != verified.amountCents ||
          verified.collectedBy !=
              WalletDepositReceiptVerifiedReceiptCollectedByEnum.school ||
          verified.method ==
              WalletDepositReceiptVerifiedReceiptMethodEnum
                  .unknownDefaultOpenApi ||
          !_receiptReference(verified.sourceReference) ||
          (verified.method == WalletDepositReceiptVerifiedReceiptMethodEnum.eft
              ? verified.settlementReference != verified.sourceReference
              : verified.settlementReference != null)) {
        _invalid();
      }
    }
  }

  bool _receiptReference(String value) =>
      RegExp(r'^[A-Z0-9][A-Z0-9 ._:/-]{0,95}$').hasMatch(value) &&
      value == value.trim() &&
      !value.contains('  ');

  @override
  Future<WalletDepositPage> deposits({int page = 1, int perPage = 30}) async {
    if (page < 1 || page > 1000000 || perPage < 1 || perPage > 50) {
      throw ArgumentError('Use a supported deposit page.');
    }
    final out = WalletDepositPage.fromJson(
      await _send(
        'GET',
        '/deposits',
        query: {'page': page, 'per_page': perPage},
      ),
    );
    if (out.schoolId != schoolId ||
        out.actorUserId != accountId ||
        out.currency != _currency ||
        out.currentPage != page ||
        out.perPage != perPage ||
        out.total < 0 ||
        out.items.length > perPage ||
        out.items.length > out.total ||
        out.items.map((r) => r.id).toSet().length != out.items.length ||
        (out.canSubmit &&
            (!out.isParent ||
                out.readOnly ||
                out.currency == null ||
                (!out.cashAllowed && !out.eftAllowed))) ||
        (!out.isParent && (out.cashAllowed || out.eftAllowed))) {
      _invalid();
    }
    for (final row in out.items) {
      _depositReceipt(row);
    }
    _depositCapabilities = out;
    _depositClaims
      ..clear()
      ..addAll(out.items.map((row) => row.id));
    return out;
  }

  @override
  Future<WalletDepositReceipt> requestDeposit({
    required int amountCents,
    required String method,
    required String reference,
    required String paidOn,
    required String evidence,
    required String operation,
  }) async {
    final allowed = _depositCapabilities;
    if (allowed == null ||
        !allowed.canSubmit ||
        !['cash', 'eft'].contains(method) ||
        (method == 'cash' ? !allowed.cashAllowed : !allowed.eftAllowed)) {
      throw ApiException(
        'Parent deposit methods changed. Refresh Wallets.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
    if (amountCents < 1 || amountCents > 100000000000 || !_uuid(operation)) {
      throw ArgumentError('Use supported parent deposit details.');
    }
    final canonical = reference
        .trim()
        .replaceAll(RegExp(r'\s+'), ' ')
        .toUpperCase();
    if (!_receiptReference(canonical)) {
      throw ArgumentError('Use a supported receipt reference.');
    }
    final amount =
        '${amountCents ~/ 100}.${(amountCents % 100).toString().padLeft(2, '0')}';
    final out = WalletDepositReceipt.fromJson(
      await _send(
        'POST',
        '/deposits',
        data: {
          'operation_id': operation,
          'amount': amount,
          'method': method,
          'source_reference': canonical,
          'paid_on': paidOn,
          'evidence': evidence,
          'confirmed': true,
        },
        operation: operation,
      ),
    );
    _depositReceipt(out);
    final date =
        '${out.paidOn.year.toString().padLeft(4, '0')}-${out.paidOn.month.toString().padLeft(2, '0')}-${out.paidOn.day.toString().padLeft(2, '0')}';
    if (out.operationId != operation ||
        out.requestedCents != amountCents ||
        out.requestedMethod.value != method ||
        out.requestedReference != canonical ||
        date != paidOn) {
      _invalid();
    }
    _depositClaims.add(out.id);
    return out;
  }

  @override
  Future<WalletDepositReceipt> cancelDeposit(
    int claim,
    String operation,
  ) async {
    final allowed = _depositCapabilities;
    if (!_depositClaims.contains(claim) ||
        allowed == null ||
        !allowed.isParent ||
        allowed.readOnly ||
        !_uuid(operation)) {
      throw ApiException(
        'Your wallet access changed. Reopen Wallets.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
    final out = WalletDepositReceipt.fromJson(
      await _send(
        'POST',
        '/deposits/$claim/cancel',
        data: {'operation_id': operation, 'confirmed': true},
        operation: operation,
      ),
    );
    _depositReceipt(out);
    if (out.id != claim ||
        out.status != WalletDepositReceiptStatusEnum.cancelled) {
      _invalid();
    }
    return out;
  }

  void _withdrawalReceipt(WalletWithdrawalReceipt row) {
    final cash = row.method == WalletWithdrawalReceiptMethodEnum.cash;
    final paid = row.status == WalletWithdrawalReceiptStatusEnum.paid;
    if (row.id < 1 ||
        row.reference != 'WW-${row.id}' ||
        row.schoolId != schoolId ||
        row.holderUserId != accountId ||
        !_uuid(row.operationId) ||
        _walletOwners[row.walletId] != accountId ||
        row.currency != _currency ||
        row.amountCents < 1 ||
        row.amountCents > 100000000000 ||
        row.method == WalletWithdrawalReceiptMethodEnum.unknownDefaultOpenApi ||
        row.status == WalletWithdrawalReceiptStatusEnum.unknownDefaultOpenApi ||
        (row.status == WalletWithdrawalReceiptStatusEnum.requested) !=
            (row.processedAt == null) ||
        cash != (row.pickupStartsAt != null) ||
        cash != (row.pickupEndsAt != null) ||
        cash != (row.pickupLabel != null) ||
        (cash &&
            (!row.pickupEndsAt!.isAfter(row.pickupStartsAt!) ||
                row.pickupLabel!.trim().isEmpty ||
                row.pickupLabel!.length > 160)) ||
        paid != (row.paidOn != null) ||
        paid != (row.payoutReference != null) ||
        (paid &&
            !RegExp(r'^WW-PAY-[1-9][0-9]*$').hasMatch(row.payoutReference!))) {
      _invalid();
    }
  }

  @override
  Future<WalletWithdrawalPage> withdrawals({
    int page = 1,
    int perPage = 30,
  }) async {
    if (page < 1 || page > 1000000 || perPage < 1 || perPage > 50) {
      throw ArgumentError('Use a supported withdrawal page.');
    }
    final out = WalletWithdrawalPage.fromJson(
      await _send(
        'GET',
        '/withdrawals',
        query: {'page': page, 'per_page': perPage},
      ),
    );
    final configured = out.policyRevision != null;
    if (out.schoolId != schoolId ||
        out.actorUserId != accountId ||
        out.currency != _currency ||
        out.currentPage != page ||
        out.perPage != perPage ||
        out.total < 0 ||
        out.items.length > perPage ||
        out.items.length > out.total ||
        out.items.map((r) => r.id).toSet().length != out.items.length ||
        configured != (out.cashThresholdCents != null) ||
        configured != (out.cashAtBoundary != null) ||
        (configured &&
            (out.policyRevision! < 1 ||
                out.cashThresholdCents! < 1 ||
                out.cashThresholdCents! > 100000000000 ||
                out.currency == null ||
                (out.currency == 'ZAR' && out.cashThresholdCents != 25000))) ||
        (out.canRequest &&
            (!out.isAdult ||
                out.readOnly ||
                !configured ||
                !_walletOwners.containsValue(accountId))) ||
        out.timezone.trim().isEmpty ||
        out.timezone.length > 100 ||
        out.slots.length > 100 ||
        out.slots.map((r) => r.id).toSet().length != out.slots.length ||
        (out.slots.isNotEmpty &&
            (!out.isAdult || out.readOnly || !configured))) {
      _invalid();
    }
    for (final slot in out.slots) {
      if (slot.id < 1 ||
          !slot.endsAt.isAfter(slot.startsAt) ||
          slot.availablePlaces < 1 ||
          slot.availablePlaces > 1000 ||
          slot.label.trim().isEmpty ||
          slot.label.length > 160) {
        _invalid();
      }
    }
    for (final row in out.items) {
      _withdrawalReceipt(row);
    }
    _withdrawalCapabilities = out;
    _withdrawalClaims
      ..clear()
      ..addAll(out.items.map((r) => r.id));
    return out;
  }

  @override
  Future<WalletWithdrawalReceipt> requestWithdrawal({
    required int amountCents,
    int? slotId,
    String? bankInstructions,
    required String operation,
  }) async {
    final allowed = _withdrawalCapabilities;
    if (allowed == null || !allowed.canRequest) {
      throw ApiException(
        'Your withdrawal access changed. Refresh Wallets.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
    if (amountCents < 1 || amountCents > 100000000000 || !_uuid(operation)) {
      throw ArgumentError('Use a supported withdrawal amount.');
    }
    final cash =
        amountCents < allowed.cashThresholdCents! ||
        (amountCents == allowed.cashThresholdCents && allowed.cashAtBoundary!);
    if (cash
        ? (slotId == null ||
              !allowed.slots.any((s) => s.id == slotId) ||
              bankInstructions != null)
        : (slotId != null ||
              bankInstructions == null ||
              bankInstructions.trim().length < 10 ||
              bankInstructions.length > 2000)) {
      throw ArgumentError(
        'Review the current cash pickup or original-holder bank instructions.',
      );
    }
    final row = WalletWithdrawalReceipt.fromJson(
      await _send(
        'POST',
        '/withdrawals',
        data: {
          'operation_id': operation,
          'amount':
              '${amountCents ~/ 100}.${(amountCents % 100).toString().padLeft(2, '0')}',
          'slot_id': slotId,
          'private_bank_instructions': bankInstructions?.trim(),
          'confirmed': true,
        },
        operation: operation,
      ),
    );
    _withdrawalReceipt(row);
    if (row.operationId != operation ||
        row.amountCents != amountCents ||
        (row.method == WalletWithdrawalReceiptMethodEnum.cash) != cash) {
      _invalid();
    }
    if (cash) {
      final slot = allowed.slots.singleWhere((s) => s.id == slotId);
      if (row.pickupStartsAt != slot.startsAt ||
          row.pickupEndsAt != slot.endsAt) {
        _invalid();
      }
    }
    _withdrawalClaims.add(row.id);
    return row;
  }

  @override
  Future<WalletWithdrawalReceipt> cancelWithdrawal(
    int withdrawal,
    String operation,
  ) async {
    final allowed = _withdrawalCapabilities;
    if (allowed == null ||
        !allowed.isAdult ||
        allowed.readOnly ||
        !_withdrawalClaims.contains(withdrawal) ||
        !_uuid(operation)) {
      throw ApiException(
        'Your withdrawal access changed. Refresh Wallets.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
    final row = WalletWithdrawalReceipt.fromJson(
      await _send(
        'POST',
        '/withdrawals/$withdrawal/cancel',
        data: {
          'operation_id': operation,
          'private_reason':
              'Cancelled by the original adult holder before outside payment',
          'confirmed': true,
        },
        operation: operation,
      ),
    );
    _withdrawalReceipt(row);
    if (row.id != withdrawal ||
        row.status != WalletWithdrawalReceiptStatusEnum.cancelled) {
      _invalid();
    }
    return row;
  }

  void _summary({
    required int id,
    required int school,
    required int owner,
    required String currency,
    required int balance,
    required int schoolCap,
    required bool child,
    required bool requiredControls,
    required WalletControls controls,
    Set<int>? allowedOwners,
  }) {
    if (id < 1 ||
        school != schoolId ||
        !(allowedOwners ?? _owners).contains(owner) ||
        !RegExp(r'^[A-Z]{3}$').hasMatch(currency) ||
        balance < 0 ||
        balance > 9000000000000000 ||
        schoolCap < 0 ||
        schoolCap > 100000000000 ||
        controls.revision < 0 ||
        controls.effectiveDailySpendCents < 0 ||
        controls.effectiveDailySpendCents > schoolCap ||
        controls.categories.length > 50 ||
        controls.categories.toSet().length != controls.categories.length ||
        controls.categories.any(
          (c) => !RegExp(r'^[a-z][a-z0-9_-]{0,39}$').hasMatch(c),
        ) ||
        (controls.allCategories && controls.categories.isNotEmpty)) {
      _invalid();
    }
    final daily = controls.dailySpendCents;
    if (controls.configured) {
      if (controls.revision < 1 ||
          daily == null ||
          daily < 0 ||
          daily > 100000000000 ||
          controls.effectiveDailySpendCents !=
              (daily < schoolCap ? daily : schoolCap)) {
        _invalid();
      }
    } else {
      if (controls.revision != 0 ||
          daily != null ||
          controls.effectiveDailySpendCents !=
              (child && requiredControls ? 0 : schoolCap)) {
        _invalid();
      }
    }
  }

  @override
  Future<PersonalWalletIndex> index() async {
    final out = PersonalWalletIndex.fromJson(await _send('GET', ''));
    if (out.schoolId != schoolId ||
        out.actorUserId != accountId ||
        out.owners.map((o) => o.ownerUserId).toSet().length !=
            out.owners.length) {
      _invalid();
    }
    final owners = out.owners.map((o) => o.ownerUserId).toSet();
    if (owners.any((id) => id < 1)) _invalid();
    final wallets = <int>{};
    final walletOwners = <int, int>{};
    for (final owner in out.owners) {
      if (owner.isSelf != (owner.ownerUserId == accountId) ||
          owner.wallets.length > 1 ||
          (out.currency == null && owner.canOpen)) {
        _invalid();
      }
      for (final wallet in owner.wallets) {
        if (wallet.ownerUserId != owner.ownerUserId ||
            wallet.currency != out.currency) {
          _invalid();
        }
        _summary(
          id: wallet.id,
          school: wallet.schoolId,
          owner: wallet.ownerUserId,
          currency: wallet.currency,
          balance: wallet.balanceCents,
          schoolCap: wallet.schoolDailySpendCents,
          child: wallet.isChild,
          requiredControls: wallet.childLimitsRequired,
          controls: wallet.limits,
          allowedOwners: owners,
        );
        if (!wallets.add(wallet.id)) _invalid();
        walletOwners[wallet.id] = owner.ownerUserId;
      }
    }
    _owners
      ..clear()
      ..addAll(owners);
    _wallets
      ..clear()
      ..addAll(wallets);
    _walletOwners
      ..clear()
      ..addAll(walletOwners);
    _currency = out.currency;
    return out;
  }

  WalletStatement _statement(
    Map<String, dynamic> data,
    int wallet, {
    int page = 1,
    int perPage = 30,
    int? expectedOwner,
  }) {
    final out = WalletStatement.fromJson(data);
    if (out.id != wallet ||
        out.ownerUserId != (expectedOwner ?? _walletOwners[wallet]) ||
        (_currency != null && out.currency != _currency)) {
      _invalid();
    }
    _summary(
      id: out.id,
      school: out.schoolId,
      owner: out.ownerUserId,
      currency: out.currency,
      balance: out.balanceCents,
      schoolCap: out.schoolDailySpendCents,
      child: out.isChild,
      requiredControls: out.childLimitsRequired,
      controls: out.limits,
    );
    if (out.entries.currentPage != page ||
        out.entries.perPage != perPage ||
        out.entries.total < 0 ||
        out.entries.items.length > perPage) {
      _invalid();
    }
    for (final row in out.entries.items) {
      if (row.entryId < 1 ||
          row.balanceAfterCents < 0 ||
          row.balanceAfterCents > 9000000000000000 ||
          row.changeCents.abs() > 9000000000000000) {
        _invalid();
      }
    }
    return out;
  }

  @override
  Future<WalletStatement> statement(
    int wallet, {
    int page = 1,
    int perPage = 30,
  }) async {
    if (wallet < 1 ||
        page < 1 ||
        page > 1000000 ||
        perPage < 1 ||
        perPage > 50) {
      throw ArgumentError('Use a supported wallet statement page.');
    }
    if (!_wallets.contains(wallet)) {
      throw ApiException(
        'Reopen Wallets to refresh your available accounts.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
    return _statement(
      await _send(
        'GET',
        '/$wallet/statement',
        query: {'page': page, 'per_page': perPage},
      ),
      wallet,
      page: page,
      perPage: perPage,
    );
  }

  @override
  Future<WalletStatement> open(int owner, String operation) async {
    if (!_owners.contains(owner)) {
      throw ApiException(
        'Your wallet access changed. Reopen Wallets.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
    final data = await _send(
      'POST',
      '',
      data: {'operation_id': operation, 'owner_user_id': owner},
      operation: operation,
    );
    final id = data['id'];
    if (id is! int) _invalid();
    if (_walletOwners[id] != null && _walletOwners[id] != owner) _invalid();
    final out = _statement(data, id, expectedOwner: owner);
    if (out.ownerUserId != owner) _invalid();
    _wallets.add(id);
    _walletOwners[id] = owner;
    _currency = out.currency;
    return out;
  }

  @override
  Future<WalletDeskReceiptPage> deskReceipts(
    int wallet, {
    int page = 1,
    int perPage = 30,
  }) async {
    if (page < 1 || page > 1000000 || perPage < 1 || perPage > 50) {
      throw ArgumentError('Use a supported receipt page.');
    }
    if (!_wallets.contains(wallet)) {
      throw ApiException(
        'Your wallet access changed. Reopen Wallets.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
    final out = WalletDeskReceiptPage.fromJson(
      await _send(
        'GET',
        '/$wallet/desk-topups',
        query: {'page': page, 'per_page': perPage},
      ),
    );
    if (out.schoolId != schoolId ||
        out.actorUserId != accountId ||
        out.walletId != wallet ||
        out.ownerUserId != _walletOwners[wallet] ||
        out.currency != _currency ||
        out.currentPage != page ||
        out.perPage != perPage ||
        out.total < 0 ||
        out.items.length > perPage ||
        out.items.length > out.total ||
        out.items.map((r) => r.id).toSet().length != out.items.length) {
      _invalid();
    }
    for (final row in out.items) {
      if (row.id < 1 ||
          row.reference != 'WT-${row.id}' ||
          row.currency != out.currency ||
          row.amountCents < 1 ||
          row.amountCents > 100000000000 ||
          row.netCents != row.amountCents ||
          row.feeCents != WalletDeskReceiptFeeCentsEnum.number0 ||
          row.collectedBy != WalletDeskReceiptCollectedByEnum.school ||
          row.status == WalletDeskReceiptStatusEnum.unknownDefaultOpenApi ||
          (row.status == WalletDeskReceiptStatusEnum.pending) !=
              (row.processedAt == null)) {
        _invalid();
      }
    }
    return out;
  }

  @override
  Future<WalletStatement> controls(
    int wallet, {
    required int revision,
    required int dailyCents,
    required bool allCategories,
    required List<String> categories,
    required String operation,
  }) async {
    if (!_wallets.contains(wallet)) {
      throw ApiException(
        'Your wallet access changed. Reopen Wallets.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
    return _statement(
      await _send(
        'PUT',
        '/$wallet/limits',
        data: {
          'operation_id': operation,
          'revision': revision,
          'daily_spend_cents': dailyCents,
          'all_categories': allCategories,
          'categories': categories,
        },
        operation: operation,
      ),
      wallet,
    );
  }
}
