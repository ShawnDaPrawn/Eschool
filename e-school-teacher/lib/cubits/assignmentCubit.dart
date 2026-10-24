import 'package:eschool_teacher/data/models/assignment.dart';
import 'package:eschool_teacher/data/repositories/assignmentRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class AssignmentState {}

class AssignmentInitial extends AssignmentState {}

class AssignmentFetchInProgress extends AssignmentState {}

class AssignmentsFetchSuccess extends AssignmentState {
  AssignmentsFetchSuccess({
    required this.assignment,
    required this.totalPage,
    required this.currentPage,
    required this.moreAssignmentsFetchError,
    required this.fetchMoreAssignmentsInProgress,
  });
  final List<Assignment> assignment;
  final int totalPage;
  final int currentPage;
  final bool moreAssignmentsFetchError;
  final bool fetchMoreAssignmentsInProgress;
  AssignmentsFetchSuccess copywith({
    List<Assignment>? newAssignment,
    int? newTotalPage,
    int? newCurrentPage,
    bool? newMoreAssignmentsFetchError,
    bool? newFetchMoreAssignmentsInProgress,
  }) {
    return AssignmentsFetchSuccess(
      assignment: newAssignment ?? assignment,
      totalPage: newTotalPage ?? totalPage,
      currentPage: newCurrentPage ?? currentPage,
      moreAssignmentsFetchError:
          newMoreAssignmentsFetchError ?? moreAssignmentsFetchError,
      fetchMoreAssignmentsInProgress:
          newFetchMoreAssignmentsInProgress ?? fetchMoreAssignmentsInProgress,
    );
  }
}

class AssignmentFetchFailure extends AssignmentState {
  AssignmentFetchFailure(this.errorMessage);
  final String errorMessage;
}

class AssignmentCubit extends Cubit<AssignmentState> {
  AssignmentCubit(this._assignmentRepository) : super(AssignmentInitial());
  final AssignmentRepository _assignmentRepository;

  Future<void> fetchAssignment({
    required int classSectionId,
    required int subjectId,
    String? termId,
    int? page,
  }) async {
    try {
      emit(AssignmentFetchInProgress());
      await _assignmentRepository
          .fetchAssignment(
            classSectionId: classSectionId,
            subjectId: subjectId,
            termId: termId,
            page: page,
          )
          .then(
            (result) => emit(
              AssignmentsFetchSuccess(
                assignment: result['assignment'],
                currentPage: result['currentPage'],
                totalPage: result['lastPage'],
                moreAssignmentsFetchError: false,
                fetchMoreAssignmentsInProgress: false,
              ),
            ),
          )
          .catchError((e) {});
    } catch (e) {
      return emit(AssignmentFetchFailure(e.toString()));
    }
  }

  void updateState(AssignmentState updatedState) {
    emit(updatedState);
  }

  bool hasMore() {
    if (state is AssignmentsFetchSuccess) {
      return (state as AssignmentsFetchSuccess).currentPage <
          (state as AssignmentsFetchSuccess).totalPage;
    }
    return false;
  }

  Future<void> fetchMoreAssignment({
    required int classSectionId,
    required int subjectId,
    String? termId,
  }) async {
    try {
      emit(
        (state as AssignmentsFetchSuccess).copywith(
          newFetchMoreAssignmentsInProgress: true,
        ),
      );

      final fetchMoreAssignment = await _assignmentRepository.fetchAssignment(
        classSectionId: classSectionId,
        subjectId: subjectId,
        termId: termId,
        page: (state as AssignmentsFetchSuccess).currentPage + 1,
      );

      final currentState = state as AssignmentsFetchSuccess;

      final List<Assignment> assignments = currentState.assignment;

      assignments.addAll(fetchMoreAssignment['assignment']);

      emit(
        AssignmentsFetchSuccess(
          assignment: assignments,
          totalPage: fetchMoreAssignment['lastPage'],
          currentPage: fetchMoreAssignment['currentPage'],
          moreAssignmentsFetchError: false,
          fetchMoreAssignmentsInProgress: false,
        ),
      );
    } catch (e) {
      emit(
        (state as AssignmentsFetchSuccess).copywith(
          newMoreAssignmentsFetchError: true,
          newFetchMoreAssignmentsInProgress: false,
        ),
      );
      // throw ApiException(e.toString());
    }
  }

  Future<void> deleteAssignment(int assignmentId) async {
    if (state is AssignmentsFetchSuccess) {
      final List<Assignment> listOfAssignments =
          (state as AssignmentsFetchSuccess).assignment;

      listOfAssignments.removeWhere((element) => element.id == assignmentId);

      emit(
        AssignmentsFetchSuccess(
          assignment: listOfAssignments,
          currentPage: (state as AssignmentsFetchSuccess).currentPage,
          fetchMoreAssignmentsInProgress:
              (state as AssignmentsFetchSuccess).fetchMoreAssignmentsInProgress,
          moreAssignmentsFetchError:
              (state as AssignmentsFetchSuccess).moreAssignmentsFetchError,
          totalPage: (state as AssignmentsFetchSuccess).totalPage,
        ),
      );
    }
  }
}
