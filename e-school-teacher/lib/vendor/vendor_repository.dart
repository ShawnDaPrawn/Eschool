import 'dart:async';

import 'package:dio/dio.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/foundation.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:uuid/uuid.dart';

int _integer(Object? value, {int min = 1, int max = 100000000000}) {
  if (value is! int || value < min || value > max) {
    throw const FormatException('Invalid vendor integer');
  }
  return value;
}

String _text(Object? value, {int max = 2000}) {
  if (value is! String || value.isEmpty || value.length > max) {
    throw const FormatException('Invalid vendor text');
  }
  return value;
}

bool _boolean(Object? value) {
  if (value is! bool) throw const FormatException('Invalid vendor flag');
  return value;
}

Map<String, dynamic> _map(Object? value) {
  if (value is! Map) throw const FormatException('Invalid vendor response');
  return Map<String, dynamic>.from(value);
}

List<dynamic> _list(Object? value, {int max = 100}) {
  if (value is! List || value.length > max) {
    throw const FormatException('Invalid vendor list');
  }
  return value;
}

String _currency(Object? value) {
  final text = _text(value, max: 3);
  if (!RegExp(r'^[A-Z]{3}$').hasMatch(text)) {
    throw const FormatException('Invalid currency');
  }
  return text;
}

DateTime _instant(Object? value) {
  final text = _text(value, max: 30);
  if (!RegExp(r'^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d\.\d{3}Z$').hasMatch(text)) {
    throw const FormatException('Invalid UTC time');
  }
  final time = DateTime.parse(text);
  if (!time.isUtc || time.toIso8601String() != text) {
    throw const FormatException('Invalid UTC time');
  }
  return time;
}

class VendorCounter {
  VendorCounter(Map<String, dynamic> data)
    : id = _integer(data['id']),
      name = _text(data['name'], max: 160),
      currency = _currency(data['currency']),
      canCollect = _boolean(data['can_collect']);
  final int id;
  final String name, currency;
  final bool canCollect;
}

class VendorProfile {
  bool get ticketingAvailable => _ticketingAvailable;
  final bool _ticketingAvailable;
  VendorProfile(Map<String, dynamic> data)
    : _ticketingAvailable = _boolean(data['ticketing_available'] ?? false),
      schoolId = _integer(data['school_id']),
      userId = _integer(data['user_id']),
      schoolName = _text(data['school_name']),
      schoolSlug = _text(data['school_slug'], max: 191),
      name = _text(data['name']),
      counters = [
        for (final row in _list(data['counters'])) VendorCounter(_map(row)),
      ] {
    if (counters.isEmpty ||
        counters.map((c) => c.id).toSet().length != counters.length) {
      throw const FormatException('Invalid assigned counters');
    }
  }
  final int schoolId, userId;
  final String schoolName, schoolSlug, name;
  final List<VendorCounter> counters;
}

class VendorPolicyDocument {
  VendorPolicyDocument(Map<String, dynamic> data)
    : document = _text(data['document'], max: 20),
      version = _text(data['version'], max: 64),
      title = _text(data['title']),
      text = _text(data['text'], max: 200000) {
    if (!['navigateed', 'school'].contains(document) ||
        !RegExp(r'^[a-f0-9]{64}$').hasMatch(version)) {
      throw const FormatException('Invalid policy version');
    }
  }
  final String document, version, title, text;
}

class VendorSetup {
  VendorSetup(Map<String, dynamic> data)
    : passwordRequired = _boolean(data['password_change_required']),
      documents = [
        for (final row in _list(data['documents'], max: 2))
          VendorPolicyDocument(_map(row)),
      ] {
    if (data['authenticated'] != false ||
        data['token'] != null ||
        data['profile'] != null) {
      throw const FormatException('Unexpected vendor setup credential');
    }
  }
  final bool passwordRequired;
  final List<VendorPolicyDocument> documents;
}

class VendorReceiptLine {
  VendorReceiptLine(Map<String, dynamic> data)
    : name = _text(data['product_name'], max: 160),
      sku = _text(data['sku'], max: 64),
      unit = _text(data['unit_label'], max: 40),
      quantity = _integer(data['quantity'], max: 999),
      grossCents = _integer(data['gross_cents'], min: 0),
      netCents = _integer(data['net_cents'], min: 0),
      vatCents = _integer(data['vat_cents'], min: 0) {
    if (grossCents != netCents + vatCents) {
      throw const FormatException('Invalid original line total');
    }
  }
  final String name, sku, unit;
  final int quantity, grossCents, netCents, vatCents;
}

class VendorReceipt {
  VendorReceipt(
    Map<String, dynamic> data,
    VendorProfile profile,
    VendorCounter counter,
  ) : id = _integer(data['id']),
      number = _text(data['order_number'], max: 32),
      status = _text(data['status'], max: 12),
      currency = _currency(data['currency']),
      amountCents = _integer(data['amount_cents'], min: 0),
      netCents = _integer(data['net_cents'], min: 0),
      vatCents = _integer(data['vat_cents'], min: 0),
      createdAt = _instant(data['created_at']),
      processedAt = data['processed_at'] == null
          ? null
          : _instant(data['processed_at']),
      canCollect = _boolean(data['can_collect']),
      newlyCollected = data['newly_collected'] == null
          ? false
          : _boolean(data['newly_collected']),
      lines = [
        for (final row in _list(data['lines'], max: 20))
          VendorReceiptLine(_map(row)),
      ] {
    if (_integer(data['school_id']) != profile.schoolId ||
        _integer(data['actor_user_id']) != profile.userId ||
        _integer(data['vendor_id']) != counter.id ||
        currency != counter.currency ||
        !RegExp(r'^VO-\d{6,20}$').hasMatch(number) ||
        !['ready', 'collected', 'cancelled'].contains(status) ||
        amountCents != netCents + vatCents ||
        lines.isEmpty ||
        lines.fold(0, (n, l) => n + l.grossCents) != amountCents ||
        lines.fold(0, (n, l) => n + l.netCents) != netCents ||
        lines.fold(0, (n, l) => n + l.vatCents) != vatCents ||
        (status == 'ready') != (processedAt == null) ||
        (canCollect && (!counter.canCollect || status != 'ready')) ||
        (newlyCollected && status != 'collected')) {
      throw const FormatException('Vendor receipt context changed');
    }
  }
  final int id, amountCents, netCents, vatCents;
  final String number, status, currency;
  final DateTime createdAt;
  final DateTime? processedAt;
  final bool canCollect, newlyCollected;
  final List<VendorReceiptLine> lines;
}

String _civilDate(Object? value) {
  final text = _text(value, max: 10);
  final parsed = DateTime.tryParse(text);
  if (!RegExp(r'^\d{4}-\d\d-\d\d$').hasMatch(text) ||
      parsed == null ||
      parsed.toIso8601String().substring(0, 10) != text) {
    throw const FormatException('Invalid reporting date');
  }
  return text;
}

class VendorSettlementLine {
  VendorSettlementLine(Map<String, dynamic> data)
    : number = _text(data['order_number'], max: 32),
      grossCents = _integer(data['gross_cents'], min: 0),
      salesNetCents = _integer(data['sales_net_cents'], min: 0),
      vatCents = _integer(data['vat_cents'], min: 0) {
    if (!RegExp(r'^VO-[0-9]{6,20}$').hasMatch(number) ||
        grossCents != salesNetCents + vatCents) {
      throw const FormatException('Invalid original settlement line');
    }
  }
  final String number;
  final int grossCents, salesNetCents, vatCents;
}

class VendorSettlement {
  VendorSettlement(
    Map<String, dynamic> data,
    VendorCounter counter, {
    bool detail = false,
  }) : id = _integer(data['id']),
       method = _text(data['method'], max: 20),
       currency = _currency(data['currency']),
       periodFrom = _civilDate(data['period_from']),
       periodTo = _civilDate(data['period_to']),
       orderCount = _integer(data['order_count'], max: 500),
       grossCents = _integer(data['gross_cents'], min: 0),
       salesNetCents = _integer(data['sales_net_cents'], min: 0),
       vatCents = _integer(data['vat_cents'], min: 0),
       collectorFeeCents = _integer(data['collector_fee_cents'], min: 0),
       payoutCents = _integer(data['payout_cents'], min: 0),
       status = _text(data['status'], max: 12),
       createdAt = _instant(data['created_at']),
       settledAt = data['settled_at'] == null
           ? null
           : _instant(data['settled_at']),
       paidOn = data['paid_on'] == null ? null : _civilDate(data['paid_on']),
       lines = detail
           ? [
               for (final line in _list(data['lines'], max: 500))
                 VendorSettlementLine(_map(line)),
             ]
           : [] {
    if (currency != counter.currency ||
        !['school_internal', 'cash', 'eft'].contains(method) ||
        !['prepared', 'approved', 'settled'].contains(status) ||
        periodFrom.compareTo(periodTo) > 0 ||
        grossCents != salesNetCents + vatCents ||
        data['collected_by'] != 'school' ||
        collectorFeeCents != 0 ||
        payoutCents != grossCents ||
        (status == 'settled') != (settledAt != null && paidOn != null) ||
        (status != 'settled' && (settledAt != null || paidOn != null))) {
      throw const FormatException('Invalid settlement totals or state');
    }
    if (detail &&
        (lines.length != orderCount ||
            lines.map((l) => l.number).toSet().length != lines.length ||
            lines.fold<int>(0, (total, l) => total + l.grossCents) !=
                grossCents ||
            lines.fold<int>(0, (total, l) => total + l.salesNetCents) !=
                salesNetCents ||
            lines.fold<int>(0, (total, l) => total + l.vatCents) != vatCents)) {
      throw const FormatException('Invalid original settlement orders');
    }
  }
  final int id,
      orderCount,
      grossCents,
      salesNetCents,
      vatCents,
      collectorFeeCents,
      payoutCents;
  final String method, currency, periodFrom, periodTo, status;
  final String? paidOn;
  final DateTime createdAt;
  final DateTime? settledAt;
  final List<VendorSettlementLine> lines;
}

class VendorAdmission {
  VendorAdmission(
    Map<String, dynamic> data,
    VendorProfile profile,
    VendorCounter counter,
  ) : id = _integer(data['id']),
      revision = _integer(data['revision'], min: 0),
      eventName = _text(data['event_name'], max: 191),
      productName = _text(data['product_name'], max: 160),
      kind = _text(data['kind'], max: 8),
      status = _text(data['status'], max: 12),
      canRedeem = _boolean(data['can_redeem']),
      namedLearner = data['named_learner_name'] == null
          ? null
          : _text(data['named_learner_name'], max: 400),
      startsAt = _instant(data['starts_at']),
      endsAt = _instant(data['ends_at']),
      redeemedAt = data['redeemed_at'] == null
          ? null
          : _instant(data['redeemed_at']),
      redeemedBy = data['redeemed_by'] == null
          ? null
          : _integer(data['redeemed_by']) {
    if (_integer(data['school_id']) != profile.schoolId ||
        _integer(data['actor_user_id']) != profile.userId ||
        _integer(data['vendor_id']) != counter.id ||
        !['general', 'named'].contains(kind) ||
        !['valid', 'redeemed'].contains(status) ||
        !endsAt.isAfter(startsAt) ||
        (kind == 'named') != (namedLearner != null) ||
        (status == 'redeemed') != (redeemedAt != null && redeemedBy != null) ||
        (status == 'redeemed' && canRedeem) ||
        [
          'holder_user_id',
          'wallet_owner_id',
          'wallet_id',
          'private_qr_token',
          'qr',
        ].any(data.containsKey)) {
      throw const FormatException('Invalid scoped original admission');
    }
  }
  final int id, revision;
  final String eventName, productName, kind, status;
  final String? namedLearner;
  final bool canRedeem;
  final DateTime startsAt, endsAt;
  final DateTime? redeemedAt;
  final int? redeemedBy;
}

/// Private vendor session: no teacher repository, token, Hive box or notification initialization.
class VendorRepository extends ChangeNotifier {
  VendorRepository({AuthMode? mode, this.createDio})
    : mode = mode ?? AuthMode.current;
  final AuthMode mode;
  final Dio Function()? createDio;
  String _token = '', _preferredSchool = '';
  int _generation = 0;
  bool _disposed = false;
  bool _suspended = false;
  VendorProfile? profile;
  VendorSetup? setup;
  VendorCounter? counter;
  VendorReceipt? receipt;
  VendorAdmission? admission;
  String _admissionCredential = '';
  final Map<int, String> _pendingAdmissions = {};
  bool get hasPendingAdmission =>
      counter != null && _pendingAdmissions.containsKey(counter!.id);
  List<VendorReceipt> orders = [];
  List<VendorSettlement> settlements = [];
  VendorSettlement? settlement;
  bool settlementView = false;
  int settlementPage = 1, lastSettlementPage = 1;
  int page = 1, lastPage = 1;
  String filter = 'ready';
  final Map<int, String> _pending = {};
  bool busy = false;
  String? error, message;
  bool get signedIn => profile != null;
  bool get hasPending =>
      counter != null &&
      (_pending.containsKey(counter!.id) ||
          _pendingAdmissions.containsKey(counter!.id));
  int get generation => _generation;
  Completer<void>? _completion;
  Future<void> get idle => _completion?.future ?? Future<void>.value();
  Future<Map<String, dynamic>> _request(
    String method,
    String path, {
    Map<String, dynamic>? data,
    String? operation,
    bool anonymous = false,
  }) async {
    final cancellation = CancelToken();
    final timer = Timer(
      const Duration(seconds: 30),
      () => cancellation.cancel('Deadline elapsed'),
    );
    try {
      final response =
          await (createDio?.call() ??
                  createSessionDio(
                    mode: mode,
                    apiBaseUrl: AppConfig.apiBaseUrl,
                  ))
              .request<Object?>(
                '${AppConfig.apiV2Url}vendor/$path',
                data: data,
                cancelToken: cancellation,
                options: Options(
                  method: method,
                  contentType: Headers.jsonContentType,
                  validateStatus: (code) => code != null && code < 500,
                  headers: {
                    'Accept': 'application/json',
                    if (!anonymous &&
                        mode == AuthMode.bearer &&
                        _token.isNotEmpty)
                      'Authorization': 'Bearer $_token',
                    if (!anonymous && profile != null)
                      'X-School-Id': '${profile!.schoolId}',
                    if (!anonymous && profile != null)
                      'X-Account-Id': '${profile!.userId}',
                    'Idempotency-Key': ?operation,
                  },
                ),
              );
      final body = _map(response.data);
      if (response.statusCode == 409 &&
          body['code'] == 'VENDOR_SETUP_REQUIRED') {
        if (path == 'sign-out' && body['data'] == null) {
          return <String, dynamic>{};
        }
        return _map(body['data']);
      }
      if (response.statusCode != 200 || body['error'] != false) {
        throw ApiException(
          body['message'] is String
              ? body['message'] as String
              : 'Vendor request failed.',
          isApiMessage: true,
          statusCode: response.statusCode,
        );
      }
      if (path == 'sign-out' && body['data'] == null) {
        return <String, dynamic>{};
      }
      return _map(body['data']);
    } on DioException catch (error) {
      throw ApiException(
        'Connect to the school and check the handover outcome.',
        isApiMessage: true,
        statusCode: error.response?.statusCode,
        isTransportFailure: error.response == null,
      );
    } finally {
      timer.cancel();
    }
  }

  void _clearViews() {
    admission = null;
    _admissionCredential = '';
    settlements = [];
    settlement = null;
    settlementView = false;
    orders = [];
    receipt = null;
    message = null;
  }

  void clear() {
    if (_disposed) return;
    _generation++;
    _suspended = false;
    _token = '';
    profile = null;
    setup = null;
    counter = null;
    _pending.clear();
    _pendingAdmissions.clear();
    _clearViews();
    page = 1;
    lastPage = 1;
    settlementPage = 1;
    lastSettlementPage = 1;
    filter = 'ready';
    busy = false;
    error = null;
    notifyListeners();
  }

  void _check(int generation, VendorProfile pinned) {
    if (_disposed ||
        generation != _generation ||
        profile?.schoolId != pinned.schoolId ||
        profile?.userId != pinned.userId) {
      throw ApiException(
        'Your vendor session changed. Sign in again.',
        statusCode: 403,
      );
    }
  }

  Future<bool> _run(Future<void> Function() work) async {
    if (_disposed || busy || _suspended) return false;
    final epoch = _generation;
    final completion = Completer<void>();
    _completion = completion;
    busy = true;
    error = null;
    _clearViews();
    notifyListeners();
    try {
      await work();
      if (_disposed) return false;
      return true;
    } catch (error) {
      if (_disposed || epoch != _generation) return false;
      _clearViews();
      if (error is ApiException && [401, 403, 404].contains(error.statusCode)) {
        clear();
        this.error = 'Vendor access changed. Sign in again.';
      } else {
        this.error = error is ApiException && error.isApiMessage
            ? error.errorMessage
            : 'Vendor request could not be checked. Refresh or check its outcome.';
      }
      return false;
    } finally {
      if (!completion.isCompleted) completion.complete();
      if (identical(_completion, completion)) _completion = null;
      if (!_disposed && epoch == _generation) {
        busy = false;
        notifyListeners();
      }
    }
  }

  String _pendingKey(VendorProfile p) =>
      'vendor-collection-pending:${p.schoolId}:${p.userId}';
  Future<void> _save(VendorProfile p) async {
    final snapshot = [for (final e in _pending.entries) '${e.key}:${e.value}'];
    final prefs = await SharedPreferences.getInstance();
    final first = await prefs.setStringList(_pendingKey(p), snapshot);
    final second = await prefs.setStringList(
      'vendor-admission-pending:${p.schoolId}:${p.userId}',
      [for (final e in _pendingAdmissions.entries) '${e.key}:${e.value}'],
    );
    if (!first || !second) {
      throw StateError('Original request identity could not be retained');
    }
  }

  Future<void> _readPending(VendorProfile p, int generation) async {
    final parsed = <int, String>{};
    final prefs = await SharedPreferences.getInstance();
    for (final value in prefs.getStringList(_pendingKey(p)) ?? <String>[]) {
      final match = RegExp(
        r'^(\d+):([a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12})$',
      ).firstMatch(value);
      if (match == null) {
        throw const FormatException('Invalid saved vendor outcome');
      }
      parsed[int.parse(match[1]!)] = match[2]!;
    }
    _check(generation, p);
    _pending
      ..clear()
      ..addAll(parsed);
    final admissions = <int, String>{};
    for (final value
        in prefs.getStringList(
              'vendor-admission-pending:${p.schoolId}:${p.userId}',
            ) ??
            <String>[]) {
      final match = RegExp(
        r'^(\d+):([a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12})$',
      ).firstMatch(value);
      if (match == null) {
        throw const FormatException('Invalid retained admission identity');
      }
      admissions[int.parse(match[1]!)] = match[2]!;
    }
    _check(generation, p);
    _pendingAdmissions
      ..clear()
      ..addAll(admissions);
  }

  Future<bool> signIn(Map<String, dynamic> input) async {
    clear();
    final generation = _generation;
    _preferredSchool = _text(input['school'], max: 191);
    return _run(() async {
      final data = await _request(
        'POST',
        'sign-in',
        data: input,
        anonymous: true,
      );
      if (generation != _generation) {
        throw ApiException('Vendor session changed.', statusCode: 403);
      }
      if (data['authenticated'] == false) {
        setup = VendorSetup(data);
        return;
      }
      if (data['authenticated'] != true ||
          data['password_change_required'] != false) {
        throw const FormatException('Invalid vendor sign-in');
      }
      final value = VendorProfile(_map(data['profile']));
      if (value.schoolSlug != _preferredSchool ||
          (mode == AuthMode.bearer &&
              (data['token'] is! String ||
                  (data['token'] as String).isEmpty))) {
        throw ApiException('Vendor school changed.', statusCode: 403);
      }
      profile = value;
      _token = data['token'] is String ? data['token'] as String : '';
      counter = value.counters.first;
      setup = null;
      await _readPending(value, generation);
      _check(generation, value);
      await _load(generation, value, counter!);
    });
  }

  Future<void> _load(
    int generation,
    VendorProfile pinned,
    VendorCounter selected,
  ) async {
    final data = await _request(
      'GET',
      'counters/${selected.id}/orders?page=$page&status=$filter',
    );
    _check(generation, pinned);
    if (_integer(data['school_id']) != pinned.schoolId ||
        _integer(data['actor_user_id']) != pinned.userId ||
        _integer(data['vendor_id']) != selected.id ||
        data['status'] != filter) {
      throw ApiException('Vendor counter changed.', statusCode: 403);
    }
    page = _integer(data['page'], max: 1000000);
    lastPage = _integer(data['last_page'], max: 1000000);
    if (page > lastPage) throw const FormatException('Invalid order page');
    orders = [
      for (final row in _list(data['items'], max: 20))
        VendorReceipt(_map(row), pinned, selected),
    ];
  }

  Future<bool> refresh({
    int? vendor,
    int nextPage = 1,
    String status = 'ready',
  }) async {
    final pinned = profile;
    if (pinned == null) return false;
    final generation = _generation;
    return _run(() async {
      final data = await _request('GET', 'session');
      _check(generation, pinned);
      final current = VendorProfile(data);
      if (current.schoolId != pinned.schoolId ||
          current.userId != pinned.userId ||
          current.schoolSlug != pinned.schoolSlug) {
        throw ApiException('Vendor identity changed.', statusCode: 403);
      }
      final selected =
          current.counters
              .where((c) => c.id == (vendor ?? counter?.id))
              .firstOrNull ??
          current.counters.first;
      profile = current;
      counter = selected;
      page = nextPage;
      filter = status;
      await _load(generation, current, selected);
    });
  }

  Future<bool> lookup(String value) async {
    final pinned = profile, selected = counter;
    if (pinned == null || selected == null) return false;
    final generation = _generation;
    return _run(() async {
      final raw = value.trim();
      final qr = raw.startsWith('navigateed:order:');
      final data = await _request(
        'POST',
        'counters/${selected.id}/lookup',
        data: {qr ? 'qr_token' : 'order_number': raw},
      );
      _check(generation, pinned);
      receipt = VendorReceipt(data, pinned, selected);
    });
  }

  Future<bool> openOrder(int id) async {
    final pinned = profile, selected = counter;
    if (pinned == null || selected == null) return false;
    final generation = _generation;
    return _run(() async {
      final data = await _request('GET', 'counters/${selected.id}/orders/$id');
      _check(generation, pinned);
      final value = VendorReceipt(data, pinned, selected);
      if (value.id != id) {
        throw const FormatException('Different original receipt');
      }
      receipt = value;
    });
  }

  Future<bool> collect() async {
    final pinned = profile, selected = counter, original = receipt;
    if (pinned == null ||
        selected == null ||
        original?.canCollect != true ||
        !selected.canCollect ||
        hasPending) {
      return false;
    }
    final generation = _generation;
    return _run(() async {
      final key = const Uuid().v4();
      _pending[selected.id] = key;
      await _save(pinned);
      _check(generation, pinned);
      final data = await _request(
        'POST',
        'counters/${selected.id}/collect',
        operation: key,
        data: {
          'operation_id': key,
          'order_number': original!.number,
          'confirmed': true,
        },
      );
      _check(generation, pinned);
      final value = VendorReceipt(data, pinned, selected);
      if (value.id != original.id ||
          value.number != original.number ||
          value.status != 'collected' ||
          value.amountCents != original.amountCents) {
        throw const FormatException('Different collection outcome');
      }
      _pending.remove(selected.id);
      try {
        await _save(pinned);
      } catch (_) {
        if (generation == _generation) _pending[selected.id] = key;
        rethrow;
      }
      _check(generation, pinned);
      receipt = value;
      message = value.newlyCollected
          ? 'Collection recorded. Hand over the listed goods once.'
          : 'Collection was already recorded. Do not hand over goods again.';
    });
  }

  Future<bool> recover() async {
    if (hasPendingAdmission) return recoverAdmission();
    final pinned = profile, selected = counter;
    if (pinned == null || selected == null) return false;
    final key = _pending[selected.id];
    if (key == null) return false;
    final generation = _generation;
    return _run(() async {
      final data = await _request(
        'POST',
        'counters/${selected.id}/operations/$key/resolve',
      );
      _check(generation, pinned);
      if (data['operation_id'] != key ||
          _integer(data['school_id']) != pinned.schoolId ||
          _integer(data['actor_user_id']) != pinned.userId ||
          _integer(data['vendor_id']) != selected.id ||
          !['committed', 'abandoned'].contains(data['status'])) {
        throw const FormatException('Different saved handover');
      }
      if (data['status'] == 'committed') {
        final value = VendorReceipt(_map(data['order']), pinned, selected);
        if (value.status != 'collected') {
          throw const FormatException('Invalid recorded collection');
        }
        receipt = value;
      } else if (data['order'] != null) {
        throw const FormatException('Unexpected abandoned receipt');
      }
      _pending.remove(selected.id);
      try {
        await _save(pinned);
      } catch (_) {
        if (generation == _generation) _pending[selected.id] = key;
        rethrow;
      }
      _check(generation, pinned);
      message = data['status'] == 'committed'
          ? 'Collection was already recorded. Check the counter record; do not hand over goods again.'
          : 'No handover was recorded by that request. Review the original order again.';
    });
  }

  Future<bool> lookupAdmission(String value) async {
    final pinned = profile, selected = counter;
    if (pinned == null || selected == null || !pinned.ticketingAvailable) {
      return false;
    }
    final generation = _generation;
    final raw = value.trim();
    return _run(() async {
      final data = await _request(
        'POST',
        'counters/${selected.id}/tickets/lookup',
        data: raw.startsWith('navigateed:ticket:')
            ? {'qr_token': raw.substring(18)}
            : {'ticket_number': raw.toUpperCase()},
      );
      _check(generation, pinned);
      admission = VendorAdmission(data, pinned, selected);
      _admissionCredential = raw;
    });
  }

  Future<bool> admit() async {
    final pinned = profile, selected = counter, original = admission;
    final credential = _admissionCredential;
    if (pinned == null ||
        selected == null ||
        original?.canRedeem != true ||
        hasPending ||
        credential.isEmpty ||
        !pinned.ticketingAvailable) {
      return false;
    }
    final generation = _generation;
    return _run(() async {
      final key = const Uuid().v4();
      _pendingAdmissions[selected.id] = key;
      await _save(pinned);
      _check(generation, pinned);
      final data = await _request(
        'POST',
        'counters/${selected.id}/tickets/redeem',
        operation: key,
        data: {
          'operation_id': key,
          'revision': original!.revision,
          'confirmed': true,
          ...(credential.startsWith('navigateed:ticket:')
              ? {'qr_token': credential.substring(18)}
              : {'ticket_number': credential.toUpperCase()}),
        },
      );
      _check(generation, pinned);
      _settlementScope(data, pinned, selected);
      final result = VendorAdmission(_map(data['receipt']), pinned, selected);
      if (result.id != original.id ||
          result.revision != original.revision + 1 ||
          result.status != 'redeemed' ||
          result.redeemedBy != pinned.userId) {
        throw const FormatException('Different original admission outcome');
      }
      final replayed = _boolean(data['replayed']);
      _pendingAdmissions.remove(selected.id);
      try {
        await _save(pinned);
      } catch (_) {
        if (generation == _generation) _pendingAdmissions[selected.id] = key;
        rethrow;
      }
      _check(generation, pinned);
      admission = result;
      message = replayed
          ? 'Admission was already recorded. Check the original record; do not admit this ticket again.'
          : 'One admission recorded. Admit this ticket once.';
    });
  }

  Future<bool> recoverAdmission() async {
    final pinned = profile, selected = counter;
    if (pinned == null || selected == null) return false;
    final key = _pendingAdmissions[selected.id];
    if (key == null) return false;
    final generation = _generation;
    return _run(() async {
      final data = await _request(
        'POST',
        'counters/${selected.id}/ticket-operations/$key/resolve',
      );
      _check(generation, pinned);
      _settlementScope(data, pinned, selected);
      if (data['operation_id'] != key ||
          !['committed', 'abandoned'].contains(data['status'])) {
        throw const FormatException('Different original admission request');
      }
      VendorAdmission? result;
      if (data['status'] == 'committed') {
        result = VendorAdmission(_map(data['receipt']), pinned, selected);
        if (result.status != 'redeemed') {
          throw const FormatException('Original admission is incomplete');
        }
      } else if (data['receipt'] != null) {
        throw const FormatException('Unexpected abandoned admission receipt');
      }
      _pendingAdmissions.remove(selected.id);
      try {
        await _save(pinned);
      } catch (_) {
        if (generation == _generation) _pendingAdmissions[selected.id] = key;
        rethrow;
      }
      _check(generation, pinned);
      admission = result;
      message = data['status'] == 'committed'
          ? 'Admission was already recorded. Check the original record; do not admit this ticket again.'
          : 'No admission was recorded by that request. Review the original ticket again.';
    });
  }

  void _settlementScope(
    Map<String, dynamic> data,
    VendorProfile pinned,
    VendorCounter selected,
  ) {
    if (_integer(data['school_id']) != pinned.schoolId ||
        _integer(data['actor_user_id']) != pinned.userId ||
        _integer(data['vendor_id']) != selected.id) {
      throw const FormatException('Different settlement scope');
    }
  }

  Future<bool> loadSettlements({int pageNumber = 1}) async {
    final pinned = profile, selected = counter;
    if (pinned == null || selected == null) return false;
    final epoch = _generation;
    return _run(() async {
      final data = await _request(
        'GET',
        'counters/${selected.id}/settlements?page=$pageNumber',
      );
      _check(epoch, pinned);
      _settlementScope(data, pinned, selected);
      final current = _integer(data['page'], max: 1000000),
          last = _integer(data['last_page'], max: 1000000);
      if (current != pageNumber || current > last) {
        throw const FormatException('Invalid settlement page');
      }
      final rows = [
        for (final row in _list(data['items'], max: 20))
          VendorSettlement(_map(row), selected),
      ];
      if (rows.map((row) => row.id).toSet().length != rows.length) {
        throw const FormatException('Duplicate settlement rows');
      }
      settlements = rows;
      settlementPage = current;
      lastSettlementPage = last;
      settlementView = true;
    });
  }

  Future<bool> openSettlement(int id) async {
    final pinned = profile, selected = counter;
    if (pinned == null || selected == null || id < 1) return false;
    final epoch = _generation;
    return _run(() async {
      final data = await _request(
        'GET',
        'counters/${selected.id}/settlements/$id',
      );
      _check(epoch, pinned);
      _settlementScope(data, pinned, selected);
      final row = VendorSettlement(data, selected, detail: true);
      if (row.id != id) {
        throw const FormatException('Different original settlement');
      }
      settlement = row;
      settlementView = true;
    });
  }

  void suspend() {
    if (_disposed) return;
    _suspended = true;
    _generation++;
    _clearViews();
    busy = false;
    notifyListeners();
  }

  Future<bool> resume() async {
    if (_disposed) return false;
    _suspended = false;
    return profile == null ? false : refresh();
  }

  Future<void> signOut() async {
    final request = profile == null ? null : _request('POST', 'sign-out');
    clear();
    if (request != null) await request;
  }

  @override
  void dispose() {
    _disposed = true;
    _generation++;
    _token = '';
    profile = null;
    setup = null;
    counter = null;
    _pending.clear();
    _pendingAdmissions.clear();
    _clearViews();
    super.dispose();
  }
}
