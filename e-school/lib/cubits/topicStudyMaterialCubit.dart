import 'package:equatable/equatable.dart';
import 'package:eschool/data/models/studyMaterial.dart';
import 'package:eschool/data/repositories/subjectRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class TopicStudyMaterialState extends Equatable {}

class TopicStudyMaterialInitial extends TopicStudyMaterialState {
  @override
  List<Object?> get props => [];
}

class TopicStudyMaterialFetchInProgress extends TopicStudyMaterialState {
  @override
  List<Object?> get props => [];
}

class TopicStudyMaterialFetchSuccess extends TopicStudyMaterialState {

  TopicStudyMaterialFetchSuccess({required this.studyMaterials});
  final List<StudyMaterial> studyMaterials;
  @override
  List<Object?> get props => [studyMaterials];
}

class TopicStudyMaterialFetchFailure extends TopicStudyMaterialState {

  TopicStudyMaterialFetchFailure(this.errorMessage);
  final String errorMessage;

  @override
  List<Object?> get props => [errorMessage];
}

class TopicStudyMaterialCubit extends Cubit<TopicStudyMaterialState> {

  TopicStudyMaterialCubit(this._subjectRepository)
      : super(TopicStudyMaterialInitial());
  final SubjectRepository _subjectRepository;

  void fetchStudyMaterials({
    required int lessonId,
    required int topicId,
    required bool userParentApi,
    int? childId,
  }) {
    emit(TopicStudyMaterialFetchInProgress());
    _subjectRepository
        .getStudyMaterialOfTopic(
          childId: childId ?? 0,
          useParentApi: userParentApi,
          lessonId: lessonId,
          topicId: topicId,
        )
        .then(
          (value) =>
              emit(TopicStudyMaterialFetchSuccess(studyMaterials: value)),
        )
        .catchError((e) => emit(TopicStudyMaterialFetchFailure(e.toString())));
  }
}
