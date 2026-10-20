import 'package:eschool_teacher/data/models/pickedStudyMaterial.dart';
import 'package:eschool_teacher/data/models/studyMaterial.dart';
import 'package:eschool_teacher/data/repositories/studyMaterialRepositoy.dart';
import 'package:eschool_teacher/utils/api.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class UpdateStudyMaterialState {}

class UpdateStudyMaterialInitial extends UpdateStudyMaterialState {}

class UpdateStudyMaterialInProgress extends UpdateStudyMaterialState {}

class UpdateStudyMaterialSuccess extends UpdateStudyMaterialState {
  UpdateStudyMaterialSuccess(this.studyMaterial);
  final StudyMaterial studyMaterial;
}

class UpdateStudyMaterialFailure extends UpdateStudyMaterialState {
  UpdateStudyMaterialFailure(this.exception);
  final ApiException exception;
}

class UpdateStudyMaterialCubit extends Cubit<UpdateStudyMaterialState> {
  UpdateStudyMaterialCubit(this._studyMaterialRepository)
    : super(UpdateStudyMaterialInitial());
  final StudyMaterialRepository _studyMaterialRepository;

  Future<void> updateStudyMaterial({
    required int fileId,
    required PickedStudyMaterial pickedStudyMaterial,
  }) async {
    emit(UpdateStudyMaterialInProgress());
    try {
      final fileDetails = await pickedStudyMaterial.toJson();
      final result = await _studyMaterialRepository.updateStudyMaterial(
        fileId: fileId,
        fileDetails: fileDetails,
      );

      emit(UpdateStudyMaterialSuccess(result));
    } catch (e) {
      emit(UpdateStudyMaterialFailure(ApiException.fromException(e)));
    }
  }
}
