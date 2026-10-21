import 'package:eschool_teacher/cubits/appConfigurationCubit.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_teacher/data/models/event.dart';
import 'package:eschool_teacher/data/models/exam.dart';
import 'package:eschool_teacher/data/models/holiday.dart';
import 'package:eschool_teacher/data/repositories/studentRepository.dart';
import 'package:eschool_teacher/data/repositories/systemInfoRepository.dart';
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

typedef AcademicCalendarHighlightColors = ({
  Color holiday,
  Color event,
  Color exam,
  Color semesterBreak,
});

abstract class AcademicCalendarState {}

class AcademicCalendarInitial extends AcademicCalendarState {}

class AcademicCalendarFetchInProgress extends AcademicCalendarState {}

class AcademicCalendarFetchSuccess extends AcademicCalendarState {
  AcademicCalendarFetchSuccess({required this.academicCalendarItems});
  final List<AcademicCalendar> academicCalendarItems;
}

class AcademicCalendarFetchFailure extends AcademicCalendarState {
  AcademicCalendarFetchFailure(this.errorMessage);
  final String errorMessage;
}

class AcademicCalendarCubit extends Cubit<AcademicCalendarState> {
  AcademicCalendarCubit(
    this._systemRepository,
    this._studentRepository, {
    required this.appConfigurationCubit,
    required this.highlights,
  }) : super(AcademicCalendarInitial());
  final SystemRepository _systemRepository;
  final StudentRepository _studentRepository;
  final AppConfigurationCubit appConfigurationCubit;

  /// Resolved from the theme when the cubit is created, because the items
  /// are built after an await, where no BuildContext is safe to use.
  final AcademicCalendarHighlightColors highlights;

  Future<void> fetchData() async {
    emit(AcademicCalendarFetchInProgress());
    try {
      emit(
        AcademicCalendarFetchSuccess(
          academicCalendarItems: [
            ...(await _systemRepository.fetchHolidays()).map<AcademicCalendar>(
              (holiday) => AcademicCalendar<Holiday>(
                data: holiday,
                date: holiday.date,
                highlightColor: highlights.holiday,
              ),
            ),
            ...(await _systemRepository.fetchEvents()).map<AcademicCalendar>(
              (event) => AcademicCalendar<Event>(
                data: event,
                date: event.startDate,
                highlightColor: highlights.event,
                rangeEndDate: event.endDate,
              ),
            ),
            ...(await _studentRepository.fetchExamsList(
              examStatus: 0,
              getTimetable: true,
            )).map<AcademicCalendar>(
              (exam) => AcademicCalendar<Exam>(
                data: exam,
                date: exam.getStartDate(),
                highlightColor: highlights.exam,
                rangeEndDate: exam.getEndDate(),
              ),
            ),
            ...(appConfigurationCubit.getAppConfiguration().semesterBreaks ??
                    [])
                .map<AcademicCalendar>(
                  (breakItem) => AcademicCalendar<SemesterBreak>(
                    data: breakItem,
                    date: breakItem.startDate,
                    highlightColor: highlights.semesterBreak,
                    rangeEndDate: breakItem.endDate,
                  ),
                ),
          ],
        ),
      );
    } catch (e) {
      emit(AcademicCalendarFetchFailure(e.toString()));
    }
  }

  List<AcademicCalendar> calendarItems() {
    if (state is AcademicCalendarFetchSuccess) {
      return (state as AcademicCalendarFetchSuccess).academicCalendarItems;
    }
    return [];
  }
}
