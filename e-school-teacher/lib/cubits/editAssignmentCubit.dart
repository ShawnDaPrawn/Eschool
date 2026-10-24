import 'package:eschool_teacher/data/repositories/assignmentRepository.dart';
import 'package:eschool_teacher/utils/api.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class EditAssignmentState {}

class EditAssignmentInitial extends EditAssignmentState {}

class EditAssignmentInProgress extends EditAssignmentState {}

class EditAssignmentSuccess extends EditAssignmentState {}

class EditAssignmentFailure extends EditAssignmentState {
  EditAssignmentFailure(this.exception);
  final ApiException exception;
}

class EditAssignmentCubit extends Cubit<EditAssignmentState> {
  EditAssignmentCubit(this._assignmentRepository)
    : super(EditAssignmentInitial());
  final AssignmentRepository _assignmentRepository;

  Future<void> editAssignment({
    required int assignmentId,
    required int classSelectionId,
    required int subjectId,
    String? termId,
    required String name,
    required String dateTime,
    required String instruction,
    required String points,
    required int resubmission,
    required String extraDayForResubmission,
    required List<PlatformFile> filePaths,
  }) async {
    emit(EditAssignmentInProgress());
    try {
      await _assignmentRepository.editAssignment(
        assignmentId: assignmentId,
        classSelectionId: classSelectionId,
        dateTime: dateTime,
        name: name,
        subjectId: subjectId,
        termId: termId,
        extraDayForResubmission: int.parse(
          extraDayForResubmission.isEmpty ? '0' : extraDayForResubmission,
        ),
        instruction: instruction,
        points: int.parse(points.isEmpty ? '0' : points),
        resubmission: resubmission,
        filePaths: filePaths,
      );
      emit(EditAssignmentSuccess());
    } catch (e) {
      emit(EditAssignmentFailure(ApiException.fromException(e)));
    }
  }
}
