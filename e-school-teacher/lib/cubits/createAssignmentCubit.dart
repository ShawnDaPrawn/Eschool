import 'package:eschool_teacher/data/repositories/assignmentRepository.dart';
import 'package:eschool_teacher/utils/api.dart';
import 'package:file_picker/file_picker.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class CreateAssignmentState {}

class CreateAssignmentInitial extends CreateAssignmentState {}

class CreateAssignmentInProcess extends CreateAssignmentState {}

class CreateAssignmentSuccess extends CreateAssignmentState {}

class CreateAssignmentFailure extends CreateAssignmentState {
  CreateAssignmentFailure({required this.exception});
  final ApiException exception;
}

class CreateAssignmentCubit extends Cubit<CreateAssignmentState> {
  CreateAssignmentCubit(this._assignmentRepository)
    : super(CreateAssignmentInitial());
  final AssignmentRepository _assignmentRepository;

  Future<void> createAssignment({
    required int classId,
    required int subjectId,
    String? semesterId,
    required String name,
    required String instruction,
    required String datetime,
    required String points,
    required bool resubmission,
    required String extraDayForResubmission,
    List<PlatformFile>? file,
  }) async {
    emit(CreateAssignmentInProcess());
    try {
      await _assignmentRepository
          .createAssignment(
            classId: classId,
            subjectId: subjectId,
            semesterId: semesterId,
            name: name,
            datetime: datetime,
            resubmission: resubmission,
            extraDayForResubmission: int.parse(
              extraDayForResubmission.isEmpty ? '0' : extraDayForResubmission,
            ),
            filePaths: file,
            instruction: instruction,
            points: int.parse(points.isEmpty ? '0' : points),
          )
          .then((value) => emit(CreateAssignmentSuccess()));
    } catch (e) {
      emit(CreateAssignmentFailure(exception: ApiException.fromException(e)));
    }
  }
}
