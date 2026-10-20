import 'package:eschool/data/repositories/leaveRepository.dart';
import 'package:eschool/utils/api.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class DeleteLeaveState {}

class DeleteLeaveInitial extends DeleteLeaveState {}

class DeleteLeaveInProgress extends DeleteLeaveState {}

class DeleteLeaveSuccess extends DeleteLeaveState {}

class DeleteLeaveFailure extends DeleteLeaveState {
  DeleteLeaveFailure(this.exception);
  final ApiException exception;
}

class DeleteLeaveCubit extends Cubit<DeleteLeaveState> {
  DeleteLeaveCubit(this._leaveRepository) : super(DeleteLeaveInitial());
  final LeaveRepository _leaveRepository;

  Future<void> deleteLeave({
    required int leaveId,
    required bool isParent,
  }) async {
    emit(DeleteLeaveInProgress());
    try {
      await _leaveRepository.deleteLeave(leaveId: leaveId, isParent: isParent);
      emit(DeleteLeaveSuccess());
    } catch (e) {
      emit(DeleteLeaveFailure(ApiException.fromException(e)));
    }
  }
}
