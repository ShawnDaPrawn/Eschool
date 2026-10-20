import 'package:eschool_teacher/data/models/leave.dart';
import 'package:eschool_teacher/data/repositories/leaveRepository.dart';
import 'package:eschool_teacher/utils/api.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class AddLeaveState {}

class AddLeaveInitial extends AddLeaveState {}

class AddLeaveInProgress extends AddLeaveState {}

class AddLeaveSuccess extends AddLeaveState {}

class AddLeaveFailure extends AddLeaveState {
  AddLeaveFailure(this.exception);
  final ApiException exception;
}

class AddLeaveCubit extends Cubit<AddLeaveState> {
  AddLeaveCubit(this._leaveRepository) : super(AddLeaveInitial());
  final LeaveRepository _leaveRepository;

  Future<void> addLeave({
    required String reason,
    required List<LeaveDateWithType> leaveDetails,
    List<String> filePaths = const [],
  }) async {
    emit(AddLeaveInProgress());
    try {
      await _leaveRepository.addLeaveRequest(
        reason: reason,
        leaveDetails: leaveDetails,
        filePaths: filePaths,
      );
      emit(AddLeaveSuccess());
    } catch (e) {
      emit(AddLeaveFailure(ApiException.fromException(e)));
    }
  }
}
