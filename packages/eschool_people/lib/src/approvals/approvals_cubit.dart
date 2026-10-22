import 'package:equatable/equatable.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/src/approvals/approvals_repository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

enum ApprovalsStatus { loading, unavailable, loaded, failure }

class ApprovalsState extends Equatable {
  const ApprovalsState({
    required this.status,
    this.approvals = const [],
    this.busy = const {},
    this.error,
    this.actionError,
  });

  final ApprovalsStatus status;
  final List<GuardianApproval> approvals;

  /// Learners whose decision is being sent.
  final Set<int> busy;
  final ApiException? error;

  /// Why the last approve or withdraw failed.
  final ApiException? actionError;

  /// Children waiting for the guardian: never approved, or withdrawn.
  List<GuardianApproval> get waiting =>
      approvals.where((approval) => !approval.isApproved).toList();

  List<GuardianApproval> get approved =>
      approvals.where((approval) => approval.isApproved).toList();

  ApprovalsState copyWith({
    List<GuardianApproval>? approvals,
    Set<int>? busy,
    ApiException? actionError,
  }) => ApprovalsState(
    status: status,
    approvals: approvals ?? this.approvals,
    busy: busy ?? this.busy,
    error: error,
    actionError: actionError,
  );

  @override
  List<Object?> get props => [status, approvals, busy, error, actionError];
}

/// The Approvals screen: a guardian's children, approving one (with the
/// consent text) or withdrawing an approval.
class ApprovalsCubit extends Cubit<ApprovalsState> {
  ApprovalsCubit(this._repository)
    : super(const ApprovalsState(status: ApprovalsStatus.loading));

  final ApprovalsRepository _repository;

  Future<void> load() async {
    emit(const ApprovalsState(status: ApprovalsStatus.loading));
    try {
      final List<GuardianApproval>? approvals = await _repository.fetch();
      if (isClosed) {
        return;
      }
      emit(
        approvals == null
            ? const ApprovalsState(status: ApprovalsStatus.unavailable)
            : ApprovalsState(
                status: ApprovalsStatus.loaded,
                approvals: approvals,
              ),
      );
    } catch (e) {
      if (!isClosed) {
        emit(
          ApprovalsState(
            status: ApprovalsStatus.failure,
            error: ApiException.fromException(e),
          ),
        );
      }
    }
  }

  /// Approves [approval]'s learner with the consent shown. Answers whether
  /// it worked.
  Future<bool> approve(GuardianApproval approval) => _decide(
    approval,
    () => _repository.approve(
      approval.learner.id,
      consentVersion: approval.consent.version,
    ),
  );

  /// Withdraws the approval of [approval]'s learner.
  Future<bool> withdraw(GuardianApproval approval) => _decide(
    approval,
    () => _repository.withdraw(
      approval.learner.id,
      consentVersion: approval.consent.version,
    ),
  );

  Future<bool> _decide(
    GuardianApproval approval,
    Future<void> Function() send,
  ) async {
    final int id = approval.learner.id;
    if (state.busy.contains(id)) {
      return false;
    }
    emit(state.copyWith(busy: {...state.busy, id}));
    try {
      await send();
      final List<GuardianApproval>? fresh = await _repository.fetch();
      if (!isClosed) {
        emit(
          state.copyWith(
            approvals: fresh ?? state.approvals,
            busy: {...state.busy}..remove(id),
          ),
        );
      }
      return true;
    } catch (e) {
      if (!isClosed) {
        emit(
          state.copyWith(
            busy: {...state.busy}..remove(id),
            actionError: ApiException.fromException(e),
          ),
        );
      }
      return false;
    }
  }
}
