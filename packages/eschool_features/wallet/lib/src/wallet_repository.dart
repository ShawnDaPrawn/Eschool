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

class ApiWalletRepository implements WalletRepository {
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
                  k.endsWith('_id') ||
                  [
                    'revision',
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
      }
    }
    _owners
      ..clear()
      ..addAll(owners);
    _wallets
      ..clear()
      ..addAll(wallets);
    return out;
  }

  WalletStatement _statement(
    Map<String, dynamic> data,
    int wallet, {
    int page = 1,
    int perPage = 30,
  }) {
    final out = WalletStatement.fromJson(data);
    if (out.id != wallet) _invalid();
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
    final out = _statement(data, id);
    if (out.ownerUserId != owner) _invalid();
    _wallets.add(id);
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
