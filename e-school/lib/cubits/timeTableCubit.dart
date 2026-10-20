import 'package:equatable/equatable.dart';
import 'package:eschool/data/models/timeTableSlot.dart';
import 'package:eschool/data/repositories/studentRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class TimeTableState extends Equatable {}

class TimeTableInitial extends TimeTableState {
  @override
  List<Object?> get props => [];
}

class TimeTableFetchInProgress extends TimeTableState {
  @override
  List<Object?> get props => [];
}

class TimeTableFetchSuccess extends TimeTableState {

  TimeTableFetchSuccess({required this.timeTable});
  final List<TimeTableSlot> timeTable;
  @override
  List<Object?> get props => [timeTable];
}

class TimeTableFetchFailure extends TimeTableState {

  TimeTableFetchFailure(this.errorMessage);
  final String errorMessage;

  @override
  List<Object?> get props => [errorMessage];
}

class TimeTableCubit extends Cubit<TimeTableState> {

  TimeTableCubit(this._studentRepository) : super(TimeTableInitial());
  final StudentRepository _studentRepository;

  void fetchStudentTimeTable({required bool useParentApi, int? childId}) {
    emit(TimeTableFetchInProgress());
    _studentRepository
        .fetchTimeTable(childId: childId ?? 0, useParentApi: useParentApi)
        .then((value) => emit(TimeTableFetchSuccess(timeTable: value)))
        .catchError((e) => emit(TimeTableFetchFailure(e.toString())));
  }
}
