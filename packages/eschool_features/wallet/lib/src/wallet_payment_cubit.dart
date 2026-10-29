import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:uuid/uuid.dart';

import 'wallet_payment_repository.dart';
import 'wallet_pending_store.dart';

class WalletPaymentState {
  const WalletPaymentState({
    this.page,
    this.payment,
    this.quote,
    this.busy = false,
    this.accessLost = false,
    this.error,
  });
  final WalletPaymentPersonalPage? page;
  final WalletPaymentPersonal? payment;
  final CommerceQuote? quote;
  final bool busy, accessLost;
  final String? error;
}

class WalletPaymentCubit extends Cubit<WalletPaymentState> {
  WalletPaymentCubit(this.repository, {WalletPendingStore? pendingStore})
    : pendingStore = pendingStore ?? SharedPreferencesWalletPendingStore(),
      super(const WalletPaymentState());
  final WalletPaymentRepository repository;
  final WalletPendingStore pendingStore;
  final Set<String> _pending = {};
  int _generation = 0;
  bool get hasPending => _pending.isNotEmpty;
  void suspend() {
    _generation++;
    if (!isClosed) emit(const WalletPaymentState());
  }

  Future<bool> _run(Future<WalletPaymentState> Function() work) async {
    if (isClosed || state.busy) return false;
    final epoch = _generation;
    emit(const WalletPaymentState(busy: true));
    try {
      final next = await work();
      if (isClosed || epoch != _generation) return false;
      if (!repository.isActive) {
        emit(const WalletPaymentState(accessLost: true));
        return false;
      }
      emit(next);
      return true;
    } catch (error) {
      if (isClosed || epoch != _generation) return false;
      emit(
        WalletPaymentState(
          accessLost:
              !repository.isActive ||
              error is ApiException &&
                  [401, 403, 404].contains(error.statusCode),
          error: error is ApiException && error.isApiMessage
              ? error.errorMessage
              : 'Wallet payments could not be updated. Check the original request outcome before trying again.',
        ),
      );
      return false;
    }
  }

  Future<bool> load({CommerceQuote? quote, int page = 1}) => _run(() async {
    _pending
      ..clear()
      ..addAll(
        await pendingStore.read(repository.schoolId, repository.accountId),
      );
    return WalletPaymentState(
      page: await repository.listing(page: page),
      quote: quote,
    );
  });
  Future<bool> issue() {
    final quote = state.quote;
    if (quote == null || hasPending) return Future.value(false);
    final operation = const Uuid().v4();
    return _run(() async {
      await pendingStore.put(
        repository.schoolId,
        repository.accountId,
        operation,
      );
      _pending.add(operation);
      if (!repository.isActive) throw StateError('Wallet authority changed.');
      final payment = await repository.issue(quote, operation);
      await pendingStore.remove(
        repository.schoolId,
        repository.accountId,
        operation,
      );
      _pending.remove(operation);
      return WalletPaymentState(payment: payment);
    });
  }

  Future<bool> read(String id) =>
      _run(() async => WalletPaymentState(payment: await repository.read(id)));
  Future<bool> cancel() {
    final row = state.payment;
    if (row == null || hasPending) return Future.value(false);
    return _run(
      () async => WalletPaymentState(payment: await repository.cancel(row.id)),
    );
  }

  Future<bool> recover() => _run(() async {
    _pending.addAll(
      await pendingStore.read(repository.schoolId, repository.accountId),
    );
    for (final operation in _pending.toList()) {
      final outcome = await repository.resolve(operation);
      if (outcome.operationId != operation ||
          outcome.schoolId != repository.schoolId ||
          outcome.actorUserId != repository.accountId ||
          outcome.status ==
              WalletOperationResolutionStatusEnum.unknownDefaultOpenApi) {
        throw StateError('The original wallet outcome could not be verified.');
      }
      await pendingStore.remove(
        repository.schoolId,
        repository.accountId,
        operation,
      );
      _pending.remove(operation);
    }
    return WalletPaymentState(page: await repository.listing());
  });
}
