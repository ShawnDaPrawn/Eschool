import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:uuid/uuid.dart';

import 'commerce_repository.dart';
import 'wallet_pending_store.dart';

class CommerceState {
  const CommerceState({
    this.wallets,
    this.vendors,
    this.catalogue,
    this.orders,
    this.quote,
    this.busy = false,
    this.accessLost = false,
    this.error,
    this.message,
  });
  final PersonalWalletIndex? wallets;
  final CommerceVendorPage? vendors;
  final CommerceCatalogue? catalogue;
  final CommerceOrderPage? orders;
  final CommerceQuote? quote;
  final bool busy, accessLost;
  final String? error, message;
}

class CommerceCubit extends Cubit<CommerceState> {
  CommerceCubit(this.repository, {WalletPendingStore? pendingStore})
    : pendingStore = pendingStore ?? SharedPreferencesWalletPendingStore(),
      super(const CommerceState());
  final CommerceRepository repository;
  final WalletPendingStore pendingStore;
  final Set<String> _pending = {};
  bool get hasPendingMoney => _pending.isNotEmpty;

  Future<bool> _run(Future<CommerceState> Function() work) async {
    if (isClosed || state.busy) return false;
    // Every refresh/navigation/write clears old private receipts before awaiting authority.
    emit(const CommerceState(busy: true));
    try {
      final next = await work();
      if (isClosed) return false;
      if (!repository.isActive) {
        emit(const CommerceState(accessLost: true));
        return false;
      }
      emit(next);
      return true;
    } catch (error) {
      if (isClosed) return false;
      final denied =
          !repository.isActive ||
          (error is ApiException && [401, 403, 404].contains(error.statusCode));
      emit(
        CommerceState(
          accessLost: denied,
          error: error is ApiException && error.isApiMessage
              ? error.errorMessage
              : 'Shop and orders could not be updated. Refresh or check the request outcome.',
        ),
      );
      return false;
    }
  }

  Future<CommerceState> _fresh({
    int vendorPage = 1,
    int orderPage = 1,
    CommerceCatalogue? catalogue,
    CommerceQuote? quote,
    String? message,
  }) async {
    final wallets = await repository.walletChoices();
    final vendors = await repository.vendors(page: vendorPage);
    final orders = await repository.orders(page: orderPage);
    return CommerceState(
      wallets: wallets,
      vendors: vendors,
      orders: orders,
      catalogue: catalogue,
      quote: quote,
      message: message,
    );
  }

  Future<bool> load({int vendorPage = 1, int orderPage = 1}) => _run(() async {
    _pending
      ..clear()
      ..addAll(
        await pendingStore.read(repository.schoolId, repository.accountId),
      );
    return _fresh(vendorPage: vendorPage, orderPage: orderPage);
  });

  Future<bool> openVendor(int vendor, {int page = 1}) => _run(() async {
    final fresh = await _fresh();
    final catalogue = await repository.catalogue(vendor, page: page);
    return CommerceState(
      wallets: fresh.wallets,
      vendors: fresh.vendors,
      orders: fresh.orders,
      catalogue: catalogue,
    );
  });

  Future<bool> review(
    int vendor,
    int wallet,
    Map<int, int> quantities, {
    int? learner,
  }) {
    if (hasPendingMoney ||
        state.vendors?.canPurchase != true ||
        state.catalogue?.canPurchase != true) {
      return Future.value(false);
    }
    final cart = Map<int, int>.from(quantities);
    return _run(() async {
      final fresh = await _fresh();
      final catalogue = await repository.catalogue(vendor);
      final quote = await repository.quote(
        vendor,
        wallet,
        cart,
        learner: learner,
      );
      return CommerceState(
        wallets: fresh.wallets,
        vendors: fresh.vendors,
        orders: fresh.orders,
        catalogue: catalogue,
        quote: quote,
      );
    });
  }

  Future<void> _retain(String operation) async {
    await pendingStore.put(
      repository.schoolId,
      repository.accountId,
      operation,
    );
    _pending.add(operation);
    if (!repository.isActive) {
      throw ApiException(
        'Your storefront access changed. Reopen Wallets.',
        statusCode: 403,
        isApiMessage: true,
      );
    }
  }

  Future<void> _remove(String operation) async {
    await pendingStore.remove(
      repository.schoolId,
      repository.accountId,
      operation,
    );
    _pending.remove(operation);
  }

  Future<bool> purchase() {
    final quote = state.quote;
    if (hasPendingMoney ||
        quote == null ||
        state.vendors?.canPurchase != true) {
      return Future.value(false);
    }
    final operation = const Uuid().v4();
    return _run(() async {
      await _retain(operation);
      await repository.purchase(quote, operation);
      final fresh = await _fresh(
        message:
            'Purchase checked. Open the original receipt and collection QR in Your orders.',
      );
      await _remove(operation);
      return fresh;
    });
  }

  Future<bool> cancel(int order, String reason) {
    if (hasPendingMoney ||
        state.orders?.readOnly != false ||
        state.orders?.items.any((o) => o.id == order && o.canCancel) != true) {
      return Future.value(false);
    }
    final operation = const Uuid().v4();
    return _run(() async {
      await _retain(operation);
      await repository.cancel(order, reason, operation);
      final fresh = await _fresh(
        message:
            'Cancellation checked. Review the original wallet balance and order status.',
      );
      await _remove(operation);
      return fresh;
    });
  }

  Future<bool> recover() => _run(() async {
    _pending.addAll(
      await pendingStore.read(repository.schoolId, repository.accountId),
    );
    for (final operation in _pending.toList()) {
      final result = await repository.resolve(operation);
      if (result.operationId != operation ||
          result.schoolId != repository.schoolId ||
          result.actorUserId != repository.accountId ||
          result.status ==
              WalletOperationResolutionStatusEnum.unknownDefaultOpenApi) {
        throw StateError('The original request outcome could not be checked.');
      }
      await _remove(operation);
    }
    return _fresh(
      message:
          'Wallet request outcomes checked. Review balances and orders before continuing.',
    );
  });
}
