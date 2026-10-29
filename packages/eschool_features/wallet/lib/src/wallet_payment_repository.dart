import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter/foundation.dart';

import 'wallet_repository.dart';

abstract interface class WalletPaymentRepository {
  int get schoolId;
  int get accountId;
  bool get isActive;
  Future<WalletPaymentPersonalPage> listing({int page = 1});
  Future<WalletPaymentPersonal> issue(CommerceQuote quote, String operation);
  Future<WalletPaymentPersonal> read(String id);
  Future<WalletPaymentPersonal> cancel(String id);
  Future<WalletOperationResolution> resolve(String operation);
}

class ApiWalletPaymentRepository implements WalletPaymentRepository {
  ApiWalletPaymentRepository(this.client, {required this.wallets});
  final ApiClient client;
  final WalletRepository wallets;
  final Set<String> _instructions = {};
  @override
  int get schoolId => wallets.schoolId;
  @override
  int get accountId => wallets.accountId;
  @override
  bool get isActive => wallets.isActive;
  bool _uuid(String value) => RegExp(
    r'^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$',
  ).hasMatch(value);
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
    if (value is List) {
      for (final row in value) {
        _integers(row);
      }
    } else if (value is Map) {
      for (final e in value.entries) {
        final key = e.key.toString();
        if (((key == 'id' && e.value is num) ||
                key.endsWith('_cents') ||
                key.endsWith('_bps') ||
                [
                  'school_id',
                  'actor_user_id',
                  'vendor_id',
                  'wallet_id',
                  'wallet_owner_id',
                  'order_id',
                  'product_id',
                  'product_revision',
                  'quantity',
                  'page',
                  'last_page',
                ].contains(key)) &&
            e.value != null &&
            e.value is! int) {
          _invalid();
        }
        _integers(e.value);
      }
    }
  }

  Future<Map<String, dynamic>> _send(
    String method,
    String suffix, {
    Map<String, dynamic>? data,
    Map<String, dynamic>? query,
    String? operation,
  }) async {
    _guard();
    final reply = await client.send(
      method: method,
      url: '${AppConfig.apiV2Url}commerce/wallet-payments$suffix',
      data: data,
      queryParameters: query,
      schoolId: schoolId,
      accountId: accountId,
      idempotencyKey: operation,
      privateRequest: true,
    );
    _guard();
    if (reply?['error'] != false ||
        reply?['code'] != 200 ||
        reply?['data'] is! Map) {
      _invalid();
    }
    final out = Map<String, dynamic>.from(reply!['data'] as Map);
    _integers(out);
    if (out['school_id'] != schoolId || out['actor_user_id'] != accountId) {
      _invalid();
    }
    return out;
  }

  void _validate(WalletPaymentPersonal row, PersonalWalletIndex owners) {
    final currency = owners.currency;
    if (!owners.owners.any(
          (o) =>
              o.ownerUserId == row.walletOwnerId &&
              o.ownerName == row.walletOwnerName &&
              o.wallets.any(
                (w) =>
                    w.id == row.walletId && w.ownerUserId == row.walletOwnerId,
              ),
        ) ||
        currency == null ||
        !_uuid(row.id) ||
        row.schoolId != schoolId ||
        row.actorUserId != accountId ||
        row.vendorId < 1 ||
        row.seller.id != row.vendorId ||
        row.currency != currency ||
        row.kind != WalletPaymentPersonalKindEnum.qr ||
        row.status == WalletPaymentPersonalStatusEnum.unknownDefaultOpenApi ||
        row.amountCents < 0 ||
        row.amountCents > 100000000000 ||
        row.netCents < 0 ||
        row.vatCents < 0 ||
        row.amountCents != row.netCents + row.vatCents ||
        row.lines.isEmpty ||
        row.lines.length > 20 ||
        row.lines.map((l) => l.productId).toSet().length != row.lines.length ||
        (row.status == WalletPaymentPersonalStatusEnum.paid) !=
            (row.orderId != null) ||
        (row.orderId != null && row.orderId! < 1) ||
        (row.status != WalletPaymentPersonalStatusEnum.awaitingCounter &&
            row.qr != null) ||
        (row.qr != null &&
            !RegExp(
              r'^navigateed:wallet-pay:[a-f0-9]{64}$',
            ).hasMatch(row.qr!))) {
      _invalid();
    }
    var gross = 0, net = 0, vat = 0;
    for (final line in row.lines) {
      final kind = line.vatKind.value;
      final rate = line.vatRateBps;
      if (line.productId < 1 ||
          line.productRevision < 1 ||
          line.quantity < 1 ||
          line.quantity > 999 ||
          line.unitPriceCents < 0 ||
          line.productName.trim().isEmpty ||
          line.grossCents != line.quantity * line.unitPriceCents ||
          line.grossCents != line.netCents + line.vatCents ||
          line.netCents < 0 ||
          line.vatCents < 0 ||
          rate < 0 ||
          rate > 10000 ||
          !['none', 'exempt', 'zero', 'standard'].contains(kind) ||
          (kind == 'standard' ? rate == 0 : rate != 0) ||
          (row.seller.vatRegistered ? kind == 'none' : kind != 'none') ||
          line.vatCents !=
              (kind == 'standard'
                  ? (line.grossCents * rate * 2 + 10000 + rate) ~/
                        ((10000 + rate) * 2)
                  : 0)) {
        _invalid();
      }
      gross += line.grossCents;
      net += line.netCents;
      vat += line.vatCents;
    }
    if (gross != row.amountCents ||
        net != row.netCents ||
        vat != row.vatCents) {
      _invalid();
    }
  }

  @override
  Future<WalletPaymentPersonalPage> listing({int page = 1}) async {
    final owners = await wallets.index();
    final out = WalletPaymentPersonalPage.fromJson(
      await _send('GET', '', query: {'page': page}),
    );
    if (out.page != page ||
        out.lastPage < 1 ||
        out.items.length > 20 ||
        out.items.map((i) => i.id).toSet().length != out.items.length) {
      _invalid();
    }
    _instructions.clear();
    for (final row in out.items) {
      _validate(row, owners);
      _instructions.add(row.id);
    }
    return out;
  }

  @override
  Future<WalletPaymentPersonal> issue(
    CommerceQuote quote,
    String operation,
  ) async {
    final owners = await wallets.index();
    if (!_uuid(operation) ||
        quote.schoolId != schoolId ||
        quote.actorUserId != accountId ||
        quote.currency != owners.currency ||
        !owners.owners.any(
          (o) =>
              o.canOpen &&
              o.wallets.any(
                (w) =>
                    w.id == quote.walletId &&
                    w.ownerUserId == quote.walletOwnerId &&
                    !w.readOnly,
              ),
        )) {
      _invalid();
    }
    final row = WalletPaymentPersonal.fromJson(
      await _send(
        'POST',
        '',
        operation: operation,
        data: {
          'operation_id': operation,
          'quote': quote.quote,
          'confirmed': true,
        },
      ),
    );
    _validate(row, owners);
    if (row.vendorId != quote.vendorId ||
        row.seller != quote.seller ||
        row.amountCents != quote.amountCents ||
        row.netCents != quote.netCents ||
        row.vatCents != quote.vatCents ||
        !listEquals(row.lines, quote.lines)) {
      _invalid();
    }
    _instructions.add(row.id);
    return row;
  }

  Future<WalletPaymentPersonal> _owned(
    String method,
    String id,
    String suffix,
  ) async {
    if (!_uuid(id) || !_instructions.contains(id)) _invalid();
    final owners = await wallets.index();
    final row = WalletPaymentPersonal.fromJson(
      await _send(method, '/$id$suffix'),
    );
    _validate(row, owners);
    if (row.id != id) _invalid();
    return row;
  }

  @override
  Future<WalletPaymentPersonal> read(String id) => _owned('GET', id, '');
  @override
  Future<WalletPaymentPersonal> cancel(String id) =>
      _owned('POST', id, '/cancel');
  @override
  Future<WalletOperationResolution> resolve(String operation) {
    _guard();
    if (wallets case WalletMoneyRepository money) {
      return money.resolve(operation);
    }
    throw StateError('Wallet recovery is unavailable.');
  }
}
