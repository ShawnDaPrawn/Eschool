import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:uuid/uuid.dart';
import 'wallet_repository.dart';
import 'wallet_pending_store.dart';

class WalletState {
  const WalletState({
    this.index,
    this.statement,
    this.deskReceipts,
    this.busy = false,
    this.error,
    this.accessLost = false,
  });
  final PersonalWalletIndex? index;
  final WalletStatement? statement;
  final WalletDeskReceiptPage? deskReceipts;
  final bool busy, accessLost;
  final String? error;
}

class WalletCubit extends Cubit<WalletState> {
  WalletCubit(this.repository, {WalletPendingStore? pendingStore})
    : pendingStore = pendingStore ?? SharedPreferencesWalletPendingStore(),
      super(const WalletState());
  final WalletRepository repository;
  final WalletPendingStore pendingStore;
  final Set<String> _pending = {};
  WalletTransferPage? _transfers;
  WalletTransferReceipt? _lastTransfer;
  String? _resolutionMessage;
  WalletMoneyRepository? get _money => repository is WalletMoneyRepository
      ? repository as WalletMoneyRepository
      : null;
  bool get hasPendingMoney => _pending.isNotEmpty;
  WalletTransferPage? get transfers => _transfers;
  WalletTransferReceipt? get lastTransfer => _lastTransfer;
  String? get resolutionMessage => _resolutionMessage;
  Future<WalletState> Function()? _retry;
  bool get canRetry => _retry != null && !hasPendingMoney;
  Future<bool> _run(Future<WalletState> Function() work) async {
    if (isClosed || state.busy) return false;
    final before = state;
    _retry = work;
    emit(
      WalletState(
        index: before.index,
        statement: before.statement,
        deskReceipts: before.deskReceipts,
        busy: true,
      ),
    );
    try {
      final next = await work();
      if (isClosed) return false;
      if (!repository.isActive) {
        _retry = null;
        _clearPrivateMoney();
        emit(const WalletState(accessLost: true));
        return false;
      }
      _retry = null;
      emit(next);
      return true;
    } catch (error) {
      if (isClosed) return false;
      final denied =
          !repository.isActive ||
          (error is ApiException && [401, 403, 404].contains(error.statusCode));
      if (denied) {
        _retry = null;
        _clearPrivateMoney();
        emit(const WalletState(accessLost: true));
      } else {
        if (error is ApiException && [409, 422].contains(error.statusCode)) {
          _retry = null;
        }
        emit(
          WalletState(
            index: before.index,
            statement: before.statement,
            deskReceipts: before.deskReceipts,
            error: error is ApiException && error.isApiMessage
                ? error.errorMessage
                : 'Wallets could not be updated. Try again or refresh.',
          ),
        );
      }
      return false;
    }
  }

  void _clearPrivateMoney() {
    _transfers = null;
    _lastTransfer = null;
    _resolutionMessage = null;
  }

  Future<bool> load() => _run(() async {
    if (_money != null) {
      _pending
        ..clear()
        ..addAll(
          await pendingStore.read(repository.schoolId, repository.accountId),
        );
    }
    final index = await repository.index();
    if (_money != null) _transfers = await _money!.transfers();
    return WalletState(index: index);
  });

  Future<bool> transfer(int from, int to, int amountCents) {
    if (hasPendingMoney || _money == null || _transfers?.canTransfer != true) {
      return Future.value(false);
    }
    final operation = const Uuid().v4();
    return _run(() async {
      await pendingStore.put(
        repository.schoolId,
        repository.accountId,
        operation,
      );
      _pending.add(operation);
      if (!repository.isActive) {
        throw ApiException(
          'Your wallet access changed. Reopen Wallets.',
          statusCode: 403,
          isApiMessage: true,
        );
      }
      final receipt = await _money!.transfer(from, to, amountCents, operation);
      final index = await repository.index();
      final history = await _money!.transfers();
      await pendingStore.remove(
        repository.schoolId,
        repository.accountId,
        operation,
      );
      _pending.remove(operation);
      _lastTransfer = receipt;
      _transfers = history;
      _resolutionMessage = null;
      return WalletState(index: index);
    });
  }

  Future<bool> resolvePending() {
    if (_pending.isEmpty || _money == null) return Future.value(false);
    final operation = _pending.first;
    return _run(() async {
      final outcome = await _money!.resolve(operation);
      final index = await repository.index();
      final history = await _money!.transfers();
      await pendingStore.remove(
        repository.schoolId,
        repository.accountId,
        operation,
      );
      _pending.remove(operation);
      _transfers = history;
      _resolutionMessage =
          outcome.status == WalletOperationResolutionStatusEnum.committed
          ? 'The previous wallet request was recorded. Review your balances and transfer history.'
          : 'No money moved for the interrupted request. You can start again.';
      return WalletState(index: index);
    });
  }

  Future<bool> transferHistory({int page = 1}) {
    final before = state;
    if (_money == null) return Future.value(false);
    return _run(() async {
      _transfers = await _money!.transfers(page: page);
      return WalletState(index: before.index, statement: before.statement);
    });
  }

  Future<bool> show(int wallet, {int page = 1}) {
    final index = state.index;
    return _run(
      () async => WalletState(
        index: index,
        statement: await repository.statement(wallet, page: page),
      ),
    );
  }

  Future<bool> deskReceipts(int wallet, {int page = 1}) {
    final before = state;
    if (before.statement?.id != wallet) return Future.value(false);
    return _run(() async {
      final receipts = await repository.deskReceipts(wallet, page: page);
      final statement = await repository.statement(wallet);
      return WalletState(
        index: before.index,
        statement: statement,
        deskReceipts: receipts,
      );
    });
  }

  void hideDeskReceipts() {
    if (isClosed || state.busy) return;
    _retry = null;
    emit(WalletState(index: state.index, statement: state.statement));
  }

  Future<bool> open(int owner) {
    final operation = const Uuid().v4();
    return _run(() async {
      final out = await repository.open(owner, operation);
      final index = await repository.index();
      return WalletState(
        index: index,
        statement: await repository.statement(out.id),
      );
    });
  }

  Future<bool> controls(
    WalletStatement pinned,
    int daily,
    bool all,
    List<String> categories,
  ) {
    final operation = const Uuid().v4();
    return _run(() async {
      await repository.controls(
        pinned.id,
        revision: pinned.limits.revision,
        dailyCents: daily,
        allCategories: all,
        categories: categories,
        operation: operation,
      );
      final index = await repository.index();
      return WalletState(
        index: index,
        statement: await repository.statement(pinned.id),
      );
    });
  }

  Future<bool> retry() {
    final work = _retry;
    return work == null || hasPendingMoney ? Future.value(false) : _run(work);
  }
}
