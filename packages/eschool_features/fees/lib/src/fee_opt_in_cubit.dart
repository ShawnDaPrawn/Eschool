import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:flutter_bloc/flutter_bloc.dart';
import 'package:uuid/uuid.dart';
import 'fee_opt_in_repository.dart';

sealed class FeeOptInState {}

final class FeeOptInLoading extends FeeOptInState {}

final class FeeOptInReady extends FeeOptInState {
  FeeOptInReady(this.page, {this.message});
  final ParentFeeOptionalItemsPage page;
  final String? message;
}

final class FeeOptInReview extends FeeOptInState {
  FeeOptInReview(
    this.page,
    this.offer,
    this.active,
    this.operationId, {
    this.busy = false,
    this.retry = false,
  });
  final ParentFeeOptionalItemsPage page;
  final ParentFeeOptionalItem offer;
  final bool active, busy, retry;
  final String operationId;
}

final class FeeOptInFailure extends FeeOptInState {
  FeeOptInFailure({required this.accessChanged});
  final bool accessChanged;
}

class FeeOptInCubit extends Cubit<FeeOptInState> {
  FeeOptInCubit(this.repository, this.familyId) : super(FeeOptInLoading());
  final FeeOptInRepository repository;
  final int familyId;
  int _generation = 0;
  bool _denied(Object error) =>
      !repository.isActive ||
      (error is ApiException && [401, 403, 404].contains(error.statusCode));
  Future<void> load({int page = 1, String? message}) async {
    final generation = ++_generation;
    if (isClosed) return;
    emit(FeeOptInLoading());
    try {
      if (!repository.isActive) {
        throw ApiException('access_changed', statusCode: 403);
      }
      final value = await repository.items(familyId, page: page);
      if (isClosed || generation != _generation) return;
      emit(
        repository.isActive
            ? FeeOptInReady(value, message: message)
            : FeeOptInFailure(accessChanged: true),
      );
    } catch (error) {
      if (!isClosed && generation == _generation) {
        emit(FeeOptInFailure(accessChanged: _denied(error)));
      }
    }
  }

  void review(ParentFeeOptionalItem offer, {required bool active}) {
    final current = state;
    if (!repository.isActive) {
      emit(FeeOptInFailure(accessChanged: true));
      return;
    }
    if (current is! FeeOptInReady ||
        !current.page.writable ||
        !offer.canApprove ||
        !current.page.items.contains(offer) ||
        (active && (!offer.available || !supportedOptionalFee(offer))) ||
        (!active && !offer.selected)) {
      return;
    }
    emit(FeeOptInReview(current.page, offer, active, const Uuid().v4()));
  }

  void cancelReview() {
    final current = state;
    if (current is FeeOptInReview && !current.busy) {
      load(page: current.page.currentPage);
    }
  }

  Future<void> confirm() async {
    final current = state;
    if (current is! FeeOptInReview || current.busy || isClosed) return;
    final generation = ++_generation;
    emit(
      FeeOptInReview(
        current.page,
        current.offer,
        current.active,
        current.operationId,
        busy: true,
      ),
    );
    try {
      if (!repository.isActive) {
        throw ApiException('access_changed', statusCode: 403);
      }
      await repository.change(
        familyId,
        current.page.sessionYearId,
        current.offer,
        active: current.active,
        operationId: current.operationId,
      );
      if (isClosed || generation != _generation) return;
      if (!repository.isActive) {
        emit(FeeOptInFailure(accessChanged: true));
        return;
      }
      await load(
        page: current.page.currentPage,
        message: current.active
            ? 'Your approval was recorded.'
            : 'Your approval was withdrawn.',
      );
    } catch (error) {
      if (isClosed || generation != _generation) return;
      if (_denied(error)) {
        emit(FeeOptInFailure(accessChanged: true));
      } else if (error is ApiException &&
          [409, 422].contains(error.statusCode)) {
        await load(
          page: current.page.currentPage,
          message:
              'The item changed. Review its current terms before approving.',
        );
      } else {
        // Keep the exact reviewed terms and UUID after an uncertain network result.
        emit(
          FeeOptInReview(
            current.page,
            current.offer,
            current.active,
            current.operationId,
            retry: true,
          ),
        );
      }
    }
  }

  @override
  Future<void> close() {
    _generation++;
    return super.close();
  }
}
