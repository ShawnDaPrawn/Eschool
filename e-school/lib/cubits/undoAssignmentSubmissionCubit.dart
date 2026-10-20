import 'package:equatable/equatable.dart';
import 'package:eschool/data/repositories/assignmentRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class UndoAssignmentSubmissionState extends Equatable {}

class UndoAssignmentSubmissionInitial extends UndoAssignmentSubmissionState {
  @override
  List<Object?> get props => [];
}

class UndoAssignmentSubmissionInProgress extends UndoAssignmentSubmissionState {
  UndoAssignmentSubmissionInProgress();
  @override
  List<Object?> get props => [];
}

class UndoAssignmentSubmissionSuccess extends UndoAssignmentSubmissionState {
  @override
  List<Object?> get props => [];
}

class UndoAssignmentSubmissionFailure extends UndoAssignmentSubmissionState {

  UndoAssignmentSubmissionFailure(this.errorMessage);
  final String errorMessage;

  @override
  List<Object?> get props => [errorMessage];
}

class UndoAssignmentSubmissionCubit
    extends Cubit<UndoAssignmentSubmissionState> {

  UndoAssignmentSubmissionCubit(this._assignmentRepository)
      : super(UndoAssignmentSubmissionInitial());
  final AssignmentRepository _assignmentRepository;

  Future<void> undoAssignmentSubmission(
      {required int assignmentSubmissionId}) async {
    try {
      emit(UndoAssignmentSubmissionInProgress());
      await _assignmentRepository.deleteAssignment(
        assignmentSubmissionId: assignmentSubmissionId,
      );
      emit(UndoAssignmentSubmissionSuccess());
    } catch (e) {
      emit(UndoAssignmentSubmissionFailure(e.toString()));
    }
  }
}
