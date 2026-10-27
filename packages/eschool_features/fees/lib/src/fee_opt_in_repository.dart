import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

abstract interface class FeeOptInRepository {
  int get schoolId;
  int get accountId;
  bool get isActive;
  Future<ParentFeeOptionalItemsPage> items(int familyId, {int page = 1});
  Future<ParentFeeItemConsentReceipt> change(
    int familyId,
    int year,
    ParentFeeOptionalItem offer, {
    required bool active,
    required String operationId,
  });
}

bool supportedOptionalFee(ParentFeeOptionalItem item) =>
    ['family', 'learner'].contains(item.scope.value) &&
    ['once_per_year', 'per_occurrence'].contains(item.billing.value);

class ApiFeeOptInRepository implements FeeOptInRepository {
  ApiFeeOptInRepository(
    this.client, {
    required this.schoolId,
    required this.accountId,
    required this.contextActive,
  }) : _token = client.authToken();
  final ApiClient client;
  @override
  final int schoolId, accountId;
  final bool Function() contextActive;
  final String _token;
  @override
  bool get isActive =>
      schoolId > 0 &&
      accountId > 0 &&
      contextActive() &&
      (!client.sendsBearerToken || client.authToken() == _token);
  void _guard() {
    if (!isActive) {
      throw ApiException(
        'Your fee access changed. Reopen this screen.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
  }

  Never _invalid() => throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
  bool _integer(Object? value, {bool nullable = false}) =>
      value is int || (nullable && value == null);
  Future<Map<String, dynamic>> _send(
    int family, {
    String suffix = '',
    String method = 'GET',
    Object? data,
    Map<String, dynamic>? query,
    String? key,
  }) async {
    _guard();
    if (family < 1) throw ArgumentError('Choose a family account.');
    final response = await client.send(
      method: method,
      url:
          '${AppConfig.apiV2Url}parent/fees/accounts/$family/optional-items$suffix',
      data: data,
      queryParameters: query,
      idempotencyKey: key,
      schoolId: schoolId,
      accountId: accountId,
      privateRequest: true,
      requestTimeout: const Duration(seconds: 20),
    );
    _guard();
    if (response?['data'] is! Map) _invalid();
    final value = Map<String, dynamic>.from(response!['data'] as Map);
    if (value['school_id'] != schoolId ||
        value['holder_user_id'] != accountId ||
        value['fee_account_id'] != family ||
        ![
          'school_id',
          'holder_user_id',
          'fee_account_id',
          'session_year_id',
        ].every((key) => _integer(value[key]))) {
      _invalid();
    }
    return value;
  }

  bool _offer(ParentFeeOptionalItem item) {
    final price = item.priceCents, share = item.shareCents, vat = item.vatCents;
    if (item.feeItemId < 1 ||
        item.revision < 0 ||
        item.name.isEmpty ||
        !RegExp(r'^[A-Z]{3}$').hasMatch(item.currency)) {
      return false;
    }
    if (item.scope.value == 'family' && item.learnerId != null) return false;
    if (item.scope.value == 'learner' &&
        (item.learnerId == null || item.learnerId! < 1)) {
      return false;
    }
    if (item.selected && item.revision == 0) return false;
    if (item.currentTermsApproved && (!item.selected || !item.available)) {
      return false;
    }
    if (price == null) {
      if (share != null || vat != null || item.available) return false;
    } else if (price < 1 ||
        price > 100000000000 ||
        share == null ||
        vat == null ||
        share < 0 ||
        share > price ||
        vat < 0 ||
        vat > share) {
      return false;
    }
    return !item.available ||
        (item.fingerprint != null &&
            RegExp(r'^[a-f0-9]{64}$').hasMatch(item.fingerprint!));
  }

  @override
  Future<ParentFeeOptionalItemsPage> items(int familyId, {int page = 1}) async {
    if (page < 1 || page > 1000000) {
      throw ArgumentError('Choose a supported page.');
    }
    final raw = await _send(familyId, query: {'page': page, 'per_page': 20});
    if (raw['items'] is! List ||
        ![
          'current_page',
          'per_page',
          'total',
        ].every((key) => _integer(raw[key]))) {
      _invalid();
    }
    for (final row in raw['items'] as List) {
      if (row is! Map ||
          !['fee_item_id', 'revision'].every((key) => _integer(row[key])) ||
          ![
            'learner_id',
            'price_cents',
            'share_cents',
            'vat_cents',
          ].every((key) => _integer(row[key], nullable: true))) {
        _invalid();
      }
    }
    ParentFeeOptionalItemsPage result;
    try {
      result = ParentFeeOptionalItemsPage.fromJson(raw);
    } catch (_) {
      _invalid();
    }
    if (result.sessionYearId < 1 ||
        result.currentPage != page ||
        result.perPage != 20 ||
        result.total < 0 ||
        result.total > 20000 ||
        result.items.length > 20 ||
        result.items.any((item) => !_offer(item))) {
      _invalid();
    }
    final keys = result.items
        .map((item) => '${item.feeItemId}:${item.learnerId}')
        .toSet();
    if (keys.length != result.items.length) _invalid();
    return result;
  }

  @override
  Future<ParentFeeItemConsentReceipt> change(
    int familyId,
    int year,
    ParentFeeOptionalItem offer, {
    required bool active,
    required String operationId,
  }) async {
    if (!_offer(offer) ||
        year < 1 ||
        !offer.canApprove ||
        (active && (!offer.available || !supportedOptionalFee(offer))) ||
        (!active && !offer.selected)) {
      _invalid();
    }
    final request = ParentFeeItemConsentChange(
      operationId: operationId,
      sessionYearId: year,
      learnerId: offer.learnerId,
      revision: offer.revision,
      active: active,
      fingerprint: offer.fingerprint,
      confirmed: true,
    );
    final raw = await _send(
      familyId,
      suffix: '/${offer.feeItemId}/consents',
      method: 'POST',
      data: request.toJson(),
      key: operationId,
    );
    if (!['id', 'fee_item_id', 'revision'].every((key) => _integer(raw[key])) ||
        !_integer(raw['learner_id'], nullable: true) ||
        raw['fee_item_id'] != offer.feeItemId ||
        raw['session_year_id'] != year ||
        raw['learner_id'] != offer.learnerId ||
        raw['active'] != active ||
        raw['revision'] != offer.revision + 1) {
      _invalid();
    }
    try {
      return ParentFeeItemConsentReceipt.fromJson(raw);
    } catch (_) {
      _invalid();
    }
  }
}
