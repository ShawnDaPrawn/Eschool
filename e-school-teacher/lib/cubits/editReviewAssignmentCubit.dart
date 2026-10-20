// ignore: depend_on_referenced_packages
import 'package:bloc/bloc.dart';
// ignore: depend_on_referenced_packages
import 'package:eschool_teacher/data/repositories/reviewAssignmentRepository.dart';
import 'package:flutter/foundation.dart';

@immutable
abstract class EditReviewAssignmentState {}

class EditReviewAssignmentInitial extends EditReviewAssignmentState {}

class EditReviewAssignmentInProgress extends EditReviewAssignmentState {}

class EditReviewAssignmentSuccess extends EditReviewAssignmentState {}

class EditReviewAssignmentFailure extends EditReviewAssignmentState {
  EditReviewAssignmentFailure({
    required this.errorMessage,
  });
  final String errorMessage;
}

class EditReviewAssignmentCubit extends Cubit<EditReviewAssignmentState> {
  EditReviewAssignmentCubit(
    this._reviewAssignmentRepository,
  ) : super(EditReviewAssignmentInitial());
  final ReviewAssignmentRepository _reviewAssignmentRepository;

  Future<void> updateReviewAssignment({
    required int reviewAssignmetId,
    required int reviewAssignmentStatus,
    String? reviewAssignmentPoints,
    String? reviewAssignmentFeedBack,
  }) async {
    try {
      emit(EditReviewAssignmentInProgress());
      await _reviewAssignmentRepository.updateReviewAssignment(
        reviewAssignmetId: reviewAssignmetId,
        reviewAssignmentStatus: reviewAssignmentStatus,
        reviewAssignmentPoints: reviewAssignmentPoints!.isNotEmpty
            ? int.parse(reviewAssignmentPoints)
            : 0,
        reviewAssignmentFeedBack: reviewAssignmentFeedBack!,
      );
      emit(EditReviewAssignmentSuccess());
    } catch (e) {
      emit(EditReviewAssignmentFailure(errorMessage: e.toString()));
    }
  }
}
