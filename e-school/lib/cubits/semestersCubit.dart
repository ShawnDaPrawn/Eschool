import 'package:eschool/data/models/semester.dart';
import 'package:eschool/data/repositories/systemInfoRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class SemesterState {}

class SemesterFetchInitial extends SemesterState {}

class SemesterFetchLoading extends SemesterState {}

class SemesterFetchSuccess extends SemesterState {
  SemesterFetchSuccess({required this.semesters});
  final List<Semester> semesters;
}

class SemesterFetchFailure extends SemesterState {
  SemesterFetchFailure(this.errorMessage);
  final String errorMessage;
}

class SemestersCubit extends Cubit<SemesterState> {
  SemestersCubit() : super(SemesterFetchInitial());

  void fetchSemesters() async {
    emit(SemesterFetchLoading());
    try {
      final semesters = await SystemRepository().fetchSemesters();
      emit(SemesterFetchSuccess(semesters: semesters));
    } catch (e) {
      emit(SemesterFetchFailure(e.toString()));
    }
  }
}
