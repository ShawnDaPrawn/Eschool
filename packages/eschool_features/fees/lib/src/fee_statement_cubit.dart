import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'fee_statement_repository.dart';

sealed class FeeStatementState {}

final class FeeStatementLoading extends FeeStatementState {}

final class FeeAccountsReady extends FeeStatementState {
  FeeAccountsReady(this.page);
  final ParentFeeAccountsPage page;
}

final class FeeStatementReady extends FeeStatementState {
  FeeStatementReady(this.statement);
  final ParentFeeStatement statement;
}

final class FeeStatementFailure extends FeeStatementState {
  FeeStatementFailure({required this.accessChanged});
  final bool accessChanged;
}

class FeeStatementCubit extends Cubit<FeeStatementState> {
  FeeStatementCubit(this.repository) : super(FeeStatementLoading());
  final FeeStatementRepository repository;
  int _generation = 0;

  Future<void> accounts({int page = 1}) => _load(
    () async => FeeAccountsReady(await repository.accounts(page: page)),
  );

  Future<void> statement(int id, {int chargesPage = 1, int receiptsPage = 1}) =>
      _load(
        () async => FeeStatementReady(
          await repository.statement(
            id,
            chargesPage: chargesPage,
            receiptsPage: receiptsPage,
          ),
        ),
      );

  Future<void> _load(Future<FeeStatementState> Function() action) async {
    final generation = ++_generation;
    if (isClosed) return;
    // Clear the preceding private data before any request or refresh.
    emit(FeeStatementLoading());
    try {
      if (!repository.isActive) {
        throw ApiException('access_changed', statusCode: 403);
      }
      final result = await action();
      if (isClosed || generation != _generation) return;
      emit(
        repository.isActive ? result : FeeStatementFailure(accessChanged: true),
      );
    } catch (error) {
      if (isClosed || generation != _generation) return;
      final denied =
          !repository.isActive ||
          (error is ApiException && [401, 403, 404].contains(error.statusCode));
      emit(FeeStatementFailure(accessChanged: denied));
    }
  }

  @override
  Future<void> close() {
    _generation++;
    return super.close();
  }
}
