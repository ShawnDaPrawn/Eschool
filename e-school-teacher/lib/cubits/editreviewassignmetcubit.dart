// ignore: depend_on_referenced_packages
import 'package:bloc/bloc.dart';
// ignore: depend_on_referenced_packages
import 'package:eschool_teacher/data/repositories/reviewAssignmentRepository.dart';
import 'package:flutter/foundation.dart';

@immutable
abstract class EditReviewAssignmetState {}

class EditReviewAssignmetInitial extends EditReviewAssignmetState {}

class EditReviewAssignmetInProgress extends EditReviewAssignmetState {}

class EditReviewAssignmetSuccess extends EditReviewAssignmetState {}

class EditReviewAssignmetFailure extends EditReviewAssignmetState {
  EditReviewAssignmetFailure({
    required this.errorMessage,
  });
  final String errorMessage;
}

class EditReviewAssignmetCubit extends Cubit<EditReviewAssignmetState> {
  EditReviewAssignmetCubit(
    this._reviewAssignmentRepository,
  ) : super(EditReviewAssignmetInitial());
  final ReviewAssignmentRepository _reviewAssignmentRepository;

  Future<void> updateReviewAssignmet({
    required int reviewAssignmetId,
    required int reviewAssignmentStatus,
    String? reviewAssignmentPoints,
    String? reviewAssignmentFeedBack,
  }) async {
    try {
      emit(EditReviewAssignmetInProgress());
      await _reviewAssignmentRepository.updateReviewAssignment(
        reviewAssignmetId: reviewAssignmetId,
        reviewAssignmentStatus: reviewAssignmentStatus,
        reviewAssignmentPoints: reviewAssignmentPoints!.isNotEmpty
            ? int.parse(reviewAssignmentPoints)
            : 0,
        reviewAssignmentFeedBack: reviewAssignmentFeedBack!,
      );
      emit(EditReviewAssignmetSuccess());
    } catch (e) {
      emit(EditReviewAssignmetFailure(errorMessage: e.toString()));
    }
  }
}
