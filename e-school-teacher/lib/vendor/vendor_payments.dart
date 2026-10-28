part of 'vendor_repository.dart';

bool _paymentUuid(String value) => RegExp(
  r'^[a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{12}$',
).hasMatch(value);

void _paymentIntegers(Object? value) {
  if (value is List) {
    for (final row in value) {
      _paymentIntegers(row);
    }
  } else if (value is Map) {
    for (final entry in value.entries) {
      final key = entry.key.toString();
      if ((key.endsWith('_cents') ||
              key.endsWith('_bps') ||
              (key.endsWith('_id') &&
                  ![
                    'intent_id',
                    'operation_id',
                    'card_id',
                    'device_id',
                  ].contains(key)) ||
              [
                'quantity',
                'revision',
                'portrait_revision',
                'product_revision',
                'page',
                'last_page',
                'available_quantity',
              ].contains(key) ||
              key == 'id' && entry.value is num) &&
          entry.value != null) {
        _integer(entry.value, min: 0);
      }
      _paymentIntegers(entry.value);
    }
  }
}

class VendorPaymentReview {
  VendorPaymentReview(
    Map<String, dynamic> data,
    VendorProfile p,
    VendorCounter c,
  ) {
    _paymentIntegers(data);
    instruction = contract.WalletPaymentInstruction.fromJson(data);
    final row = instruction;
    if (!_paymentUuid(row.id) ||
        row.schoolId != p.schoolId ||
        row.actorUserId != p.userId ||
        row.vendorId != c.id ||
        row.currency != c.currency ||
        row.seller.id != c.id ||
        !['qr', 'sun'].contains(row.kind.value) ||
        row.amountCents < 0 ||
        row.amountCents > 100000000000 ||
        row.netCents < 0 ||
        row.vatCents < 0 ||
        row.amountCents != row.netCents + row.vatCents ||
        row.lines.isEmpty ||
        row.lines.length > 20 ||
        row.lines.map((l) => l.productId).toSet().length != row.lines.length) {
      throw const FormatException('Invalid current counter instruction');
    }
    var gross = 0, net = 0, tax = 0;
    for (final line in row.lines) {
      _text(line.productName, max: 191);
      final kind = line.vatKind.value, rate = line.vatRateBps;
      if (line.productId < 1 ||
          line.productRevision < 1 ||
          line.quantity < 1 ||
          line.quantity > 999 ||
          line.unitPriceCents < 0 ||
          line.grossCents != line.quantity * line.unitPriceCents ||
          line.grossCents != line.netCents + line.vatCents ||
          line.netCents < 0 ||
          line.vatCents < 0 ||
          !['none', 'zero', 'exempt', 'standard'].contains(kind) ||
          rate < 0 ||
          rate > 10000 ||
          (kind == 'standard' ? rate == 0 : rate != 0) ||
          (row.seller.vatRegistered ? kind == 'none' : kind != 'none') ||
          line.vatCents !=
              (kind == 'standard'
                  ? (line.grossCents * rate * 2 + 10000 + rate) ~/
                        ((10000 + rate) * 2)
                  : 0)) {
        throw const FormatException('Invalid original counter price or VAT');
      }
      gross += line.grossCents;
      net += line.netCents;
      tax += line.vatCents;
    }
    if (gross != row.amountCents ||
        net != row.netCents ||
        tax != row.vatCents) {
      throw const FormatException('Invalid original counter total');
    }
    if (row.kind == contract.WalletPaymentInstructionKindEnum.sun) {
      holderName = _text(data['holder_name'], max: 255);
      portraitRevision = _integer(data['portrait_revision']);
    }
  }
  late final contract.WalletPaymentInstruction instruction;
  String? holderName;
  int? portraitRevision;
}

class VendorPayments {
  VendorPayments(this.repository);
  final VendorRepository repository;
  contract.WalletCounterCatalogue? catalogue;
  VendorPaymentReview? review;
  VendorReceipt? receipt;
  Uint8List? portrait;
  String _qr = '', _reader = '';
  int _viewEpoch = 0;
  int? _readerCounter;
  bool get paired =>
      _reader.isNotEmpty && _readerCounter == repository.counter?.id;

  bool matches(int generation, int school, int actor, int counter) =>
      !repository._disposed &&
      !repository._suspended &&
      repository.generation == generation &&
      repository.profile?.schoolId == school &&
      repository.profile?.userId == actor &&
      repository.counter?.id == counter &&
      repository.profile?.walletPaymentsAvailable == true;

  void clearViews() {
    _viewEpoch++;
    catalogue = null;
    review = null;
    receipt = null;
    portrait = null;
    _qr = '';
  }

  void eraseReader() {
    _reader = '';
    _readerCounter = null;
  }

  bool pair(String value) {
    final p = repository.profile, c = repository.counter;
    if (p == null ||
        c == null ||
        !p.walletPaymentsAvailable ||
        !RegExp('^NED1\\.${p.schoolId}\\.[a-f0-9-]{36}\\.[a-f0-9]{64}\$')
            .hasMatch(value.trim())) {
      return false;
    }
    _reader = value.trim();
    _readerCounter = c.id;
    repository._notifyPayments();
    return true;
  }

  void _checkView(int epoch) {
    if (epoch != _viewEpoch) {
      throw ApiException(
        'This payment view was closed. Reopen Wallet payments.',
        statusCode: 409,
        isApiMessage: true,
      );
    }
  }

  void _scope(Map<String, dynamic> data, VendorProfile p, VendorCounter c) {
    _paymentIntegers(data);
    if (data['school_id'] != p.schoolId ||
        data['actor_user_id'] != p.userId ||
        data['vendor_id'] != c.id) {
      throw const FormatException('Different current payment counter');
    }
  }

  Future<bool> load({int page = 1}) async {
    final repo = repository, p = repository.profile, c = repository.counter;
    if (p == null || c == null || !p.walletPaymentsAvailable) return false;
    final epoch = repo.generation;
    return repo._run(() async {
      final viewEpoch = _viewEpoch;
      final data = await repo._request(
        'GET',
        'counters/${c.id}/payments/catalogue?page=$page',
      );
      repo._check(epoch, p);
      _checkView(viewEpoch);
      _scope(data, p, c);
      final result = contract.WalletCounterCatalogue.fromJson(data);
      if (result.currency != c.currency ||
          result.page != page ||
          result.lastPage < 1 ||
          result.items.length > 30 ||
          result.items.map((i) => i.id).toSet().length != result.items.length) {
        throw const FormatException('Invalid counter catalogue');
      }
      for (final item in result.items) {
        _integer(item.id);
        _integer(item.revision);
        _integer(item.unitPriceCents, min: 0);
        _text(item.name, max: 191);
      }
      catalogue = result;
    });
  }

  Future<bool> lookup(String value) async {
    final repo = repository, p = repository.profile, c = repository.counter;
    if (p == null ||
        c == null ||
        !p.walletPaymentsAvailable ||
        !RegExp(r'^navigateed:wallet-pay:[a-f0-9]{64}$').hasMatch(value.trim())) {
      return false;
    }
    final epoch = repo.generation, token = value.trim().substring(22);
    return repo._run(() async {
      final viewEpoch = _viewEpoch;
      final data = await repo._request(
        'POST',
        'counters/${c.id}/payments/lookup',
        data: {'qr_token': token},
      );
      repo._check(epoch, p);
      _checkView(viewEpoch);
      final result = VendorPaymentReview(data, p, c);
      if (result.instruction.kind !=
          contract.WalletPaymentInstructionKindEnum.qr) {
        throw const FormatException('A card is not a wallet QR');
      }
      review = result;
      _qr = token;
    });
  }

  Future<bool> prepare(VendorSunTap tap, Map<int, int> quantities) async {
    final repo = repository, p = repository.profile, c = repository.counter;
    if (p == null ||
        c == null ||
        !p.walletPaymentsAvailable ||
        !paired ||
        quantities.isEmpty ||
        quantities.length > 20) {
      return false;
    }
    final epoch = repo.generation, credential = _reader;
    final lines = [
      for (final e in quantities.entries)
        {'product_id': e.key, 'quantity': e.value},
    ];
    return repo._run(() async {
      final viewEpoch = _viewEpoch;
      final operation = const Uuid().v4();
      final data = await repo._request(
        'POST',
        'counters/${c.id}/payments/card',
        operation: operation,
        paymentReader: credential,
        data: {
          'operation_id': operation,
          'card_id': tap.cardId,
          'picc': tap.picc,
          'mac': tap.mac,
          'lines': lines,
        },
      );
      repo._check(epoch, p);
      _checkView(viewEpoch);
      final result = VendorPaymentReview(data, p, c);
      if (result.instruction.kind !=
          contract.WalletPaymentInstructionKindEnum.sun) {
        throw const FormatException('Invalid card payment instruction');
      }
      final response =
          await (repo.createDio?.call() ??
                  createSessionDio(
                    mode: repo.mode,
                    apiBaseUrl: AppConfig.apiBaseUrl,
                  ))
              .get<List<int>>(
                '${AppConfig.apiV2Url}vendor/counters/${c.id}/payments/card/${result.instruction.id}/portrait',
                options: Options(
                  responseType: ResponseType.bytes,
                  receiveTimeout: const Duration(seconds: 30),
                  headers: {
                    'Accept': 'image/jpeg',
                    'X-Payment-Reader': credential,
                    'X-School-Id': '${p.schoolId}',
                    'X-Account-Id': '${p.userId}',
                    if (repo.mode == AuthMode.bearer)
                      'Authorization': 'Bearer ${repo._token}',
                  },
                ),
              );
      repo._check(epoch, p);
      _checkView(viewEpoch);
      final bytes = response.data;
      if (response.statusCode != 200 ||
          bytes == null ||
          bytes.length < 4 ||
          bytes.length > 262144 ||
          bytes[0] != 255 ||
          bytes[1] != 216) {
        throw const FormatException(
          'The current private portrait is unavailable',
        );
      }
      review = result;
      portrait = Uint8List.fromList(bytes);
    });
  }

  VendorReceipt _receipt(
    Map<String, dynamic> data,
    VendorProfile p,
    VendorCounter c,
    String operation,
  ) {
    _scope(data, p, c);
    if (!_paymentUuid(_text(data['id'])) ||
        !_paymentUuid(_text(data['intent_id'])) ||
        data['operation_id'] != operation ||
        data['status'] != 'paid') {
      throw const FormatException('Different original wallet payment');
    }
    final value = VendorReceipt(_map(data['order']), p, c);
    if (value.status != 'collected') {
      throw const FormatException('Payment has no completed handover');
    }
    return value;
  }

  Future<bool> pay({required bool photoConfirmed}) async {
    final repo = repository,
        p = repository.profile,
        c = repository.counter,
        original = review;
    if (p == null ||
        c == null ||
        original == null ||
        repo.hasPending ||
        !original.instruction.expiresAt.isAfter(DateTime.now().toUtc())) {
      return false;
    }
    final card =
        original.instruction.kind ==
        contract.WalletPaymentInstructionKindEnum.sun;
    if (card && (!paired || portrait == null || !photoConfirmed)) return false;
    final epoch = repo.generation, qr = _qr, credential = _reader;
    return repo._run(() async {
      final viewEpoch = _viewEpoch;
      final operation = const Uuid().v4();
      repo._pendingPayments[c.id] = operation;
      await repo._save(p);
      repo._check(epoch, p);
      _checkView(viewEpoch);
      final data = await repo._request(
        'POST',
        'counters/${c.id}/payments/pay',
        operation: operation,
        paymentReader: card ? credential : null,
        data: {
          'operation_id': operation,
          'intent_id': original.instruction.id,
          if (!card) 'qr_token': qr,
          if (card) 'portrait_confirmed': true,
          if (card) 'portrait_revision': original.portraitRevision,
          'confirmed': true,
        },
      );
      repo._check(epoch, p);
      _checkView(viewEpoch);
      final value = _receipt(data, p, c, operation);
      if (data['intent_id'] != original.instruction.id ||
          value.amountCents != original.instruction.amountCents) {
        throw const FormatException('Different original payment total');
      }
      if (value.lines.length != original.instruction.lines.length) {
        throw const FormatException('Different original payment goods');
      }
      for (final expected in original.instruction.lines) {
        final matches = value.lines.where(
          (line) =>
              line.sku == expected.sku && line.name == expected.productName,
        );
        if (matches.length != 1 ||
            matches.single.quantity != expected.quantity ||
            matches.single.unit != expected.unitLabel ||
            matches.single.grossCents != expected.grossCents ||
            matches.single.netCents != expected.netCents ||
            matches.single.vatCents != expected.vatCents) {
          throw const FormatException('Different original payment goods');
        }
      }
      final replayed = _boolean(data['replayed']);
      repo._pendingPayments.remove(c.id);
      try {
        await repo._save(p);
      } catch (_) {
        if (epoch == repo.generation) repo._pendingPayments[c.id] = operation;
        rethrow;
      }
      repo._check(epoch, p);
      _checkView(viewEpoch);
      receipt = value;
      repo.message = replayed
          ? 'Payment was already recorded. Check the original receipt; do not hand over goods again.'
          : original.instruction.lines.every((line) => line.ticket != null)
          ? 'Payment recorded. Digital admissions are available in the original wallet.'
          : 'Payment recorded. Hand over the listed goods once.';
    });
  }

  Future<bool> recover() async {
    final repo = repository, p = repository.profile, c = repository.counter;
    if (p == null || c == null) return false;
    final operation = repo._pendingPayments[c.id];
    if (operation == null) return false;
    final epoch = repo.generation;
    return repo._run(() async {
      final viewEpoch = _viewEpoch;
      final data = await repo._request(
        'POST',
        'counters/${c.id}/payments/operations/$operation/resolve',
      );
      repo._check(epoch, p);
      _checkView(viewEpoch);
      _scope(data, p, c);
      if (data['operation_id'] != operation ||
          !['committed', 'abandoned'].contains(data['status'])) {
        throw const FormatException(
          'Different original wallet payment outcome',
        );
      }
      VendorReceipt? original;
      if (data['status'] == 'committed') {
        original = _receipt(_map(data['payment']), p, c, operation);
      } else if (data['payment'] != null) {
        throw const FormatException('An abandoned request has no payment');
      }
      repo._pendingPayments.remove(c.id);
      try {
        await repo._save(p);
      } catch (_) {
        if (epoch == repo.generation) repo._pendingPayments[c.id] = operation;
        rethrow;
      }
      repo._check(epoch, p);
      _checkView(viewEpoch);
      receipt = original;
      repo.message = original == null
          ? 'No payment was recorded by that request. Review a current instruction.'
          : 'Payment was already recorded. Check the original receipt; do not hand over goods again.';
    });
  }
}
