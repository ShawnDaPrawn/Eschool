import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:uuid/uuid.dart';

import 'ticket_repository.dart';
import 'wallet_pending_store.dart';

class TicketState {
  const TicketState({
    this.page,
    this.receiving,
    this.preview,
    this.busy = false,
    this.accessLost = false,
    this.error,
    this.message,
  });
  final AdmissionTicketPage? page;
  final AdmissionReceiveCode? receiving;
  final AdmissionTransferPreview? preview;
  final bool busy, accessLost;
  final String? error, message;
}

class TicketCubit extends Cubit<TicketState> {
  TicketCubit(this.repository, {WalletPendingStore? pendingStore})
    : pendingStore = pendingStore ?? SharedPreferencesWalletPendingStore(),
      super(const TicketState());
  final TicketRepository repository;
  final WalletPendingStore pendingStore;
  final Set<String> _pending = {};
  int _generation = 0;
  String _receiveCode = '';
  bool get hasPending => _pending.isNotEmpty;

  void suspend() {
    _generation++;
    _receiveCode = '';
    if (!isClosed) emit(const TicketState());
  }

  Future<bool> _run(Future<TicketState> Function() work) async {
    if (isClosed || state.busy) return false;
    final generation = _generation;
    emit(const TicketState(busy: true));
    try {
      final next = await work();
      if (isClosed || generation != _generation) return false;
      if (!repository.isActive) {
        _receiveCode = '';
        emit(const TicketState(accessLost: true));
        return false;
      }
      emit(next);
      return true;
    } catch (error) {
      if (isClosed || generation != _generation) return false;
      _receiveCode = '';
      final denied =
          !repository.isActive ||
          (error is ApiException && [401, 403, 404].contains(error.statusCode));
      emit(
        TicketState(
          accessLost: denied,
          error: error is ApiException && error.isApiMessage
              ? error.errorMessage
              : 'Tickets could not be updated. Refresh or check the original request outcome.',
        ),
      );
      return false;
    }
  }

  Future<bool> load({int page = 1}) => _run(() async {
    _receiveCode = '';
    _pending
      ..clear()
      ..addAll(
        await pendingStore.read(repository.schoolId, repository.accountId),
      );
    return TicketState(page: await repository.tickets(page: page));
  });

  Future<bool> receivingCode() => _run(() async {
    _receiveCode = '';
    final page = await repository.tickets();
    final code = await repository.receiveCode();
    return TicketState(page: page, receiving: code);
  });

  Future<bool> preview(int ticket, String code) {
    if (hasPending ||
        state.page?.items.any((t) => t.id == ticket && t.canTransfer) != true) {
      return Future.value(false);
    }
    final pageNumber = state.page!.page;
    return _run(() async {
      _receiveCode = '';
      final preview = await repository.preview(ticket, code);
      final page = await repository.tickets(page: pageNumber);
      if (!page.items.any(
        (row) =>
            row.id == ticket &&
            row.revision == preview.revision &&
            row.canTransfer,
      )) {
        return TicketState(
          page: page,
          error:
              'Tickets could not be updated. Refresh or check the original request outcome.',
        );
      }
      _receiveCode = code;
      return TicketState(page: page, preview: preview);
    });
  }

  Future<bool> transfer() {
    final preview = state.preview;
    final code = _receiveCode;
    if (hasPending || preview == null || code.isEmpty) {
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
      if (!repository.isActive) throw StateError('Ticket profile changed');
      await repository.transfer(preview, code, operation);
      _receiveCode = '';
      await pendingStore.remove(
        repository.schoolId,
        repository.accountId,
        operation,
      );
      _pending.remove(operation);
      return TicketState(
        page: await repository.tickets(),
        message:
            'Original ticket transfer recorded. Only the current holder can open its new admission code.',
      );
    });
  }

  Future<bool> recover() => _run(() async {
    _receiveCode = '';
    _pending.addAll(
      await pendingStore.read(repository.schoolId, repository.accountId),
    );
    for (final operation in _pending.toList()) {
      final result = await repository.resolve(operation);
      if (result.schoolId != repository.schoolId ||
          result.actorUserId != repository.accountId ||
          result.operationId != operation ||
          result.status ==
              WalletOperationResolutionStatusEnum.unknownDefaultOpenApi) {
        throw StateError('Original ticket request outcome is unavailable');
      }
      await pendingStore.remove(
        repository.schoolId,
        repository.accountId,
        operation,
      );
      _pending.remove(operation);
    }
    return TicketState(
      page: await repository.tickets(),
      message:
          'Original request outcomes checked. Review current tickets before continuing.',
    );
  });
}
