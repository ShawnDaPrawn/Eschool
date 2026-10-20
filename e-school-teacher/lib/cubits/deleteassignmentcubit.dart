import 'package:eschool_teacher/data/repositories/assignmentRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class DeleteAssignmentState {}

class DeleteAssignmentInitial extends DeleteAssignmentState {}

class DeleteAssignmentInProgress extends DeleteAssignmentState {}

class DeleteAssignmentSuccess extends DeleteAssignmentState {}

class DeleteAssignmentFailure extends DeleteAssignmentState {
  DeleteAssignmentFailure(this.errorMessage);
  final String errorMessage;
}

class DeleteAssignmentCubit extends Cubit<DeleteAssignmentState> {
  DeleteAssignmentCubit(this._assignmentRepository)
    : super(DeleteAssignmentInitial());
  final AssignmentRepository _assignmentRepository;

  Future<void> deleteAssignment({required int assignmentId}) async {
    try {
      emit(DeleteAssignmentInProgress());
      await _assignmentRepository
          .deleteAssignment(assignmentId: assignmentId)
          .then((_) => emit(DeleteAssignmentSuccess()));
    } catch (e) {
      emit(DeleteAssignmentFailure(e.toString()));
    }
  }
}
