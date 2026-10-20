import 'package:dio/dio.dart';
import 'package:equatable/equatable.dart';
import 'package:eschool/data/models/assignment.dart';
import 'package:eschool/data/repositories/assignmentRepository.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class UploadAssignmentState extends Equatable {}

class UploadAssignmentInitial extends UploadAssignmentState {
  @override
  List<Object?> get props => [];
}

class UploadAssignmentInProgress extends UploadAssignmentState {

  UploadAssignmentInProgress(this.uploadedProgress);
  final double uploadedProgress;
  @override
  List<Object?> get props => [uploadedProgress];
}

class UploadAssignmentFetchSuccess extends UploadAssignmentState {

  UploadAssignmentFetchSuccess({required this.assignmentSubmission});
  final AssignmentSubmission assignmentSubmission;
  @override
  List<Object?> get props => [assignmentSubmission];
}

class UploadAssignmentCanceled extends UploadAssignmentState {
  @override
  List<Object?> get props => [];
}

class UploadAssignmentFailure extends UploadAssignmentState {

  UploadAssignmentFailure(this.errorMessage);
  final String errorMessage;

  @override
  List<Object?> get props => [errorMessage];
}

class UploadAssignmentCubit extends Cubit<UploadAssignmentState> {

  UploadAssignmentCubit(this._assignmentRepository)
      : super(UploadAssignmentInitial());
  final AssignmentRepository _assignmentRepository;

  final CancelToken _cancelToken = CancelToken();

  void _uploadAssignmentPercentage(double percentage) {
    emit(UploadAssignmentInProgress(percentage));
  }

  Future<void> uploadAssignment({
    required int assignmentId,
    required BuildContext context,
    required int assignmentSubmissionId,
    required List<String> filePaths,
    required String textSubmission,
  }) async {
    emit(UploadAssignmentInProgress(0));
    try {
      late AssignmentSubmission result;

      result = await _assignmentRepository.submitAssignment(
        assignmentId: assignmentId,
        filePaths: filePaths,
        cancelToken: _cancelToken,
        updateUploadAssignmentPercentage: _uploadAssignmentPercentage,
        textSubmission: textSubmission,
      );
      emit(UploadAssignmentFetchSuccess(assignmentSubmission: result));
    } catch (e) {
      if (_cancelToken.isCancelled) {
        emit(UploadAssignmentCanceled());
      } else {
        emit(UploadAssignmentFailure(e.toString()));
      }
    }
  }

  void cancelUploadAssignmentProcess() {
    _cancelToken.cancel();
  }
}
