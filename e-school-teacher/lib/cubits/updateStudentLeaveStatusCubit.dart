import 'package:eschool_teacher/data/repositories/studentLeaveRepository.dart';
import 'package:eschool_teacher/utils/api.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class UpdateStudentLeaveStatusState {}

class UpdateStudentLeaveStatusInitial extends UpdateStudentLeaveStatusState {}

class UpdateStudentLeaveStatusInProgress
    extends UpdateStudentLeaveStatusState {}

class UpdateStudentLeaveStatusSuccess extends UpdateStudentLeaveStatusState {
  UpdateStudentLeaveStatusSuccess({required this.status, required this.id});
  final String status;
  final int id;

  bool get isStatusPending => status == '0';
}

class UpdateStudentLeaveStatusFailure extends UpdateStudentLeaveStatusState {
  UpdateStudentLeaveStatusFailure(this.exception);
  final ApiException exception;
}

class UpdateStudentLeaveStatusCubit
    extends Cubit<UpdateStudentLeaveStatusState> {
  UpdateStudentLeaveStatusCubit(this.studentLeaveRepository)
    : super(UpdateStudentLeaveStatusInitial());
  final StudentLeaveRepository studentLeaveRepository;

  Future<void> updateStudentLeaveStatus({
    required int leaveId,
    required String status,
    String? reason,
  }) async {
    emit(UpdateStudentLeaveStatusInProgress());
    try {
      await studentLeaveRepository.updateStudentLeaveStatus(
        leaveId: leaveId.toString(),
        status: status,
        reason: reason,
      );
      emit(UpdateStudentLeaveStatusSuccess(status: status, id: leaveId));
    } catch (e) {
      emit(UpdateStudentLeaveStatusFailure(ApiException.fromException(e)));
    }
  }
}
