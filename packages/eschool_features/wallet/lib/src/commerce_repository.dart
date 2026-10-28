import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'wallet_repository.dart';

abstract interface class CommerceRepository {
  int get schoolId;
  int get accountId;
  bool get isActive;
  Future<PersonalWalletIndex> walletChoices();
  Future<CommerceVendorPage> vendors({int page = 1});
  Future<CommerceCatalogue> catalogue(int vendor, {int page = 1});
  Future<CommerceOrderPage> orders({int page = 1});
  Future<CommerceQuote> quote(int vendor, int wallet, Map<int, int> quantities);
  Future<CommerceOrderReceipt> purchase(CommerceQuote quote, String operation);
  Future<CommerceOrderReceipt> cancel(
    int order,
    String reason,
    String operation,
  );
  Future<WalletOperationResolution> resolve(String operation);
}

class ApiCommerceRepository implements CommerceRepository {
  ApiCommerceRepository(this.client, {required this.wallets});
  final ApiClient client;
  final WalletRepository wallets;
  final Map<int, int> _owners = {};
  final Set<int> _orders = {};
  String? _currency;
  @override
  int get schoolId => wallets.schoolId;
  @override
  int get accountId => wallets.accountId;
  @override
  bool get isActive => wallets.isActive;
  Never _invalid() => throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
  bool _uuid(String s) => RegExp(
    r'^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$',
  ).hasMatch(s);
  void _guard() {
    if (!isActive) {
      throw ApiException(
        'Your storefront access changed. Reopen Wallets.',
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
                [
                  'quantity',
                  'product_revision',
                  'vat_rate_bps',
                  'page',
                  'last_page',
                ].contains(key)) &&
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
      url: '${AppConfig.apiV2Url}commerce$path',
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

  void _page(int requested, int current, int last, int length) {
    if (requested < 1 ||
        requested > 1000000 ||
        current != requested ||
        last < 1 ||
        length > 30) {
      _invalid();
    }
  }

  void _money(int cents) {
    if (cents < 0 || cents > 100000000000) _invalid();
  }

  void _tax(String kind, int rate, int gross, int net, int tax) {
    _money(gross);
    _money(net);
    _money(tax);
    if (gross != net + tax ||
        !['none', 'zero', 'exempt', 'standard'].contains(kind) ||
        rate < 0 ||
        rate > 10000 ||
        (kind == 'standard' ? rate == 0 : rate != 0)) {
      _invalid();
    }
    final expected = kind == 'standard'
        ? (gross * rate * 2 + 10000 + rate) ~/ ((10000 + rate) * 2)
        : 0;
    if (tax != expected) _invalid();
  }

  @override
  Future<PersonalWalletIndex> walletChoices() async {
    _guard();
    _owners.clear();
    final out = await wallets.index();
    _guard();
    _currency = out.currency;
    for (final owner in out.owners) {
      for (final wallet in owner.wallets) {
        _owners[wallet.id] = owner.ownerUserId;
      }
    }
    return out;
  }

  @override
  Future<CommerceVendorPage> vendors({int page = 1}) async {
    final out = CommerceVendorPage.fromJson(
      await _send('GET', '/vendors', query: {'page': page}),
    );
    _page(page, out.page, out.lastPage, out.items.length);
    if (out.items.map((v) => v.id).toSet().length != out.items.length) {
      _invalid();
    }
    for (final vendor in out.items) {
      if (vendor.id < 1 ||
          vendor.name.trim().isEmpty ||
          vendor.currency != _currency ||
          !['school', 'independent'].contains(vendor.kind.value)) {
        _invalid();
      }
    }
    return out;
  }

  @override
  Future<CommerceCatalogue> catalogue(int vendor, {int page = 1}) async {
    if (vendor < 1) throw ArgumentError('Choose a current vendor.');
    final out = CommerceCatalogue.fromJson(
      await _send('GET', '/vendors/$vendor', query: {'page': page}),
    );
    _page(page, out.page, out.lastPage, out.items.length);
    if (out.vendor.id != vendor ||
        !['school', 'independent'].contains(out.vendor.kind.value) ||
        out.items.map((p) => p.id).toSet().length != out.items.length) {
      _invalid();
    }
    for (final p in out.items) {
      if (p.id < 1 ||
          p.name.trim().isEmpty ||
          p.currency != _currency ||
          !RegExp(r'^[a-z][a-z0-9_-]{0,39}$').hasMatch(p.categoryKey)) {
        _invalid();
      }
      _money(p.priceCents);
      if (!['none', 'zero', 'exempt', 'standard'].contains(p.vatKind.value) ||
          p.vatRateBps < 0 ||
          p.vatRateBps > 10000 ||
          (p.vatKind.value == 'standard'
              ? p.vatRateBps == 0
              : p.vatRateBps != 0)) {
        _invalid();
      }
    }
    return out;
  }

  void _receipt(CommerceOrderReceipt out) {
    _seller(out.seller);
    if (out.id < 1 ||
        out.schoolId != schoolId ||
        out.actorUserId != accountId ||
        !_uuid(out.operationId) ||
        !RegExp(r'^VO-[0-9]{6,20}$').hasMatch(out.orderNumber) ||
        _owners[out.walletId] != out.walletOwnerId ||
        out.currency != _currency ||
        out.vendorId < 1 ||
        out.seller.id != out.vendorId ||
        !['school', 'independent'].contains(out.seller.kind.value) ||
        out.status == CommerceOrderReceiptStatusEnum.unknownDefaultOpenApi ||
        out.lines.isEmpty ||
        out.lines.length > 20 ||
        out.lines.map((l) => l.productId).toSet().length != out.lines.length ||
        (out.status == CommerceOrderReceiptStatusEnum.ready) !=
            (out.processedAt == null) ||
        (out.status != CommerceOrderReceiptStatusEnum.ready &&
            (out.canCancel || out.qr != null)) ||
        (out.status == CommerceOrderReceiptStatusEnum.ready &&
            !RegExp(
              r'^navigateed:order:[a-f0-9]{64}$',
            ).hasMatch(out.qr ?? ''))) {
      _invalid();
    }
    var gross = 0, net = 0, tax = 0;
    for (final line in out.lines) {
      if (out.seller.vatRegistered
          ? line.vatKind.value == 'none'
          : line.vatKind.value != 'none') {
        _invalid();
      }
      if (line.productId < 1 ||
          line.quantity < 1 ||
          line.quantity > 999 ||
          line.grossCents != line.unitPriceCents * line.quantity) {
        _invalid();
      }
      _money(line.unitPriceCents);
      _tax(
        line.vatKind.value,
        line.vatRateBps,
        line.grossCents,
        line.netCents,
        line.vatCents,
      );
      gross += line.grossCents;
      net += line.netCents;
      tax += line.vatCents;
    }
    _money(out.amountCents);
    if (gross != out.amountCents ||
        net != out.netCents ||
        tax != out.vatCents) {
      _invalid();
    }
  }

  @override
  Future<CommerceOrderPage> orders({int page = 1}) async {
    final out = CommerceOrderPage.fromJson(
      await _send('GET', '/orders', query: {'page': page}),
    );
    _page(page, out.page, out.lastPage, out.items.length);
    if (out.readOnly && out.items.any((o) => o.canCancel)) _invalid();
    if (out.items.map((o) => o.id).toSet().length != out.items.length) {
      _invalid();
    }
    _orders.clear();
    for (final order in out.items) {
      _receipt(order);
      _orders.add(order.id);
    }
    return out;
  }

  @override
  Future<CommerceQuote> quote(
    int vendor,
    int wallet,
    Map<int, int> quantities,
  ) async {
    if (vendor < 1 ||
        !_owners.containsKey(wallet) ||
        quantities.isEmpty ||
        quantities.length > 20 ||
        quantities.entries.any(
          (e) => e.key < 1 || e.value < 1 || e.value > 999,
        )) {
      throw ArgumentError(
        'Choose a current wallet and valid product quantities.',
      );
    }
    final out = CommerceQuote.fromJson(
      await _send(
        'POST',
        '/quotes',
        data: {
          'vendor_id': vendor,
          'wallet_id': wallet,
          'lines': [
            for (final line in quantities.entries)
              {'product_id': line.key, 'quantity': line.value},
          ],
        },
      ),
    );
    _seller(out.seller);
    if (out.walletId != wallet ||
        out.walletOwnerId != _owners[wallet] ||
        out.vendorId != vendor ||
        out.currency != _currency ||
        out.seller.id != vendor ||
        out.quote.isEmpty ||
        out.quote.length > 32768 ||
        out.lines.length != quantities.length ||
        out.lines.map((l) => l.productId).toSet().length != out.lines.length) {
      _invalid();
    }
    var gross = 0, net = 0, tax = 0;
    for (final line in out.lines) {
      if (out.seller.vatRegistered
          ? line.vatKind.value == 'none'
          : line.vatKind.value != 'none') {
        _invalid();
      }
      if (quantities[line.productId] != line.quantity ||
          line.productRevision < 1 ||
          line.grossCents != line.unitPriceCents * line.quantity) {
        _invalid();
      }
      _money(line.unitPriceCents);
      _tax(
        line.vatKind.value,
        line.vatRateBps,
        line.grossCents,
        line.netCents,
        line.vatCents,
      );
      gross += line.grossCents;
      net += line.netCents;
      tax += line.vatCents;
    }
    _money(out.amountCents);
    if (gross != out.amountCents ||
        net != out.netCents ||
        tax != out.vatCents) {
      _invalid();
    }
    return out;
  }

  @override
  Future<CommerceOrderReceipt> purchase(
    CommerceQuote quote,
    String operation,
  ) async {
    if (!_uuid(operation) ||
        quote.schoolId != schoolId ||
        quote.actorUserId != accountId ||
        _owners[quote.walletId] != quote.walletOwnerId) {
      throw ArgumentError('Review the current checkout.');
    }
    final out = CommerceOrderReceipt.fromJson(
      await _send(
        'POST',
        '/orders',
        operation: operation,
        data: {
          'operation_id': operation,
          'quote': quote.quote,
          'confirmed': true,
        },
      ),
    );
    _receipt(out);
    if (out.operationId != operation ||
        out.seller != quote.seller ||
        out.vendorId != quote.vendorId ||
        out.walletId != quote.walletId ||
        out.amountCents != quote.amountCents ||
        out.netCents != quote.netCents ||
        out.vatCents != quote.vatCents ||
        out.lines.length != quote.lines.length) {
      _invalid();
    }
    for (final expected in quote.lines) {
      final matches = out.lines.where((l) => l.productId == expected.productId);
      if (matches.length != 1 ||
          matches.single.quantity != expected.quantity ||
          matches.single.unitPriceCents != expected.unitPriceCents ||
          matches.single.name != expected.productName ||
          matches.single.sku != expected.sku ||
          matches.single.categoryKey != expected.categoryKey ||
          matches.single.vatKind.value != expected.vatKind.value ||
          matches.single.vatRateBps != expected.vatRateBps ||
          matches.single.vatCents != expected.vatCents) {
        _invalid();
      }
    }
    _orders.add(out.id);
    return out;
  }

  @override
  Future<CommerceOrderReceipt> cancel(
    int order,
    String reason,
    String operation,
  ) async {
    if (!_orders.contains(order) ||
        !_uuid(operation) ||
        reason.trim().length < 3 ||
        reason.length > 2000) {
      throw ArgumentError('Choose a current order and a cancellation reason.');
    }
    final out = CommerceOrderReceipt.fromJson(
      await _send(
        'POST',
        '/orders/$order/cancel',
        operation: operation,
        data: {
          'operation_id': operation,
          'private_reason': reason.trim(),
          'confirmed': true,
        },
      ),
    );
    _receipt(out);
    if (out.id != order ||
        out.status != CommerceOrderReceiptStatusEnum.cancelled) {
      _invalid();
    }
    return out;
  }

  @override
  Future<WalletOperationResolution> resolve(String operation) {
    _guard();
    if (wallets is! WalletMoneyRepository) {
      throw StateError('Wallet recovery is unavailable.');
    }
    return (wallets as WalletMoneyRepository).resolve(operation);
  }

  void _seller(CommerceSeller seller) {
    if (seller.id < 1 ||
        seller.name.trim().isEmpty ||
        seller.legalName.trim().isEmpty ||
        !['school', 'independent'].contains(seller.kind.value) ||
        (seller.vatRegistered &&
            ((seller.vatNumber ?? '').trim().isEmpty ||
                (seller.legalAddress ?? '').trim().isEmpty))) {
      _invalid();
    }
  }
}
