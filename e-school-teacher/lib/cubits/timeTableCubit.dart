import 'package:eschool_teacher/data/models/timeTableSlot.dart';
import 'package:eschool_teacher/data/repositories/teacherRepository.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

abstract class TimeTableState {}

class TimeTableInitial extends TimeTableState {}

class TimeTableFetchInProgress extends TimeTableState {}

class TimeTableFetchSuccess extends TimeTableState {

  TimeTableFetchSuccess(this.timetableSlots);
  final List<TimeTableSlot> timetableSlots;
}

class TimeTableFetchFailure extends TimeTableState {

  TimeTableFetchFailure(this.errorMessage);
  final String errorMessage;
}

class TimeTableCubit extends Cubit<TimeTableState> {

  TimeTableCubit(this._teacherRepository) : super(TimeTableInitial());
  final TeacherRepository _teacherRepository;

  Future<void> fetchTimeTable() async {
    emit(TimeTableFetchInProgress());
    try {
      emit(TimeTableFetchSuccess(await _teacherRepository.fetchTimeTable()));
    } catch (e) {
      emit(TimeTableFetchFailure(e.toString()));
    }
  }
}
