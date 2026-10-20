import 'package:eschool/data/models/teacher.dart';
import 'package:eschool/data/repositories/parentRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class ChildTeachersState {}

class ChildTeachersInitial extends ChildTeachersState {}

class ChildTeachersFetchInProgress extends ChildTeachersState {}

class ChildTeachersFetchSuccess extends ChildTeachersState {

  ChildTeachersFetchSuccess({required this.teachers});
  final List<Teacher> teachers;
}

class ChildTeachersFetchFailure extends ChildTeachersState {

  ChildTeachersFetchFailure(this.errorMessage);
  final String errorMessage;
}

class ChildTeachersCubit extends Cubit<ChildTeachersState> {

  ChildTeachersCubit(this._parentRepository) : super(ChildTeachersInitial());
  final ParentRepository _parentRepository;

  Future<void> fetchChildTeachers({required int childId}) async {
    emit(ChildTeachersFetchInProgress());
    try {
      final teachers =
          await _parentRepository.fetchChildTeachers(childId: childId);
      emit(ChildTeachersFetchSuccess(teachers: teachers));
    } catch (e) {
      emit(ChildTeachersFetchFailure(e.toString()));
    }
  }
}
