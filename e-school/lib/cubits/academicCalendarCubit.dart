import 'package:eschool/cubits/appConfigurationCubit.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool/data/models/event.dart';
import 'package:eschool/data/models/exam.dart';
import 'package:eschool/data/models/holiday.dart';
import 'package:eschool/data/repositories/studentRepository.dart';
import 'package:eschool/data/repositories/systemInfoRepository.dart';
import 'package:eschool/ui/screens/academicCalendar/academicCalendarScreen.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

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
  }) : super(AcademicCalendarInitial());
  final SystemRepository _systemRepository;
  final StudentRepository _studentRepository;
  final AppConfigurationCubit appConfigurationCubit;

  /// [colors] are the marker colours of the items, read from the theme by
  /// the screen before the fetch starts.
  Future<void> fetchData({
    required AcademicCalendarColors colors,
    int? childId,
  }) async {
    emit(AcademicCalendarFetchInProgress());
    try {
      emit(
        AcademicCalendarFetchSuccess(
          academicCalendarItems: [
            ...(await _systemRepository.fetchHolidays()).map<AcademicCalendar>(
              (holiday) => AcademicCalendar<Holiday>(
                data: holiday,
                date: holiday.date,
                highlightColor: colors.holiday,
              ),
            ),
            ...(await _systemRepository.fetchEvents()).map<AcademicCalendar>(
              (event) => AcademicCalendar<Event>(
                data: event,
                date: event.startDate,
                highlightColor: colors.event,
                rangeEndDate: event.endDate,
              ),
            ),
            ...(await _studentRepository.fetchExamsList(
              childId: childId ?? 0,
              examStatus: 0,
              useParentApi: childId != null,
              getTimetable: true,
            )).map<AcademicCalendar>(
              (exam) => AcademicCalendar<Exam>(
                data: exam,
                date: exam.getStartDate(),
                highlightColor: colors.exam,
                rangeEndDate: exam.getEndDate(),
              ),
            ),
            ...(appConfigurationCubit.getAppConfiguration().termBreaks ?? [])
                .map<AcademicCalendar>(
                  (breakItem) => AcademicCalendar<TermBreak>(
                    data: breakItem,
                    date: breakItem.startDate,
                    highlightColor: colors.termBreak,
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
