import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

abstract interface class FeeStatementRepository {
  int get schoolId;
  int get accountId;
  bool get isActive;
  Future<ParentFeeAccountsPage> accounts({int page = 1, int perPage = 20});
  Future<ParentFeeStatement> statement(
    int familyId, {
    int chargesPage = 1,
    int receiptsPage = 1,
    int perPage = 20,
  });
}

class ApiFeeStatementRepository implements FeeStatementRepository {
  ApiFeeStatementRepository(
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

  void _paging(int page, int perPage) {
    if (page < 1 || page > 1000000 || perPage < 1 || perPage > 50) {
      throw ArgumentError('Use a supported statement page.');
    }
  }

  Future<Map<String, dynamic>> _send(
    String path,
    Map<String, dynamic> query,
  ) async {
    _guard();
    final response = await client.send(
      method: 'GET',
      url: '${AppConfig.apiV2Url}parent/fees/accounts$path',
      schoolId: schoolId,
      accountId: accountId,
      queryParameters: query,
      privateRequest: true,
      requestTimeout: const Duration(seconds: 20),
    );
    _guard();
    if (response?['data'] is! Map) _invalid();
    return Map<String, dynamic>.from(response!['data'] as Map);
  }

  bool _money(
    String currency,
    int charged,
    int allocated,
    int outstanding,
    int credit,
    int net,
  ) =>
      RegExp(r'^[A-Z]{3}$').hasMatch(currency) &&
      charged >= 0 &&
      allocated >= 0 &&
      outstanding >= 0 &&
      credit >= 0 &&
      charged == allocated + outstanding &&
      net == outstanding - credit;

  @override
  Future<ParentFeeAccountsPage> accounts({
    int page = 1,
    int perPage = 20,
  }) async {
    _paging(page, perPage);
    final result = ParentFeeAccountsPage.fromJson(
      await _send('', {'page': page, 'per_page': perPage}),
    );
    if (result.schoolId != schoolId ||
        result.holderUserId != accountId ||
        result.currentPage != page ||
        result.perPage != perPage ||
        result.total < 0 ||
        result.items.length > perPage) {
      _invalid();
    }
    for (final item in result.items) {
      if (item.schoolId != schoolId ||
          item.holderUserId != accountId ||
          item.id < 1 ||
          item.scope !=
              ParentFeeAccountSummaryScopeEnum.yourVisibleLedgerRecords ||
          !_money(
            item.currency,
            item.chargedCents,
            item.allocatedCents,
            item.outstandingCents,
            item.creditCents,
            item.netCents,
          )) {
        _invalid();
      }
    }
    return result;
  }

  @override
  Future<ParentFeeStatement> statement(
    int familyId, {
    int chargesPage = 1,
    int receiptsPage = 1,
    int perPage = 20,
  }) async {
    if (familyId < 1) throw ArgumentError.value(familyId);
    _paging(chargesPage, perPage);
    _paging(receiptsPage, perPage);
    final result = ParentFeeStatement.fromJson(
      await _send('/$familyId/statement', {
        'charges_page': chargesPage,
        'receipts_page': receiptsPage,
        'per_page': perPage,
      }),
    );
    if (result.id != familyId ||
        result.schoolId != schoolId ||
        result.holderUserId != accountId ||
        result.scope != ParentFeeStatementScopeEnum.yourVisibleLedgerRecords ||
        !_money(
          result.currency,
          result.chargedCents,
          result.allocatedCents,
          result.outstandingCents,
          result.creditCents,
          result.netCents,
        ) ||
        result.charges.currentPage != chargesPage ||
        result.receipts.currentPage != receiptsPage ||
        result.charges.perPage != perPage ||
        result.receipts.perPage != perPage ||
        result.charges.items.length > perPage ||
        result.receipts.items.length > perPage ||
        result.charges.total < 0 ||
        result.receipts.total < 0) {
      _invalid();
    }
    for (final row in result.charges.items) {
      if (row.learnerId < 1 ||
          row.invoiceLineId < 1 ||
          row.currency != result.currency ||
          row.chargedCents < 0 ||
          row.allocatedCents < 0 ||
          row.outstandingCents < 0 ||
          row.chargedCents != row.allocatedCents + row.outstandingCents) {
        _invalid();
      }
    }
    for (final row in result.receipts.items) {
      if (row.id < 1 ||
          row.currency != result.currency ||
          row.amountCents < 1 ||
          row.allocatedCents < 0 ||
          row.creditCents < 0 ||
          row.amountCents != row.allocatedCents + row.creditCents) {
        _invalid();
      }
    }
    return result;
  }
}
