import 'package:eschool/data/models/feesTransaction.dart';
import 'package:eschool/data/repositories/studentRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class FeesTransactionState {}

class FeesTransactionInitial extends FeesTransactionState {}

class FeesTransactionFetchInProgress extends FeesTransactionState {}

class FeesTransactionFetchFailure extends FeesTransactionState {

  FeesTransactionFetchFailure({required this.errorMessage, this.page});
  final String errorMessage;
  final int? page;
}

class FeesTransactionFetchSuccess extends FeesTransactionState {

  FeesTransactionFetchSuccess({
    required this.feesTransactions,
    required this.totalPage,
    required this.currentPage,
    required this.moreTransactionsFetchError,
    required this.moreTransactionsFetchInProgress,
  });
  final List<FeesTransaction> feesTransactions;
  final int totalPage;
  final int currentPage;
  final bool moreTransactionsFetchError;
  final bool moreTransactionsFetchInProgress;

  FeesTransactionFetchSuccess copyWith({
    List<FeesTransaction>? newTransactions,
    int? newTotalPage,
    int? newCurrentPage,
    bool? newFetchMoreTransactionsInProgress,
    bool? newFetchMoreTransactionsError,
  }) {
    return FeesTransactionFetchSuccess(
      feesTransactions: newTransactions ?? feesTransactions,
      totalPage: newTotalPage ?? totalPage,
      currentPage: newCurrentPage ?? currentPage,
      moreTransactionsFetchInProgress:
          newFetchMoreTransactionsInProgress ?? moreTransactionsFetchInProgress,
      moreTransactionsFetchError:
          newFetchMoreTransactionsError ?? moreTransactionsFetchError,
    );
  }
}

class FeesTransactionsCubit extends Cubit<FeesTransactionState> {

  FeesTransactionsCubit(this._studentRepository)
      : super(FeesTransactionInitial());
  final StudentRepository _studentRepository;

  bool isLoading() {
    if (state is FeesTransactionFetchInProgress) {
      return true;
    }
    return false;
  }

  void fetchFeesTransactions({
    required int page,
    required bool isStudentLoggedIn,
    int? childId,
    int? sessionYearId,
  }) {
    emit(FeesTransactionFetchInProgress());
    _studentRepository
        .fetchFeesTransactions(
          page: page,
          isStudentLoggedIn: isStudentLoggedIn,
          childId: childId,
          sessionYearId: sessionYearId,
        )
        .then(
          (value) => emit(
            FeesTransactionFetchSuccess(
              feesTransactions: value['feesTransactions'],
              totalPage: value['totalPage'],
              currentPage: value['currentPage'],
              moreTransactionsFetchError: false,
              moreTransactionsFetchInProgress: false,
            ),
          ),
        )
        .catchError(
          (e) => emit(
            FeesTransactionFetchFailure(
              errorMessage: e.toString(),
              page: page,
            ),
          ),
        );
  }

  Future<void> fetchMoreFeesTransactions({
    required bool isStudentLoggedIn,
    int? childId,
    int? sessionYearId,
  }) async {
    if (state is FeesTransactionFetchSuccess) {
      final stateAs = state as FeesTransactionFetchSuccess;
      if (stateAs.moreTransactionsFetchInProgress) {
        return;
      }
      try {
        emit(stateAs.copyWith(newFetchMoreTransactionsInProgress: true));

        final moreTransactionResult =
            await _studentRepository.fetchFeesTransactions(
          page: stateAs.currentPage + 1,
          isStudentLoggedIn: isStudentLoggedIn,
          childId: childId,
          sessionYearId: sessionYearId,
        );

        final List<FeesTransaction> transactions = stateAs.feesTransactions;

        transactions.addAll(moreTransactionResult['feesTransactions']);

        emit(
          FeesTransactionFetchSuccess(
            feesTransactions: transactions,
            totalPage: moreTransactionResult['totalPage'],
            currentPage: moreTransactionResult['currentPage'],
            moreTransactionsFetchError: false,
            moreTransactionsFetchInProgress: false,
          ),
        );
      } catch (e) {
        emit(
          (state as FeesTransactionFetchSuccess).copyWith(
            newFetchMoreTransactionsInProgress: false,
            newFetchMoreTransactionsError: true,
          ),
        );
      }
    }
  }

  bool hasMore() {
    if (state is FeesTransactionFetchSuccess) {
      return (state as FeesTransactionFetchSuccess).currentPage <
          (state as FeesTransactionFetchSuccess).totalPage;
    }
    return false;
  }
}
