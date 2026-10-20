import 'package:equatable/equatable.dart';
import 'package:eschool/data/models/parent.dart';
import 'package:eschool/data/repositories/studentRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class StudentParentDetailsState extends Equatable {}

class StudentParentDetailsInitial extends StudentParentDetailsState {
  @override
  List<Object?> get props => [];
}

class StudentParentDetailsFetchInProgress extends StudentParentDetailsState {
  @override
  List<Object?> get props => [];
}

class StudentParentDetailsFetchSuccess extends StudentParentDetailsState {

  StudentParentDetailsFetchSuccess({
    required this.father,
    required this.guardian,
    required this.mother,
  });
  final Parent? mother;
  final Parent? father;
  final Parent? guardian;
  @override
  List<Object?> get props => [mother, father, guardian];
}

class StudentParentDetailsFetchFailure extends StudentParentDetailsState {

  StudentParentDetailsFetchFailure(this.errorMessage);
  final String errorMessage;

  @override
  List<Object?> get props => [];
}

class StudentParentDetailsCubit extends Cubit<StudentParentDetailsState> {

  StudentParentDetailsCubit(this._studentRepository)
      : super(StudentParentDetailsInitial());
  final StudentRepository _studentRepository;

  Future<void> getStudentParentDetails() async {
    emit(StudentParentDetailsFetchInProgress());
    try {
      final result = await _studentRepository.fetchParentDetails();
      emit(
        StudentParentDetailsFetchSuccess(
          father: result['father'],
          guardian: result['guardian'],
          mother: result['mother'],
        ),
      );
    } catch (e) {
      emit(StudentParentDetailsFetchFailure(e.toString()));
    }
  }
}
