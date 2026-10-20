import 'package:eschool/utils/labelKeys.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class AssignmentsTabSelectionState {

  AssignmentsTabSelectionState({
    required this.assignmentFilterBySubjectId,
    required this.assignmentFilterTabTitle,
  });
  //Assigned or completed
  final String assignmentFilterTabTitle;
  final int assignmentFilterBySubjectId;
}

class AssignmentsTabSelectionCubit extends Cubit<AssignmentsTabSelectionState> {
  AssignmentsTabSelectionCubit()
      : super(
          AssignmentsTabSelectionState(
            assignmentFilterBySubjectId: 0,
            assignmentFilterTabTitle: assignedKey,
          ),
        ); //Not-submitted/Assigned by default

  void changeAssignmentFilterTabTitle(String assignmentFilterTabTitle) {
    emit(
      AssignmentsTabSelectionState(
        assignmentFilterBySubjectId: state.assignmentFilterBySubjectId,
        assignmentFilterTabTitle: assignmentFilterTabTitle,
      ),
    );
  }

  void changeAssignmentFilterBySubjectId(int assignmentFilterBySubjectId) {
    emit(
      AssignmentsTabSelectionState(
        assignmentFilterBySubjectId: assignmentFilterBySubjectId,
        assignmentFilterTabTitle: state.assignmentFilterTabTitle,
      ),
    );
  }

  int isAssignmentSubmitted() {
    return state.assignmentFilterTabTitle == assignedKey ? 0 : 1;
  }
}
