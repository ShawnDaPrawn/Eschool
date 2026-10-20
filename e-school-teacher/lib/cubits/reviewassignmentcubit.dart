// ignore: depend_on_referenced_packages
import 'package:bloc/bloc.dart';
// ignore: depend_on_referenced_packages

import 'package:eschool_teacher/data/models/reviewAssignmentssubmition.dart';
import 'package:eschool_teacher/data/repositories/reviewAssignmentRepository.dart';
import 'package:flutter/foundation.dart';

@immutable
abstract class ReviewAssignmentState {}

class ReviewAssignmentInitial extends ReviewAssignmentState {}

class ReviewAssignmentInProcess extends ReviewAssignmentState {}

class ReviewAssignmentSuccess extends ReviewAssignmentState {

  ReviewAssignmentSuccess({
    required this.reviewAssignment,
  });
  final List<ReviewAssignmentsSubmission> reviewAssignment;
}

class ReviewAssignmentFailure extends ReviewAssignmentState {
  ReviewAssignmentFailure({
    required this.errorMessage,
  });
  final String errorMessage;
}

class ReviewAssignmentCubit extends Cubit<ReviewAssignmentState> {
  ReviewAssignmentCubit(this._reviewAssignmentRepository)
      : super(ReviewAssignmentInitial());
  final ReviewAssignmentRepository _reviewAssignmentRepository;

  Future<void> fetchReviewAssignment({
    required int assignmentId,
  }) async {
    try {
      emit(ReviewAssignmentInProcess());
      await _reviewAssignmentRepository
          .fetchReviewAssignment(assignmetId: assignmentId)
          .then((value) {
        emit(
          ReviewAssignmentSuccess(
            reviewAssignment: value,
          ),
        );
      });
    } catch (e) {
      emit(ReviewAssignmentFailure(errorMessage: e.toString()));
    }
  }

  Future<void> updateReviewAssignmet({
    required ReviewAssignmentsSubmission updatedReviewAssignmentSubmition,
  }) async {
    try {
      final List<ReviewAssignmentsSubmission> currentassignment =
          (state as ReviewAssignmentSuccess).reviewAssignment;
      final List<ReviewAssignmentsSubmission> updateassignment =
          List.from(currentassignment);
      final int reviewAssignmentIndex = currentassignment.indexWhere(
        (element) => element.id == updatedReviewAssignmentSubmition.id,
      );
      updateassignment[reviewAssignmentIndex] =
          updatedReviewAssignmentSubmition;
      emit(ReviewAssignmentSuccess(reviewAssignment: updateassignment));
    } catch (e) {
      emit(ReviewAssignmentFailure(errorMessage: e.toString()));
    }
  }

  List<ReviewAssignmentsSubmission> reviewAssignment() {
    return (state as ReviewAssignmentSuccess).reviewAssignment;
  }
}
