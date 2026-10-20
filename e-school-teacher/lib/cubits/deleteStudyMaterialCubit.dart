import 'package:eschool_teacher/data/repositories/studyMaterialRepositoy.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class DeleteStudyMaterialState {}

class DeleteStudyMaterialInitial extends DeleteStudyMaterialState {}

class DeleteStudyMaterialInProgress extends DeleteStudyMaterialState {}

class DeleteStudyMaterialSuccess extends DeleteStudyMaterialState {}

class DeleteStudyMaterialFailure extends DeleteStudyMaterialState {

  DeleteStudyMaterialFailure(this.errorMessage);
  final String errorMessage;
}

class DeleteStudyMaterialCubit extends Cubit<DeleteStudyMaterialState> {

  DeleteStudyMaterialCubit(this._studyMaterialRepository)
      : super(DeleteStudyMaterialInitial());
  final StudyMaterialRepository _studyMaterialRepository;

  Future<void> deleteStudyMaterial({required int fileId}) async {
    emit(DeleteStudyMaterialInProgress());
    try {
      await _studyMaterialRepository.deleteStudyMaterial(fileId: fileId);
      emit(DeleteStudyMaterialSuccess());
    } catch (e) {
      emit(DeleteStudyMaterialFailure(e.toString()));
    }
  }
}
